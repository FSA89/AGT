<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\SetupProxyMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsMessageHandler]
class SetupProxyHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private HttpClientInterface $client,
        private ParameterBagInterface $params
    ) {}

    public function __invoke(SetupProxyMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) {
            $this->logger->error("Proxy Handler: Site ID {$message->getSiteId()} not found.");
            return;
        }

        // 1. Подготовка данных
        $domainRaw = $site->getArticle()?->getDomainUrl();
        // Чистим домен (как в python: убираем http, www)
        $domain = str_replace(['https://', 'http://', 'www.'], '', $domainRaw ?? '');
        $domain = rtrim($domain, '/');
        
        $cfEmail = $site->getCfEmail();
        $cfKey = $site->getCfApiKey();

        // Проверка обязательных полей
        if (!$domain || !$cfEmail || !$cfKey) {
            $this->updateStatus($site, 'Error: No Domain, Email or Key');
            return;
        }

        // Получаем настройки из services.yaml
        $jenkinsUrl = $this->params->get('jenkins.url');
        $user = $this->params->get('jenkins.user'); // themepark
        $token = $this->params->get('jenkins.token'); // твой токен

        $this->logger->info("⚙️ Jenkins Proxy Config start for: $domain");

        try {
            // Опции запроса (аналог verify=False и auth=...)
            $httpOptions = [
                'auth_basic' => [$user, $token],
                'verify_peer' => false, // Игнорируем самоподписанный SSL
                'verify_host' => false,
                'timeout' => 30,
            ];

            // --- ШАГ 1: Получаем Crumb (CSRF токен) ---
            $crumbUrl = rtrim($jenkinsUrl, '/') . '/crumbIssuer/api/json';
            $response = $this->client->request('POST', $crumbUrl, $httpOptions);
            
            $crumbData = $response->toArray();
            $crumbHeaderName = $crumbData['crumbRequestField'];
            $crumbValue = $crumbData['crumb'];

            $this->logger->info("🔑 Crumb received ($crumbValue)");

            // Добавляем Crumb в заголовки для следующих запросов
            $httpOptions['headers'] = [
                $crumbHeaderName => $crumbValue
            ];

            // --- ШАГ 2: Создаем Credentials в Jenkins ---
            // URL зависит от юзера (themepark)
            $credsUrl = rtrim($jenkinsUrl, '/') . "/user/$user/credentials/store/user/domain/_/createCredentials";
            
            // Формируем JSON payload, как в Python скрипте
            $jsonPayload = json_encode([
                "" => "0",
                "credentials" => [
                    "scope" => "GLOBAL",
                    "id" => $domain,
                    "username" => $cfEmail,
                    "password" => $cfKey,
                    "description" => $domain,
                    '$class' => "com.cloudbees.plugins.credentials.impl.UsernamePasswordCredentialsImpl"
                ]
            ]);

            try {
                // Jenkins требует отправлять JSON внутри form-param 'json'
                $this->client->request('POST', $credsUrl, array_merge($httpOptions, [
                    'body' => ['json' => $jsonPayload], 
                    'headers' => array_merge($httpOptions['headers'] ?? [], ['Content-Type' => 'application/x-www-form-urlencoded'])
                ]));
                $this->logger->info("👤 Credentials created.");
            } catch (\Exception $e) {
                // Если креды уже есть, Jenkins кинет ошибку. Игнорируем дубликаты.
                $msg = $e->getMessage();
                if (str_contains($msg, 'already exists') || str_contains($msg, '400') || str_contains($msg, '500')) {
                    $this->logger->warning("Credentials warning (ignoring): " . $msg);
                } else {
                    throw $e; // Если ошибка другая - выкидываем исключение
                }
            }

            // --- ШАГ 3: Запускаем Job ---
            $jobUrl = rtrim($jenkinsUrl, '/') . "/job/ThemePark_Proxy_Conf/buildWithParameters";
            
            $jobParams = [
                'CF_API_ZONE_NAME' => $domain,
                'REG_NAME' => 'dynadot'
            ];

            $jobResponse = $this->client->request('POST', $jobUrl, array_merge($httpOptions, [
                'body' => $jobParams
            ]));

            if ($jobResponse->getStatusCode() !== 201) {
                throw new \Exception("Job start failed: Code " . $jobResponse->getStatusCode());
            }

            // --- ШАГ 4: Ждем и обновляем статус ---
            $this->logger->info("🚀 Job started. Waiting 2 minutes...");
            $this->updateStatus($site, 'Job started: 2 min');

            // Пауза 2 минуты (как было в Python)
            sleep(120);

            $this->updateStatus($site, 'Proxy Success');
            $this->logger->info("✅ Proxy Configured for $domain");

        } catch (\Exception $e) {
            $errorMsg = substr($e->getMessage(), 0, 250);
            $this->updateStatus($site, "Error: Jenkins failed ($errorMsg)");
            $this->logger->error("Jenkins Error ($domain): " . $e->getMessage());
        }
    }

    private function updateStatus($site, string $status)
    {
        // Переоткрываем EntityManager на случай разрыва соединения во время sleep(120)
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }
        $site->setStatusProxy($status);
        $this->em->flush();
    }
}
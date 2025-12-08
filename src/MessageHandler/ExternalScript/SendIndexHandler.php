<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\SendIndexMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsMessageHandler]
class SendIndexHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private HttpClientInterface $client,
        private KernelInterface $kernel
    ) {}

    public function __invoke(SendIndexMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        $domainRaw = $site->getArticle()?->getDomainUrl();
        // Очистка домена (как в Python)
        $domain = str_replace(['https://', 'http://', 'www.'], '', $domainRaw ?? '');
        $domain = rtrim($domain, '/');

        $webmasterStr = $site->getWebmaster(); // Например "Вебмастер 10"

        if (!$domain || !$webmasterStr) {
            $this->updateStatus($site, 'Error: Нет домена или профиля');
            return;
        }

        // 1. Загружаем доступы (JSON)
        $account = $this->getYandexAccount($webmasterStr);
        if (!$account) {
            $this->updateStatus($site, "Error: Аккаунт ($webmasterStr) не найден в JSON");
            return;
        }

        $userId = $account['user_id'];
        $token = $account['token'];
        $headers = ['Authorization' => "OAuth $token"];

        $this->logger->info("🚀 Indexing start: $domain (User: $userId)");

        try {
            // 2. Ищем host_id
            // Python: requests.get(.../hosts)
            $response = $this->client->request('GET', "https://api.webmaster.yandex.net/v4/user/$userId/hosts", [
                'headers' => $headers,
                'timeout' => 15
            ]);
            
            $hostsData = $response->toArray();
            $expectedHost = "https:$domain:443";
            $hostId = null;

            foreach ($hostsData['hosts'] as $h) {
                if ($h['host_id'] === $expectedHost) {
                    $hostId = $h['host_id'];
                    break;
                }
            }

            if (!$hostId) {
                throw new \Exception("YVM Host ID not found (Сайт не добавлен в Вебмастер?)");
            }

            // 3. Отправка на переобход (Recrawl)
            // Python: requests.post(.../recrawl/queue)
            $recrawlUrl = "https://$domain/";
            $this->client->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostId/recrawl/queue", [
                'headers' => $headers,
                'json' => ['url' => $recrawlUrl],
                'timeout' => 15
            ]);

            // 4. Пауза 5 секунд (как в скрипте)
            sleep(5); 

            // 5. Добавление Sitemap
            // Python: requests.post(.../user-added-sitemaps)
            $sitemapUrl = "https://$domain/sitemap_index.xml";
            $this->client->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostId/user-added-sitemaps", [
                'headers' => $headers,
                'json' => ['url' => $sitemapUrl],
                'timeout' => 15
            ]);

            $this->updateStatus($site, 'Success (Отправлено на индексацию)');
            $this->logger->info("✅ Indexing Success: $domain");

        } catch (\Exception $e) {
            // Игнорируем 409 (уже добавлено) и 202 (принято), но HttpClient кидает эксепшн на 4xx.
            // Если ошибка 409 Conflict - это норм, значит уже в очереди.
            if (str_contains($e->getMessage(), '409')) {
                $this->updateStatus($site, 'Success (Уже в очереди)');
            } else {
                $errorMsg = substr($e->getMessage(), 0, 100);
                $this->updateStatus($site, "Error: $errorMsg");
                $this->logger->error("Indexing Error ($domain): " . $e->getMessage());
            }
        }
    }

    private function getYandexAccount(string $webmasterName): ?array
    {
        // Извлекаем номер "10" из "Вебмастер 10"
        if (preg_match('/(\d+)/', $webmasterName, $matches)) {
            $id = $matches[1];
            $path = $this->kernel->getProjectDir() . "/config/secrets/yandex_webmasters.json";
            
            if (!file_exists($path)) return null;
            
            $json = json_decode(file_get_contents($path), true);
            return $json[$id] ?? null;
        }
        return null;
    }

    private function updateStatus($site, string $status)
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }
        $site->setIndexingStatus($status);
        $this->em->flush();
    }
}
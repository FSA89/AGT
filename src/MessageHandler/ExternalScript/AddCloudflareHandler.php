<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\AddCloudflareMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class AddCloudflareHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function __invoke(AddCloudflareMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        // Берем данные из сайта (они заполняются при сохранении)
        $domain = $site->getArticle()?->getDomainUrl();
        $email = $site->getCfEmail();
        $apiKey = $site->getCfApiKey();

        if (!$domain || !$email || !$apiKey) {
            $this->updateStatus($site, 'Error: Нет данных (Domain/Email/Key)');
            return;
        }

        // Очистка домена от https://
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = str_replace('www.', '', $domain);

        $this->logger->info("☁️ Добавляю в CF: $domain ($email)");

        try {
            // 1. Создаем зону (POST /zones)
            $response = $this->httpClient->request('POST', 'https://api.cloudflare.com/client/v4/zones', [
                'headers' => [
                    'X-Auth-Email' => $email,
                    'X-Auth-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $domain,
                    'jump_start' => false
                ],
                'timeout' => 20
            ]);

            $data = $response->toArray(false); // false = не кидать исключение при 4xx

            if ($response->getStatusCode() === 200 && ($data['success'] ?? false)) {
                // УСПЕХ: Зона создана
                $ns = $data['result']['name_servers'] ?? [];
                $this->saveSuccess($site, $ns);
            } 
            elseif (isset($data['errors'][0]['code']) && $data['errors'][0]['code'] == 1061) {
                // ОШИБКА: "Zone already exists" (это ок)
                $this->logger->warning("CF: Домен уже существует. Получаю инфо...");
                $this->fetchExistingZoneNs($site, $domain, $email, $apiKey);
            } 
            else {
                // РЕАЛЬНАЯ ОШИБКА
                $error = $data['errors'][0]['message'] ?? 'Unknown Error';
                $this->updateStatus($site, "Error: $error");
            }

        } catch (\Exception $e) {
            $this->updateStatus($site, "Error: " . $e->getMessage());
        }
    }

    // Если зона уже была, делаем GET запрос, чтобы узнать NS
    private function fetchExistingZoneNs($site, $domain, $email, $apiKey)
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.cloudflare.com/client/v4/zones', [
                'headers' => ['X-Auth-Email' => $email, 'X-Auth-Key' => $apiKey],
                'query' => ['name' => $domain]
            ]);
            $data = $response->toArray();
            if (!empty($data['result'][0]['name_servers'])) {
                $this->saveSuccess($site, $data['result'][0]['name_servers']);
            } else {
                $this->updateStatus($site, "Error: Zone exists but no NS found");
            }
        } catch (\Exception $e) {
            $this->updateStatus($site, "Error (Get): " . $e->getMessage());
        }
    }

    private function saveSuccess($site, array $ns)
    {
        if (count($ns) >= 2) {
            $site->setNs1($ns[0]);
            $site->setNs2($ns[1]);
        }
        $site->setStatusCf('Success');
        $this->em->flush();
        $this->logger->info("✅ CF Успех: NS обновлены.");
    }

    private function updateStatus($site, string $msg)
    {
        $site->setStatusCf($msg);
        $this->em->flush();
    }
}
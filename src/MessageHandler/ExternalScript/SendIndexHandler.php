<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\SendIndexMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsMessageHandler]
class SendIndexHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private ManagerRegistry $doctrine, // Заменили EM на Registry для безопасного перезапуска
        private LoggerInterface $logger,
        private HttpClientInterface $client,
        private KernelInterface $kernel
    ) {}

    public function __invoke(SendIndexMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        $domainRaw = $site->getArticle()?->getDomainUrl();
        // Очистка домена
        $domain = str_replace(['https://', 'http://', 'www.'], '', $domainRaw ?? '');
        $domain = rtrim($domain, '/');

        $webmasterStr = $site->getWebmaster(); 

        if (!$domain || !$webmasterStr) {
            $this->updateStatus($message->getSiteId(), 'Error: Нет домена или профиля');
            return;
        }

        // 1. Загружаем доступы
        $account = $this->getYandexAccount($webmasterStr);
        if (!$account) {
            $this->updateStatus($message->getSiteId(), "Error: Аккаунт ($webmasterStr) не найден в JSON");
            return;
        }

        $userId = $account['user_id'];
        $token = $account['token'];
        $headers = ['Authorization' => "OAuth $token"];

        $this->logger->info("🚀 Indexing start: $domain (User: $userId)");

        try {
            // 2. Ищем host_id
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

            // ВАЖНО: Экранируем host_id для вставки в URL (https:site.ru:443 -> https%3Asite.ru%3A443)
            $hostIdEncoded = urlencode($hostId);

            // 3. Отправка на переобход (Recrawl)
            $recrawlUrl = "https://$domain/";
            $this->client->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostIdEncoded/recrawl/queue", [
                'headers' => $headers,
                'json' => ['url' => $recrawlUrl],
                'timeout' => 15
            ]);

            // 4. Пауза
            sleep(5); 

            // 5. Sitemap
            $sitemapUrl = "https://$domain/sitemap_index.xml";
            $this->client->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostIdEncoded/user-added-sitemaps", [
                'headers' => $headers,
                'json' => ['url' => $sitemapUrl],
                'timeout' => 15
            ]);

            $this->updateStatus($message->getSiteId(), 'Success (Отправлено на индексацию)');
            $this->logger->info("✅ Indexing Success: $domain");

        } catch (\Throwable $e) {
            // Ловим 409 (Conflict - уже добавлено) через ClientException
            if ($e instanceof ClientExceptionInterface && $e->getResponse()->getStatusCode() === 409) {
                 $this->updateStatus($message->getSiteId(), 'Success (Уже в очереди)');
                 $this->logger->info("ℹ️ Already queued: $domain");
            } else {
                $errorMsg = substr($e->getMessage(), 0, 100);
                $this->updateStatus($message->getSiteId(), "Error: $errorMsg");
                $this->logger->error("Indexing Error ($domain): " . $e->getMessage());
            }
        }
    }

    private function getYandexAccount(string $webmasterName): ?array
    {
        if (preg_match('/(\d+)/', $webmasterName, $matches)) {
            $id = $matches[1];
            // Используем конкатенацию путей более надежно
            $path = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . 'yvm' . DIRECTORY_SEPARATOR . 'yandex_webmasters.json';
            
            // Если ты положил файл просто в secrets, поправь путь выше. Я оставил как в твоем примере.
            if (!file_exists($path)) {
                 // Фолбэк на старый путь, если вдруг файл лежит не в yvm
                 $path = $this->kernel->getProjectDir() . '/config/secrets/yandex_webmasters.json';
                 if (!file_exists($path)) return null;
            }
            
            $json = json_decode(file_get_contents($path), true);
            return $json[$id] ?? null;
        }
        return null;
    }

    private function updateStatus(int $siteId, string $status): void
    {
        $em = $this->doctrine->getManager();
        
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        // Загружаем сайт заново, чтобы избежать проблем с отсоединенными сущностями
        $site = $this->siteRepo->find($siteId);
        if ($site) {
            $site->setIndexingStatus($status);
            $em->flush();
        }
    }
}
<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\VerifyYandexMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsMessageHandler]
class VerifyYandexHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private KernelInterface $kernel
    ) {}

    public function __invoke(VerifyYandexMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        // 1. Получаем данные
        $domain = $site->getArticle()?->getDomainUrl();
        $webmasterStr = $site->getWebmaster(); 
        
        $domainClean = preg_replace('#^https?://#', '', $domain ?? '');
        $domainClean = str_replace('www.', '', $domainClean);

        $cfEmail = $site->getCfEmail();
        $cfKey = $site->getCfApiKey();

        if (!$domainClean || !$webmasterStr || !$cfEmail || !$cfKey) {
            $this->updateStatus($site, 'Error: Нет данных (Domain/Webmaster/CF)');
            return;
        }

        $yandexToken = $this->getYandexToken($webmasterStr);
        if (!$yandexToken) {
            $this->updateStatus($site, "Error: Token not found for $webmasterStr");
            return;
        }

        $this->logger->info("🚀 Yandex Verify start: $domainClean");

        try {
            $yUserId = $this->getYandexUserId($yandexToken);
            if (!$yUserId) throw new \Exception("Не удалось получить User ID Яндекса");

            // 1. Добавляем сайт (С ЗАЩИТОЙ ОТ ОШИБКИ 409)
            $this->addSiteToYandex($yUserId, $yandexToken, $domainClean);
            
            // 2. Получаем UIN
            $hostId = "https:$domainClean:443";
            $uin = $this->getVerificationUin($yUserId, $yandexToken, $hostId);
            
            if (!$uin) throw new \Exception("Не получен UIN код");

            $this->logger->info("📝 UIN получен: $uin. Ставлю TXT...");

            // 3. Ставим TXT в Cloudflare
            $this->addTxtRecordToCf($domainClean, $uin, $cfEmail, $cfKey);

            // 4. Ждем
            $this->logger->info("⏳ Ждем 20 сек...");
            sleep(20);

            // 5. Подтверждаем
            $state = $this->confirmVerification($yUserId, $yandexToken, $hostId);

            if ($state === 'VERIFIED') {
                $this->updateStatus($site, 'YVM: Verified'); 
                $this->logger->info("✅ Yandex: Verified!");
            } elseif ($state === 'IN_PROGRESS') {
                $this->updateStatus($site, 'YVM: In Progress');
            } else {
                $this->updateStatus($site, "Status: $state");
            }

        } catch (\Exception $e) {
            $this->updateStatus($site, "Error: " . $e->getMessage());
        }
    }

    // --- МЕТОДЫ ---

    private function addSiteToYandex($userId, $token, $domain)
    {
        try {
            $response = $this->httpClient->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts", [
                'headers' => ['Authorization' => "OAuth $token"],
                'json' => ['host_url' => "https://$domain"]
            ]);
            
            // Принудительно читаем статус код, чтобы вызвать исключение, если оно есть
            $statusCode = $response->getStatusCode();
            
            // Если 409, мы сюда не дойдем, так как вылетит исключение. 
            // Но если вдруг HttpClient настроен иначе:
            if ($statusCode === 409) return;

        } catch (\Throwable $e) {
            // ЛОВИМ ВСЁ!
            // Проверяем, есть ли "409" в коде или сообщении
            if ($e->getCode() === 409 || strpos($e->getMessage(), '409') !== false) {
                // Это НЕ ошибка для нас. Сайт уже есть. Идем дальше.
                return; 
            }
            // Если это другая ошибка (401, 500) - выбрасываем её дальше
            throw $e;
        }
    }

    private function addTxtRecordToCf($domain, $uin, $email, $key)
    {
        // 1. Zone ID
        $res = $this->httpClient->request('GET', 'https://api.cloudflare.com/client/v4/zones', [
            'headers' => ['X-Auth-Email' => $email, 'X-Auth-Key' => $key],
            'query' => ['name' => $domain]
        ]);
        $zoneId = $res->toArray()['result'][0]['id'] ?? null;
        if (!$zoneId) throw new \Exception("CF Zone not found");

        // 2. TXT Record
        try {
            $this->httpClient->request('POST', "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records", [
                'headers' => ['X-Auth-Email' => $email, 'X-Auth-Key' => $key],
                'json' => [
                    'type' => 'TXT',
                    'name' => $domain, 
                    'content' => "yandex-verification: $uin",
                    'ttl' => 120 
                ]
            ])->getContent();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Игнорируем "Record already exists"
            if (strpos($msg, '81057') !== false || strpos($msg, 'already exists') !== false) {
                return;
            }
        }
    }

    private function getYandexToken(string $webmasterName): ?string
    {
        if (preg_match('/(\d+)/', $webmasterName, $matches)) {
            $id = $matches[1];
            $path = $this->kernel->getProjectDir() . '/config/secrets/yandex_webmasters.json';
            if (!file_exists($path)) return null;
            $json = json_decode(file_get_contents($path), true);
            return $json[$id]['token'] ?? null;
        }
        return null;
    }

    private function getYandexUserId(string $token): ?string
    {
        $res = $this->httpClient->request('GET', 'https://api.webmaster.yandex.net/v4/user', [
            'headers' => ['Authorization' => "OAuth $token"]
        ]);
        return $res->toArray()['user_id'] ?? null;
    }

    private function getVerificationUin($userId, $token, $hostId): ?string
    {
        $hostIdEnc = urlencode($hostId);
        $res = $this->httpClient->request('GET', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostIdEnc/verification", [
            'headers' => ['Authorization' => "OAuth $token"],
            'query' => ['verification_type' => 'DNS']
        ]);
        return $res->toArray()['verification_uin'] ?? null;
    }

    private function confirmVerification($userId, $token, $hostId): string
    {
        $hostIdEnc = urlencode($hostId);
        try {
            $this->httpClient->request('POST', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostIdEnc/verification", [
                'headers' => ['Authorization' => "OAuth $token"],
                'query' => ['verification_type' => 'DNS']
            ]);
        } catch (\Exception $e) {}
        
        sleep(3);

        $res = $this->httpClient->request('GET', "https://api.webmaster.yandex.net/v4/user/$userId/hosts/$hostIdEnc/verification", [
            'headers' => ['Authorization' => "OAuth $token"]
        ]);
        
        $data = $res->toArray(false);
        return $data['verification_state'] ?? 'UNKNOWN';
    }

    private function updateStatus($site, string $status)
    {
        $site->setYTxtStatus($status);
        $this->em->flush();
    }
}
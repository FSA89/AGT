<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\VerifyGscMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use phpseclib3\Net\SFTP;

#[AsMessageHandler]
class VerifyGscHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private KernelInterface $kernel,
        private ParameterBagInterface $params
    ) {}

    public function __invoke(VerifyGscMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) {
            $this->logger->error("GSC Handler: Site ID {$message->getSiteId()} not found.");
            return;
        }

        $domain = $site->getArticle()?->getDomainUrl();
        // Чистим домен
        $domainClean = preg_replace('#^https?://#', '', $domain ?? '');
        $domainClean = str_replace('www.', '', $domainClean);
        $domainClean = rtrim($domainClean, '/');

        $webmasterStr = $site->getWebmaster(); 

        if (!$domainClean || !$webmasterStr) {
            $this->updateStatus($site, 'Error: Нет домена или Вебмастера');
            return;
        }

        $googleKeyPath = $this->getGoogleKeyPath($webmasterStr);
        if (!$googleKeyPath || !file_exists($googleKeyPath)) {
            $this->updateStatus($site, "Error: Key file not found ($webmasterStr)");
            return;
        }

        $this->logger->info("🚀 GSC Verify start: $domainClean using $googleKeyPath");

        try {
            // 1. Авторизация
            $client = new \Google\Client();
            $client->setAuthConfig($googleKeyPath);
            $client->addScope(['https://www.googleapis.com/auth/webmasters', 'https://www.googleapis.com/auth/siteverification']);
            
            $searchConsole = new \Google\Service\SearchConsole($client);
            $siteVerification = new \Google\Service\SiteVerification($client);
            
            $siteUrl = "https://$domainClean/";

            // 2. Добавляем сайт (игнор ошибки 409)
            try {
                $searchConsole->sites->add($siteUrl);
            } catch (\Exception $e) {}

            // 3. Запрашиваем токен (ИСПРАВЛЕНИЕ: Создаем объект, а не массив)
            $tokenResource = new \Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequest();
            $tokenResource->setVerificationMethod('FILE');
            
            // --- FIX START ---
            $siteData = new \Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequestSite();
            $siteData->setIdentifier($siteUrl);
            $siteData->setType('SITE');
            $tokenResource->setSite($siteData);
            // --- FIX END ---
            
            $response = $siteVerification->webResource->getToken($tokenResource);
            $token = $response->getToken(); 
            
            if (!$token) throw new \Exception("Empty GSC Token response");

            $fileName = $token;
            $fileContent = "google-site-verification: $token";

            $this->logger->info("📄 Токен получен: $fileName. SFTP...");

            // 4. Загружаем файл
            $this->uploadFileSftp($domainClean, $fileName, $fileContent);

            // 5. Подтверждаем (ИСПРАВЛЕНИЕ: Тоже нужен объект)
            $this->logger->info("✅ Файл загружен. Верификация...");
            
            $verifyResource = new \Google\Service\SiteVerification\SiteVerificationWebResourceResource();
            
            // --- FIX START ---
            $verifySiteData = new \Google\Service\SiteVerification\SiteVerificationWebResourceResourceSite();
            $verifySiteData->setIdentifier($siteUrl);
            $verifySiteData->setType('SITE');
            $verifyResource->setSite($verifySiteData);
            // --- FIX END ---
            
            $siteVerification->webResource->insert('FILE', $verifyResource);

            $this->updateStatus($site, 'GSC: Verified');
            $this->logger->info("🎉 GSC Success: $domainClean");

        } catch (\Exception $e) {
            $errorMsg = substr($e->getMessage(), 0, 250); 
            $this->updateStatus($site, "Error: " . $errorMsg);
            $this->logger->error("GSC Error ($domainClean): " . $e->getMessage());
        }
    }

    private function uploadFileSftp($domain, $fileName, $content)
    {
        $host = $this->params->get('sftp.host');
        $user = $this->params->get('sftp.user');
        $pass = $this->params->get('sftp.pass');

        $sftp = new SFTP($host);
        if (!$sftp->login($user, $pass)) {
            throw new \Exception("SFTP Login failed");
        }

        $remoteDir = "/www/$domain";
        if (!$sftp->is_dir($remoteDir)) {
             if (!$sftp->mkdir($remoteDir, -1, true)) {
                 $this->logger->warning("SFTP: Could not create dir $remoteDir");
             }
        }

        $remotePath = "$remoteDir/$fileName";
        if (!$sftp->put($remotePath, $content)) {
             throw new \Exception("SFTP Upload failed");
        }
    }

    private function getGoogleKeyPath(string $webmasterName): ?string
    {
        if (preg_match('/(\d+)/', $webmasterName, $matches)) {
            $id = $matches[1];
            return $this->kernel->getProjectDir() . "/config/secrets/gsc/key_{$id}.json";
        }
        return null;
    }

    private function updateStatus($site, string $status)
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }
        $site->setGscStatus($status);
        $this->em->flush();
    }
}

// namespace App\MessageHandler\ExternalScript;

// use App\Message\ExternalScript\VerifyGscMessage;
// use App\Repository\Dashboard\SiteRepository;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\Messenger\Attribute\AsMessageHandler;
// use Psr\Log\LoggerInterface;
// use Symfony\Component\HttpKernel\KernelInterface;
// use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
// use phpseclib3\Net\SFTP; // Библиотека для SFTP

// #[AsMessageHandler]
// class VerifyGscHandler
// {
//     public function __construct(
//         private SiteRepository $siteRepo,
//         private EntityManagerInterface $em,
//         private LoggerInterface $logger,
//         private KernelInterface $kernel,
//         private ParameterBagInterface $params
//     ) {}

//     public function __invoke(VerifyGscMessage $message)
//     {
//         $site = $this->siteRepo->find($message->getSiteId());
//         if (!$site) return;

//         $domain = $site->getArticle()?->getDomainUrl();
//         $webmasterStr = $site->getWebmaster(); 
        
//         $domainClean = preg_replace('#^https?://#', '', $domain ?? '');
//         $domainClean = str_replace('www.', '', $domainClean);

//         if (!$domainClean || !$webmasterStr) {
//             $this->updateStatus($site, 'Error: Нет домена или Вебмастера');
//             return;
//         }

//         // 1. Получаем путь к ключу Google (JSON)
//         $googleKeyPath = $this->getGoogleKeyPath($webmasterStr);
//         if (!$googleKeyPath || !file_exists($googleKeyPath)) {
//             $this->updateStatus($site, "Error: Key file not found for $webmasterStr");
//             return;
//         }

//         $this->logger->info("🚀 GSC Verify start: $domainClean");

//         try {
//             // 2. Авторизация в Google
//             $client = new \Google\Client();
//             $client->setAuthConfig($googleKeyPath);
//             $client->addScope(['https://www.googleapis.com/auth/webmasters', 'https://www.googleapis.com/auth/siteverification']);
            
//             $searchConsole = new \Google\Service\SearchConsole($client);
//             $siteVerification = new \Google\Service\SiteVerification($client);
            
//             $siteUrl = "https://$domainClean/";

//             // 3. Добавляем сайт в аккаунт (игнорируем, если уже есть)
//             try {
//                 $searchConsole->sites->add($siteUrl);
//             } catch (\Exception $e) {
//                 // 409 Conflict игнорируем
//             }

//             // 4. Запрашиваем токен (файл)
//             $tokenResource = new \Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequest();
//             $tokenResource->setVerificationMethod('FILE');
//             $tokenResource->setSite(['identifier' => $siteUrl, 'type' => 'SITE']);
            
//             $response = $siteVerification->webResource->getToken($tokenResource);
//             $token = $response->getToken(); // Например: "google12345.html"
            
//             if (!$token) throw new \Exception("Не удалось получить токен GSC");

//             // Формируем контент файла
//             $fileContent = "google-site-verification: $token";
//             $fileName = $token;

//             $this->logger->info("📄 Токен получен: $fileName. Загружаю по SFTP...");

//             // 5. Загружаем файл по SFTP
//             $this->uploadFileSftp($domainClean, $fileName, $fileContent);

//             // 6. Подтверждаем верификацию
//             $this->logger->info("✅ Файл загружен. Отправляю запрос на проверку...");
            
//             $verifyResource = new \Google\Service\SiteVerification\SiteVerificationWebResourceResource();
//             $verifyResource->setSite(['identifier' => $siteUrl, 'type' => 'SITE']);
            
//             $siteVerification->webResource->insert('FILE', $verifyResource);

//             // Если не упало с ошибкой - значит успех
//             $this->updateStatus($site, 'GSC: Verified');
//             $this->logger->info("🎉 GSC Verified: $domainClean");

//         } catch (\Exception $e) {
//             $this->updateStatus($site, "Error: " . $e->getMessage());
//             $this->logger->error("GSC Error: " . $e->getMessage());
//         }
//     }

//     private function uploadFileSftp($domain, $fileName, $content)
//     {
//         $host = $this->params->get('sftp.host');
//         $user = $this->params->get('sftp.user');
//         $pass = $this->params->get('sftp.pass');

//         $sftp = new SFTP($host);
//         if (!$sftp->login($user, $pass)) {
//             throw new \Exception("SFTP Login failed");
//         }

//         // Путь на сервере: /www/domain.com/google...html
//         $remotePath = "/www/$domain/$fileName";
        
//         // Проверяем папку, если нужно (обычно /www/ уже есть, а папка домена должна быть)
//         // $sftp->mkdir("/www/$domain", -1, true); // Раскомментировать, если папки может не быть

//         if (!$sftp->put($remotePath, $content)) {
//              throw new \Exception("SFTP Upload failed to $remotePath");
//         }
//     }

//     private function getGoogleKeyPath(string $webmasterName): ?string
//     {
//         if (preg_match('/(\d+)/', $webmasterName, $matches)) {
//             $id = $matches[1];
//             // Путь: config/secrets/gsc/key_10.json
//             return $this->kernel->getProjectDir() . "/config/secrets/gsc/key_{$id}.json";
//         }
//         return null;
//     }

//     private function updateStatus($site, string $status)
//     {
//         $site->setGscStatus($status);
//         $this->em->flush();
//     }
// }
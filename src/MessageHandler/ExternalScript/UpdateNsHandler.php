<?php

// namespace App\MessageHandler\ExternalScript;

// use App\Message\ExternalScript\UpdateNsMessage;
// use App\Repository\Dashboard\SiteRepository;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\Messenger\Attribute\AsMessageHandler;
// use Psr\Log\LoggerInterface;

// #[AsMessageHandler]
// class UpdateNsHandler
// {
//     public function __construct(
//         private SiteRepository $siteRepo,
//         private EntityManagerInterface $em,
//         private LoggerInterface $logger
//     ) {}

//     public function __invoke(UpdateNsMessage $message)
//     {
//         $site = $this->siteRepo->find($message->getSiteId());
//         if (!$site) return;

//         $domain = $site->getArticle()?->getDomainUrl();
//         $registrar = $site->getRegistrar();
//         $ns1 = $site->getNs1();
//         $ns2 = $site->getNs2();

//         // Проверка данных
//         if (!$domain || !$registrar || !$ns1 || !$ns2) {
//             $this->updateStatus($site, 'Error: Нет данных (Domain/Reg/NS)');
//             return;
//         }

//         $this->logger->info("🔄 Обновление NS для $domain через $registrar ($ns1, $ns2)...");

//         try {
//             // ==================================================
//             // ТУТ БУДЕТ ЛОГИКА API (DYNADOT / NAMESILO)
//             // Мы добавим её следующим шагом.
//             // ==================================================
            
//             // Имитация бурной деятельности (задержка 2 сек)
//             sleep(2);

//             // Пока считаем, что всегда успех
//             $this->updateStatus($site, 'Success');
//             $this->logger->info("✅ NS успешно обновлены (имитация).");

//         } catch (\Exception $e) {
//             $this->updateStatus($site, "Error: " . $e->getMessage());
//         }
//     }

//     private function updateStatus($site, string $msg)
//     {
//         $site->setStatusNsUpdate($msg);
//         $this->em->flush();
//     }
// }



// namespace App\MessageHandler\ExternalScript;

// use App\Message\ExternalScript\UpdateNsMessage;
// use App\Repository\Dashboard\SiteRepository;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\Messenger\Attribute\AsMessageHandler;
// use Symfony\Contracts\HttpClient\HttpClientInterface;
// use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
// use Psr\Log\LoggerInterface;

// #[AsMessageHandler]
// class UpdateNsHandler
// {
//     public function __construct(
//         private SiteRepository $siteRepo,
//         private EntityManagerInterface $em,
//         private HttpClientInterface $httpClient,
//         private LoggerInterface $logger,
//         private ParameterBagInterface $params // Для доступа к ключам из .env
//     ) {}

//     public function __invoke(UpdateNsMessage $message)
//     {
//         $site = $this->siteRepo->find($message->getSiteId());
//         if (!$site) return;

//         $domain = $site->getArticle()?->getDomainUrl();
//         $registrar = strtolower($site->getRegistrar() ?? '');
//         $ns1 = $site->getNs1();
//         $ns2 = $site->getNs2();

//         // Очистка домена
//         $domain = preg_replace('#^https?://#', '', $domain ?? '');
//         $domain = str_replace('www.', '', $domain);

//         if (!$domain || !$registrar || !$ns1 || !$ns2) {
//             $this->updateStatus($site, 'Error: Нет данных (Domain/Reg/NS)');
//             return;
//         }

//         $this->logger->info("🔄 Обновление NS для $domain ($registrar)...");

//         try {
//             $success = false;
//             $errorMsg = '';

//             if ($registrar === 'namesilo') {
//                 [$success, $errorMsg] = $this->updateNamesilo($domain, $ns1, $ns2);
//             } 
//             elseif ($registrar === 'dynadot') {
//                 [$success, $errorMsg] = $this->updateDynadot($domain, $ns1, $ns2);
//             } 
//             else {
//                 $errorMsg = "Unknown Registrar: $registrar";
//             }

//             if ($success) {
//                 $this->updateStatus($site, 'Success');
//                 $this->logger->info("✅ NS обновлены для $domain");
//             } else {
//                 $this->updateStatus($site, $errorMsg);
//                 $this->logger->error("❌ Ошибка NS $domain: $errorMsg");
//             }

//         } catch (\Exception $e) {
//             $this->updateStatus($site, "Error: " . $e->getMessage());
//         }
//     }

//     // --- NAMESILO ---
//     private function updateNamesilo(string $domain, string $ns1, string $ns2): array
//     {
//         $apiKey = $this->params->get('registrar.namesilo_key');
//         $url = "https://www.namesilo.com/api/changeNameServers";

//         $response = $this->httpClient->request('GET', $url, [
//             'query' => [
//                 'version' => 1,
//                 'type' => 'xml',
//                 'key' => $apiKey,
//                 'domain' => $domain,
//                 'ns1' => $ns1,
//                 'ns2' => $ns2
//             ]
//         ]);

//         $content = $response->getContent();
//         // Простейший парсинг XML через SimpleXML
//         $xml = simplexml_load_string($content);
        
//         $code = (string)$xml->reply->code;
//         $detail = (string)$xml->reply->detail;

//         if ($code == '300') {
//             return [true, "Success"];
//         } else {
//             return [false, "Error (NameSilo $code): $detail"];
//         }
//     }

//     // --- DYNADOT ---
//     private function updateDynadot(string $domain, string $ns1, string $ns2): array
//     {
//         // Проверяем режим (prod или sandbox)
//         $env = $this->params->get('registrar.dynadot_env');
//         $isSandbox = ($env === 'sandbox');

//         $apiKey = $isSandbox 
//             ? $this->params->get('registrar.dynadot_sandbox_key') 
//             : $this->params->get('registrar.dynadot_key');
        
//         $baseUrl = $isSandbox 
//             ? "https://api-sandbox.dynadot.com/api3.json" 
//             : "https://api.dynadot.com/api3.json";

//         $response = $this->httpClient->request('GET', $baseUrl, [
//             'query' => [
//                 'key' => $apiKey,
//                 'command' => 'set_ns',
//                 'domain' => $domain,
//                 'ns0' => $ns1,
//                 'ns1' => $ns2
//             ]
//         ]);

//         $data = $response->toArray(false); // false = не кидать исключение при ошибках
        
//         // Логика парсинга ответа Dynadot (по аналогии с твоим python скриптом)
//         $responseBlock = $data['SetNsResponse'] ?? $data['Response'] ?? [];
//         $header = $responseBlock['ResponseHeader'] ?? $responseBlock;
        
//         $status = $header['Status'] ?? 'error';
//         $responseCode = $header['ResponseCode'] ?? $header['SuccessCode'] ?? -1;

//         if ($status === 'success' && (string)$responseCode === '0') {
//             return [true, "Success"];
//         } else {
//             $error = $header['Error'] ?? $header['Message'] ?? $status;
//             return [false, "Error (Dynadot): $error"];
//         }
//     }

//     private function updateStatus($site, string $msg)
//     {
//         $site->setStatusNsUpdate($msg);
//         $this->em->flush();
//     }
// }




namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\UpdateNsMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class UpdateNsHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ParameterBagInterface $params
    ) {}

    public function __invoke(UpdateNsMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        $domain = $site->getArticle()?->getDomainUrl();
        $registrar = strtolower($site->getRegistrar() ?? '');
        $ns1 = $site->getNs1();
        $ns2 = $site->getNs2();

        $domain = preg_replace('#^https?://#', '', $domain ?? '');
        $domain = str_replace('www.', '', $domain);

        if (!$domain || !$registrar || !$ns1 || !$ns2) {
            $this->updateStatus($site, 'Error: Нет данных (Domain/Reg/NS)');
            return;
        }

        $this->logger->info("🔄 Start NS Update for $domain ($registrar)...");

        try {
            $success = false;
            $errorMsg = '';

            if ($registrar === 'namesilo') {
                [$success, $errorMsg] = $this->updateNamesilo($domain, $ns1, $ns2);
            } 
            elseif ($registrar === 'dynadot') {
                [$success, $errorMsg] = $this->updateDynadot($domain, $ns1, $ns2);
            } 
            else {
                $errorMsg = "Unknown Registrar: $registrar";
            }

            if ($success) {
                $this->updateStatus($site, 'Success');
                $this->logger->info("✅ NS Updated for $domain");
            } else {
                $this->updateStatus($site, $errorMsg);
                $this->logger->error("❌ NS Error $domain: $errorMsg");
            }

        } catch (\Exception $e) {
            $this->updateStatus($site, "Error: " . $e->getMessage());
        }
    }

    // --- NAMESILO ---
    private function updateNamesilo(string $domain, string $ns1, string $ns2): array
    {
        $apiKey = $this->params->get('registrar.namesilo_key');
        $url = "https://www.namesilo.com/api/changeNameServers";

        $response = $this->httpClient->request('GET', $url, [
            'query' => [
                'version' => 1,
                'type' => 'xml',
                'key' => $apiKey,
                'domain' => $domain,
                'ns1' => $ns1,
                'ns2' => $ns2
            ]
        ]);

        $content = $response->getContent();
        $xml = simplexml_load_string($content);
        $code = (string)$xml->reply->code;
        $detail = (string)$xml->reply->detail;

        if ($code == '300') {
            return [true, "Success"];
        } else {
            return [false, "Error (NameSilo $code): $detail"];
        }
    }

    // --- DYNADOT (Исправленный парсер) ---
    private function updateDynadot(string $domain, string $ns1, string $ns2): array
    {
        $env = $this->params->get('registrar.dynadot_env');
        $isSandbox = ($env === 'sandbox');

        $apiKey = $isSandbox 
            ? $this->params->get('registrar.dynadot_sandbox_key') 
            : $this->params->get('registrar.dynadot_key');
        
        $baseUrl = $isSandbox 
            ? "https://api-sandbox.dynadot.com/api3.json" 
            : "https://api.dynadot.com/api3.json";

        $this->logger->info("Dynadot Req: set_ns for $domain");

        $response = $this->httpClient->request('GET', $baseUrl, [
            'query' => [
                'key' => $apiKey,
                'command' => 'set_ns',
                'domain' => $domain,
                'ns0' => $ns1,
                'ns1' => $ns2
            ]
        ]);

        $data = $response->toArray(false);
        
        // 1. Проверяем простую ошибку верхнего уровня (частый случай для -1)
        if (isset($data['Response']['Error'])) {
            return [false, "Error (Dynadot): " . $data['Response']['Error']];
        }

        // 2. Проверяем вложенные структуры (успех или сложные ошибки)
        $header = null;
        if (isset($data['SetNsResponse']['ResponseHeader'])) {
            $header = $data['SetNsResponse']['ResponseHeader'];
        } elseif (isset($data['Response']['ResponseHeader'])) {
            $header = $data['Response']['ResponseHeader'];
        }

        if ($header) {
            $status = $header['Status'] ?? 'error';
            $responseCode = $header['ResponseCode'] ?? $header['SuccessCode'] ?? -1;
            
            if ($status === 'success' && (string)$responseCode === '0') {
                return [true, "Success"];
            } else {
                $error = $header['Error'] ?? $header['Message'] ?? $status;
                return [false, "Error (Dynadot): $error"];
            }
        }

        // 3. Если ничего не поняли
        return [false, "Error (Dynadot): Unknown response format " . json_encode($data)];
    }

    private function updateStatus($site, string $msg)
    {
        if (strlen($msg) > 250) $msg = substr($msg, 0, 247) . '...';
        $site->setStatusNsUpdate($msg);
        $this->em->flush();
    }
}
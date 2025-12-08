<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\CheckNsMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CheckNsHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CheckNsMessage $message)
    {
        $site = $this->siteRepo->find($message->getSiteId());
        if (!$site) return;

        $domain = $site->getArticle()?->getDomainUrl();
        // Чистим домен
        $domain = preg_replace('#^https?://#', '', $domain ?? '');
        $domain = str_replace('www.', '', $domain);

        if (!$domain) {
            $this->updateStatus($site, 'Error: Нет домена');
            return;
        }

        // Целевые NS (которые выдал Cloudflare)
        $targetNs1 = $site->getNs1();
        $targetNs2 = $site->getNs2();

        if (!$targetNs1 || !$targetNs2) {
            $this->updateStatus($site, 'Error: Нет NS1/NS2 в базе');
            return;
        }

        $this->logger->info("🔍 Проверка NS для $domain...");

        try {
            // Получаем реальные NS записи из интернета
            // dns_get_record возвращает массив массивов
            $records = dns_get_record($domain, DNS_NS);
            
            $liveNs = [];
            foreach ($records as $r) {
                if (isset($r['target'])) {
                    $liveNs[] = strtolower($r['target']);
                }
            }

            if (empty($liveNs)) {
                $this->updateStatus($site, 'NS не найдены (NXDOMAIN)');
                return;
            }

            // Сравниваем
            // Приводим всё к нижнему регистру для сравнения
            $targetNs1 = strtolower($targetNs1);
            $targetNs2 = strtolower($targetNs2);

            $found1 = false;
            $found2 = false;

            foreach ($liveNs as $live) {
                if ($live === $targetNs1) $found1 = true;
                if ($live === $targetNs2) $found2 = true;
            }

            if ($found1 && $found2) {
                $this->updateStatus($site, 'NS Correct');
                $this->logger->info("✅ NS Correct для $domain");
            } else {
                // Показываем, какие нашли (для отладки)
                $firstLive = $liveNs[0] ?? 'none';
                $this->updateStatus($site, "Mismatch: $firstLive...");
                $this->logger->warning("⚠️ NS Mismatch: Ожидали $targetNs1, нашли " . implode(', ', $liveNs));
            }

        } catch (\Exception $e) {
            $this->updateStatus($site, "Error: " . $e->getMessage());
        }
    }

    private function updateStatus($site, string $msg)
    {
        $site->setNsStatus($msg);
        $this->em->flush();
    }
}
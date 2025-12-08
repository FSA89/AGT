<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\CheckSiteMessage;
use App\Repository\Dashboard\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CheckSiteHandler
{
    public function __construct(
        private SiteRepository $siteRepo,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CheckSiteMessage $message)
    {
        $siteId = $message->getSiteId();
        
        $site = $this->siteRepo->find($siteId);
        if (!$site) {
            return;
        }

        $article = $site->getArticle();
        if (!$article || !$article->getDomainUrl()) {
            $this->updateStatus($site, 'Error: Нет домена');
            return;
        }

        $domain = $article->getDomainUrl();
        if (!str_starts_with($domain, 'http')) {
            $domain = 'https://' . $domain;
        }

        $this->logger->info("🔍 Checking site: $domain (ID: $siteId)");

        try {
            $response = $this->httpClient->request('GET', $domain, [
                'timeout' => 15,
                'verify_peer' => false, 
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 400) {
                $this->updateStatus($site, '200 OK');
            } else {
                $this->updateStatus($site, "Error: Status $statusCode");
            }

        } catch (\Exception $e) {
            $this->updateStatus($site, 'Error: Недоступен');
            $this->logger->error("Site check failed: " . $e->getMessage());
        }
    }

    private function updateStatus($site, string $status): void
    {
        $site->setSiteStatus($status);
        $this->em->flush();
    }
}
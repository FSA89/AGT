<?php

namespace App\MessageHandler\ExternalScript;

use App\Message\ExternalScript\ParseStructureMessage;
use App\Repository\Dashboard\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class ParseStructureHandler
{
    public function __construct(
        private TaskRepository $taskRepo,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function __invoke(ParseStructureMessage $message)
    {
        $task = $this->taskRepo->find($message->getTaskId());
        if (!$task) return;

        $urlsText = $task->getCompetitorUrls();
        if (!$urlsText) {
            $task->setCompetitorStructures("Error: Нет URL для парсинга");
            $this->em->flush();
            return;
        }

        $urls = preg_split('/[\s,]+/', $urlsText, -1, PREG_SPLIT_NO_EMPTY);
        
        $finalOutput = [];

        foreach ($urls as $rawUrl) {
            // Очистка URL
            $url = rtrim($rawUrl, ".,;…");
            while (str_ends_with($url, '...')) {
                $url = substr($url, 0, -3);
            }
            if (empty($url)) continue;
            if (!str_starts_with($url, 'http')) $url = 'https://' . $url;

            try {
                $this->logger->info("Parsing: $url");
                
                $response = $this->httpClient->request('GET', $url, [
                    'timeout' => 15,
                    'verify_peer' => false,
                    'max_redirects' => 5,
                    'headers' => ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36']
                ]);

                if ($response->getStatusCode() !== 200) {
                    // Если ошибка, пишем просто статус, без URL
                    $finalOutput[] = "Error: HTTP " . $response->getStatusCode();
                    continue;
                }

                $html = $response->getContent();
                $crawler = new Crawler($html);
                $structure = [];

                $crawler->filter('h1, h2, h3')->each(function (Crawler $node) use (&$structure) {
                    $tag = strtoupper($node->nodeName());
                    $text = trim(preg_replace('/\s+/', ' ', $node->text()));
                    if ($text) {
                        $structure[] = "$tag: $text";
                    }
                });

                if (empty($structure)) {
                    $finalOutput[] = "(Заголовки не найдены)";
                } else {
                    // Просто список заголовков, без шапки с доменом
                    $finalOutput[] = implode("\n", $structure);
                }

            } catch (\Exception $e) {
                $msg = $e->getMessage();
                if (strlen($msg) > 50) $msg = substr($msg, 0, 50) . '...';
                $finalOutput[] = "Error: $msg";
            }
        }

        // Соединяем результаты разных сайтов через двойной перенос строки (интервал)
        $task->setCompetitorStructures(implode("\n\n", $finalOutput));
        $this->em->flush();
        
        $this->logger->info("Parsing done for Task " . $task->getId());
    }
}
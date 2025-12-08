<?php

namespace App\Controller\Dashboard;

use App\Entity\Dashboard\Article;
use App\Repository\Dashboard\ArticleRepository;
use App\Repository\Dashboard\TaskRepository;
use App\Repository\Dashboard\NeuroRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'dashboard_articles')]
    public function articles(
        ArticleRepository $articleRepo,
        TaskRepository $taskRepo,
        NeuroRepository $neuroRepo
    ): Response
    {
        $articles = $articleRepo->findBy([], ['id' => 'DESC']);

        $neuroMap = [];
        foreach ($neuroRepo->findAll() as $n) {
            $neuroMap[$n->getId()] = $n->getModelName();
        }

        $tableData = [];
        foreach ($articles as $art) {
            $task = $art->getTask();
            $schemeText = $art->getSchemeNeuroSnapshot();

            if (empty($schemeText) && $task && $task->getSchemeNeuro()) {
                $rawIds = $task->getSchemeNeuro()->getSchemeNeuroFull(); 
                if ($rawIds) {
                    $ids = explode('+', $rawIds);
                    $names = array_map(fn($id) => $neuroMap[trim($id)] ?? $id, $ids);
                    $schemeText = implode(' + ', $names);
                }
            }

            $tableData[] = [
                'id' => $art->getId(),
                'task_custom_id' => $art->getTaskCustomId(),
                'task_id' => $task?->getId(),
                'task_query' => $task?->getQuery(),
                'scheme_neuro_snapshot' => $schemeText, 
                'title' => $art->getTitle(),
                'description' => $art->getDescription(),
                'content' => $art->getContent(),
                'rating' => $art->getRating(),
                'status' => $art->getStatus(),
                'domain_url' => $art->getDomainUrl(),
            ];
        }

        $tasksList = [];
        foreach ($taskRepo->findBy([], ['id' => 'DESC'], 500) as $t) {
            $tasksList[$t->getId()] = $t->getId() . ': ' . $t->getMainKeyword();
        }

        return $this->render('dashboard/articles.html.twig', [
            'articles_json' => json_encode($tableData),
            'dict_tasks' => json_encode($tasksList),
        ]);
    }

    #[Route('/api/articles/save', name: 'api_articles_save', methods: ['POST'])]
    public function saveArticles(
        Request $request, 
        EntityManagerInterface $em,
        ArticleRepository $articleRepo,
        TaskRepository $taskRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $article = $articleRepo->find($row['id']);
            } else {
                $article = new Article();
                $em->persist($article);
            }

            if ($article) {
                $article->setTaskCustomId($row['task_custom_id'] ?? null);
                $article->setTitle($row['title'] ?? null);
                $article->setDescription($row['description'] ?? null);
                $article->setContent($row['content'] ?? null);
                $article->setRating(isset($row['rating']) ? (int)$row['rating'] : null);
                $article->setStatus($row['status'] ?? 'ready');
                $article->setDomainUrl($row['domain_url'] ?? null);
                $article->setSchemeNeuroSnapshot($row['scheme_neuro_snapshot'] ?? null);

                $taskId = isset($row['task_id']) ? (int)$row['task_id'] : 0;
                $article->setTask($taskId > 0 ? $taskRepo->find($taskId) : null);

                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено статей: $count"]);
    }

    #[Route('/api/articles/delete', name: 'api_articles_delete', methods: ['POST'])]
    public function deleteArticle(Request $request, ArticleRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id']) && $item = $repo->find($data['id'])) {
            $em->remove($item);
            $em->flush();
            return $this->json(['status' => 'success']);
        }
        return $this->json(['status' => 'error']);
    }
}
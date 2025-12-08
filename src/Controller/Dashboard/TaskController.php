<?php

namespace App\Controller\Dashboard;

use App\Entity\Dashboard\Task;
use App\Repository\Dashboard\TaskRepository;
use App\Repository\Dashboard\PageTypeRepository;
use App\Repository\Dashboard\HreflangRepository;
use App\Repository\Dashboard\SchemeNeuroRepository;
use App\Repository\Dashboard\NeuroRepository;
use App\Message\ExternalScript\ParseStructureMessage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/dashboard')] // Общий префикс для рендера, API маршруты ниже переопределяют путь
class TaskController extends AbstractController
{
    #[Route('/tasks', name: 'dashboard_tasks')]
    public function tasks(
        TaskRepository $taskRepo,
        PageTypeRepository $pageTypeRepo,
        HreflangRepository $hreflangRepo,
        SchemeNeuroRepository $schemeRepo,
        NeuroRepository $neuroRepo
    ): Response
    {
        $tasks = $taskRepo->findBy([], ['id' => 'DESC']);
        $tableData = [];
        foreach ($tasks as $task) {
            $tableData[] = [
                'id' => $task->getId(),
                'main_keyword' => $task->getMainKeyword(),
                'page_type_id' => $task->getPageType()?->getId(),
                'keywords' => $task->getKeywords(),
                'competitor_urls' => $task->getCompetitorUrls(),
                'competitor_structures' => $task->getCompetitorStructures(),
                'count' => $task->getCount(),
                'count_done' => $task->getCountDone(),
                'status' => $task->getStatus(),
                'query' => $task->getQuery(),
                'hreflang_id' => $task->getHreflang()?->getId(),
                'scheme_neuro_id' => $task->getSchemeNeuro()?->getId(),
            ];
        }

        $pageTypes = $this->formatDict($pageTypeRepo->findAll(), 'typeName');
        $hreflangs = $this->formatDict($hreflangRepo->findAll(), 'code');

        $neuroNames = [];
        foreach ($neuroRepo->findAll() as $n) $neuroNames[$n->getId()] = $n->getModelName();

        $schemes = [];
        foreach ($schemeRepo->findAll() as $sc) {
            $rawIds = $sc->getSchemeNeuro(); 
            $readableName = 'Без названия';
            if ($rawIds) {
                $ids = explode('+', $rawIds);
                $names = array_map(fn($id) => $neuroNames[trim($id)] ?? $id, $ids);
                $readableName = implode(' + ', $names);
            }
            $schemes[] = [
                'id' => $sc->getId(),
                'name' => $readableName,
                'active' => $sc->isActive()
            ];
        }

        return $this->render('dashboard/tasks.html.twig', [
            'tasks_json' => json_encode($tableData),
            'dict_page_types' => json_encode($pageTypes),
            'dict_hreflangs' => json_encode($hreflangs),
            'dict_schemes' => json_encode($schemes),
        ]);
    }

    #[Route('/api/tasks/save', name: 'api_tasks_save', methods: ['POST'])]
    public function saveTasks(
        Request $request, 
        EntityManagerInterface $em,
        TaskRepository $taskRepo,
        PageTypeRepository $pageTypeRepo,
        HreflangRepository $hreflangRepo,
        SchemeNeuroRepository $schemeRepo,
        MessageBusInterface $bus
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        $dispatchedTasks = 0;

        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $task = $taskRepo->find($row['id']);
            } else {
                $task = new Task();
                $em->persist($task);
            }

            if ($task) {
                $task->setMainKeyword($row['main_keyword'] ?? null);
                $task->setKeywords($row['keywords'] ?? null);
                $task->setCompetitorUrls($row['competitor_urls'] ?? null);
                $task->setCompetitorStructures($row['competitor_structures'] ?? null);
                $task->setCount((int)($row['count'] ?? 0));
                $task->setCountDone((int)($row['count_done'] ?? 0));
                $task->setQuery($row['query'] ?? null);
                $task->setStatus($row['status'] ?? 'generate');

                if (isset($row['_command']) && $row['_command'] === 'parse_structure') {
                    $bus->dispatch(new ParseStructureMessage($task->getId()));
                    $dispatchedTasks++;
                }

                $ptId = isset($row['page_type_id']) ? (int)$row['page_type_id'] : 0;
                $task->setPageType($ptId > 0 ? $pageTypeRepo->find($ptId) : null);

                $hlId = isset($row['hreflang_id']) ? (int)$row['hreflang_id'] : 0;
                $task->setHreflang($hlId > 0 ? $hreflangRepo->find($hlId) : null);

                $scId = isset($row['scheme_neuro_id']) ? (int)$row['scheme_neuro_id'] : 0;
                $task->setSchemeNeuro($scId > 0 ? $schemeRepo->find($scId) : null);

                $count++;
            }
        }

        $em->flush();
        $msg = "Сохранено: $count";
        if ($dispatchedTasks > 0) $msg .= ". Запущен парсинг: $dispatchedTasks";

        return $this->json(['status' => 'success', 'message' => $msg]);
    }
    
    #[Route('/api/task/{id}/status', name: 'api_task_get_status', methods: ['GET'])]
    public function getTaskStatus(int $id, TaskRepository $taskRepo): JsonResponse
    {
        $task = $taskRepo->find($id);
        if (!$task) return $this->json(['status' => 'deleted']);
        
        return $this->json([
            'status' => $task->getStatus(),
            'competitor_structures' => $task->getCompetitorStructures()
        ]);
    }

    #[Route('/api/tasks/delete', name: 'api_tasks_delete', methods: ['POST'])]
    public function deleteTask(Request $request, TaskRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id']) && $task = $repo->find($data['id'])) {
            $em->remove($task);
            $em->flush();
            return $this->json(['status' => 'success']);
        }
        return $this->json(['status' => 'error']);
    }

    // Вспомогательный метод (приватный для этого контроллера)
    private function formatDict($items, $nameField) {
        $result = [];
        foreach ($items as $item) {
            $getter = 'get' . ucfirst($nameField);
            $result[] = [
                'id' => $item->getId(),
                'name' => $item->$getter(),
                'active' => $item->isActive()
            ];
        }
        return $result;
    }
}
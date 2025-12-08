<?php

namespace App\Controller;

use App\Entity\Dashboard\Hreflang;
use App\Repository\Dashboard\HreflangRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/dictionary')]
class DictionaryController extends AbstractController
{
    // =========================================================================
    // == ГЛАВНАЯ (РЕДИРЕКТ)
    // =========================================================================
    
    #[Route('', name: 'dictionary_index')]
    public function index(): Response
    {
        // При заходе в /dictionary перекидываем на первую вкладку (Hreflang)
        return $this->redirectToRoute('dictionary_hreflang');
    }

    // =========================================================================
    // == HREFLANG
    // =========================================================================

    #[Route('/hreflang', name: 'dictionary_hreflang')]
    public function hreflang(HreflangRepository $repo): Response
    {
        // Получаем все записи, сортируем по коду
        $items = $repo->findBy([], ['code' => 'ASC']);

        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                'id' => $item->getId(),
                'code' => $item->getCode(),
                'is_active' => $item->isActive(),
            ];
        }

        return $this->render('dashboard/dictionary/hreflang.html.twig', [
            'table_json' => json_encode($tableData),
        ]);
    }

    #[Route('/hreflang/save', name: 'api_hreflang_save', methods: ['POST'])]
    public function saveHreflang(Request $request, EntityManagerInterface $em, HreflangRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $repo->find($row['id']);
            } else {
                $item = new Hreflang();
                $em->persist($item);
            }

            if ($item) {
                $item->setCode($row['code'] ?? '');
                $item->setIsActive((bool)($row['is_active'] ?? true));
                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено записей: $count"]);
    }

    #[Route('/hreflang/delete', name: 'api_hreflang_delete', methods: ['POST'])]
    public function deleteHreflang(Request $request, HreflangRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                $em->remove($item);
                $em->flush();
                return $this->json(['status' => 'success']);
            }
        }
        return $this->json(['status' => 'error']);
    }



    // =========================================================================
    // == PAGE TYPE (ТИПЫ СТРАНИЦ)
    // =========================================================================

    #[Route('/page-type', name: 'dictionary_page_type')]
    public function pageType(\App\Repository\Dashboard\PageTypeRepository $repo): Response
    {
        $items = $repo->findBy([], ['type_name' => 'ASC']);

        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                'id' => $item->getId(),
                'type_name' => $item->getTypeName(),
                'is_active' => $item->isActive(),
            ];
        }

        return $this->render('dashboard/dictionary/page_type.html.twig', [
            'table_json' => json_encode($tableData),
        ]);
    }

    #[Route('/page-type/save', name: 'api_page_type_save', methods: ['POST'])]
    public function savePageType(Request $request, EntityManagerInterface $em, \App\Repository\Dashboard\PageTypeRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $repo->find($row['id']);
            } else {
                $item = new \App\Entity\Dashboard\PageType();
                $em->persist($item);
            }

            if ($item) {
                $item->setTypeName($row['type_name'] ?? '');
                $item->setIsActive((bool)($row['is_active'] ?? true));
                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено записей: $count"]);
    }

    #[Route('/page-type/delete', name: 'api_page_type_delete', methods: ['POST'])]
    public function deletePageType(Request $request, \App\Repository\Dashboard\PageTypeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                $em->remove($item);
                $em->flush();
                return $this->json(['status' => 'success']);
            }
        }
        return $this->json(['status' => 'error']);
    }



    // =========================================================================
    // == NEURO (НЕЙРОСЕТИ)
    // =========================================================================

    #[Route('/neuro', name: 'dictionary_neuro')]
    public function neuro(\App\Repository\Dashboard\NeuroRepository $repo): Response
    {
        // Сортируем по ID DESC, чтобы новые были сверху, или по имени
        $items = $repo->findBy([], ['id' => 'DESC']);

        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                'id' => $item->getId(),
                'model_name' => $item->getModelName(),
                'is_active' => $item->isActive(),
            ];
        }

        return $this->render('dashboard/dictionary/neuro.html.twig', [
            'table_json' => json_encode($tableData),
        ]);
    }

    #[Route('/neuro/save', name: 'api_neuro_save', methods: ['POST'])]
    public function saveNeuro(Request $request, EntityManagerInterface $em, \App\Repository\Dashboard\NeuroRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $repo->find($row['id']);
            } else {
                $item = new \App\Entity\Dashboard\Neuro();
                $em->persist($item);
            }

            if ($item) {
                $item->setModelName($row['model_name'] ?? '');
                $item->setIsActive((bool)($row['is_active'] ?? true));
                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено моделей: $count"]);
    }

    #[Route('/neuro/delete', name: 'api_neuro_delete', methods: ['POST'])]
    public function deleteNeuro(Request $request, \App\Repository\Dashboard\NeuroRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                try {
                    $em->remove($item);
                    $em->flush();
                    return $this->json(['status' => 'success']);
                } catch (\Exception $e) {
                    // Нейросеть может использоваться в схемах (SchemeNeuro), удаление запрещено FK
                    return $this->json(['status' => 'error', 'message' => 'Нельзя удалить: модель используется в схемах!']);
                }
            }
        }
        return $this->json(['status' => 'error']);
    }

    // =========================================================================
    // == SCHEME NEURO (СХЕМЫ НЕЙРОСЕТЕЙ)
    // =========================================================================

    #[Route('/scheme', name: 'dictionary_scheme')]
    public function scheme(
        \App\Repository\Dashboard\SchemeNeuroRepository $schemeRepo,
        \App\Repository\Dashboard\NeuroRepository $neuroRepo
    ): Response
    {
        // 1. Данные схем
        $items = $schemeRepo->findBy([], ['id' => 'DESC']);

        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                'id' => $item->getId(),
                // Текстовые поля
                'scheme_neuro' => $item->getSchemeNeuro(),
                'scheme_neuro_full' => $item->getSchemeNeuroFull(),
                'is_active' => $item->isActive(),
                
                // Связи (храним ID)
                'structure_analyzer_id' => $item->getStructureAnalyzer()?->getId(),
                'meta_header_generator_id' => $item->getMetaHeaderGenerator()?->getId(),
                'writer_id' => $item->getWriter()?->getId(),
                'meta_corrector_id' => $item->getMetaCorrector()?->getId(),
                'text_corrector_id' => $item->getTextCorrector()?->getId(),
            ];
        }

        // 2. Справочник Нейросетей для выпадающих списков
        $neuroList = [];
        foreach ($neuroRepo->findAll() as $n) {
            $neuroList[$n->getId()] = $n->getModelName();
        }

        return $this->render('dashboard/dictionary/scheme_neuro.html.twig', [
            'table_json' => json_encode($tableData),
            'dict_neuro' => json_encode($neuroList), // Передаем список моделей
        ]);
    }

    #[Route('/scheme/save', name: 'api_scheme_save', methods: ['POST'])]
    public function saveScheme(
        Request $request, 
        EntityManagerInterface $em, 
        \App\Repository\Dashboard\SchemeNeuroRepository $schemeRepo,
        \App\Repository\Dashboard\NeuroRepository $neuroRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $schemeRepo->find($row['id']);
            } else {
                $item = new \App\Entity\Dashboard\SchemeNeuro();
                $em->persist($item);
            }

            if ($item) {
                // --- ЛОГИКА КОНКАТЕНАЦИИ (МАССИВ -> СТРОКА) ---
                
                // Поле 1: Короткое название/схема
                $shortScheme = $row['scheme_neuro'] ?? '';
                if (is_array($shortScheme)) {
                    $shortScheme = implode('+', $shortScheme);
                }
                $item->setSchemeNeuro($shortScheme);

                // Поле 2: Полная цепочка
                $fullScheme = $row['scheme_neuro_full'] ?? '';
                if (is_array($fullScheme)) {
                    $fullScheme = implode('+', $fullScheme);
                }
                $item->setSchemeNeuroFull($fullScheme);
                $item->setIsActive((bool)($row['is_active'] ?? true));

                // --- СВЯЗИ (ОДИНОЧНЫЕ) ---
                $setRel = function($field, $setter) use ($row, $item, $neuroRepo) {
                    $id = isset($row[$field]) ? (int)$row[$field] : 0;
                    $entity = $id > 0 ? $neuroRepo->find($id) : null;
                    $item->$setter($entity);
                };

                $setRel('structure_analyzer_id', 'setStructureAnalyzer');
                $setRel('meta_header_generator_id', 'setMetaHeaderGenerator');
                $setRel('writer_id', 'setWriter');
                $setRel('meta_corrector_id', 'setMetaCorrector');
                $setRel('text_corrector_id', 'setTextCorrector');

                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено схем: $count"]);
    }
    
    #[Route('/scheme/delete', name: 'api_scheme_delete', methods: ['POST'])]
    public function deleteScheme(Request $request, \App\Repository\Dashboard\SchemeNeuroRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                $em->remove($item);
                $em->flush();
                return $this->json(['status' => 'success']);
            }
        }
        return $this->json(['status' => 'error']);
    }


    // =========================================================================
    // == TEMPLATES (ШАБЛОНЫ)
    // =========================================================================

    #[Route('/template', name: 'dictionary_template')]
    public function template(\App\Repository\Dashboard\TemplateRepository $repo): Response
    {
        $items = $repo->findBy([], ['id' => 'DESC']);

        $tableData = [];
        foreach ($items as $item) {
            // Превращаем массив в красивую JSON-строку для редактирования в textarea
            // JSON_UNESCAPED_UNICODE нужен, чтобы кириллица не превращалась в \u041...
            $jsonStr = json_encode($item->getJsonTemplate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            $tableData[] = [
                'id' => $item->getId(),
                'template_name' => $item->getTemplateName(),
                'server_name' => $item->getServerName(),
                'button_url' => $item->getButtonUrl(),
                // Если null, отдаем пустую строку
                'json_template' => ($jsonStr === 'null') ? '' : $jsonStr,
                'is_active' => $item->isActive(),
            ];
        }

        return $this->render('dashboard/dictionary/template.html.twig', [
            'table_json' => json_encode($tableData),
        ]);
    }

    #[Route('/template/save', name: 'api_template_save', methods: ['POST'])]
    public function saveTemplate(Request $request, EntityManagerInterface $em, \App\Repository\Dashboard\TemplateRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $repo->find($row['id']);
            } else {
                $item = new \App\Entity\Dashboard\Template();
                $em->persist($item);
            }

            if ($item) {
                $item->setTemplateName($row['template_name'] ?? '');
                $item->setServerName($row['server_name'] ?? '');
                $item->setButtonUrl($row['button_url'] ?? '');
                $item->setIsActive((bool)($row['is_active'] ?? true));
                
                // Обратное преобразование: Текст -> JSON Array
                $jsonRaw = $row['json_template'] ?? '[]';
                $jsonArr = json_decode($jsonRaw, true);
                
                // Валидация: если юзер ввел невалидный JSON, запишем пустой массив, чтобы не сломать сайт
                if (!is_array($jsonArr)) {
                    $jsonArr = []; 
                }
                
                $item->setJsonTemplate($jsonArr);
                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено шаблонов: $count"]);
    }

    #[Route('/template/delete', name: 'api_template_delete', methods: ['POST'])]
    public function deleteTemplate(Request $request, \App\Repository\Dashboard\TemplateRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                try {
                    $em->remove($item);
                    $em->flush();
                    return $this->json(['status' => 'success']);
                } catch (\Exception $e) {
                    // Шаблон может использоваться в Sites, удаление запрещено FK
                    return $this->json(['status' => 'error', 'message' => 'Нельзя удалить: шаблон используется на сайтах!']);
                }
            }
        }
        return $this->json(['status' => 'error']);
    }

    // =========================================================================
    // == CLOUDFLARE ACCOUNTS
    // =========================================================================

    #[Route('/cloudflare', name: 'dictionary_cloudflare')]
    public function cloudflare(\App\Repository\Dashboard\CloudflareAccountRepository $repo): Response
    {
        $items = $repo->findBy([], ['id' => 'DESC']);

        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                'id' => $item->getId(),
                'cf_email' => $item->getCfEmail(),
                'cf_pass' => $item->getCfPass(),
                'cf_api_key' => $item->getCfApiKey(),
                'status' => $item->getStatus(), // int (0, 1...)
                'is_active' => $item->isActive(),
            ];
        }

        return $this->render('dashboard/dictionary/cloudflare.html.twig', [
            'table_json' => json_encode($tableData),
        ]);
    }

    #[Route('/cloudflare/save', name: 'api_cloudflare_save', methods: ['POST'])]
    public function saveCloudflare(Request $request, EntityManagerInterface $em, \App\Repository\Dashboard\CloudflareAccountRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $item = $repo->find($row['id']);
            } else {
                $item = new \App\Entity\Dashboard\CloudflareAccount();
                $em->persist($item);
            }

            if ($item) {
                $item->setCfEmail($row['cf_email'] ?? '');
                $item->setCfPass($row['cf_pass'] ?? '');
                $item->setCfApiKey($row['cf_api_key'] ?? '');
                $item->setStatus((int)($row['status'] ?? 0));
                $item->setIsActive((bool)($row['is_active'] ?? true));
                $count++;
            }
        }

        $em->flush();
        return $this->json(['status' => 'success', 'message' => "Сохранено аккаунтов: $count"]);
    }

    #[Route('/cloudflare/delete', name: 'api_cloudflare_delete', methods: ['POST'])]
    public function deleteCloudflare(Request $request, \App\Repository\Dashboard\CloudflareAccountRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id'])) {
            $item = $repo->find($data['id']);
            if ($item) {
                try {
                    $em->remove($item);
                    $em->flush();
                    return $this->json(['status' => 'success']);
                } catch (\Exception $e) {
                    // Аккаунт используется в Sites
                    return $this->json(['status' => 'error', 'message' => 'Нельзя удалить: аккаунт используется на сайтах!']);
                }
            }
        }
        return $this->json(['status' => 'error']);
    }
}
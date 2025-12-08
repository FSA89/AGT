<?php

namespace App\Controller\Dashboard;

use App\Entity\Dashboard\Site;
use App\Repository\Dashboard\SiteRepository;
use App\Repository\Dashboard\TemplateRepository;
use App\Repository\Dashboard\CloudflareAccountRepository;
use App\Repository\Dashboard\ArticleRepository;

use App\Message\ExternalScript\CheckSiteMessage;
use App\Message\ExternalScript\AddCloudflareMessage;
use App\Message\ExternalScript\CheckNsMessage;
use App\Message\ExternalScript\UpdateNsMessage;
use App\Message\ExternalScript\VerifyYandexMessage;
use App\Message\ExternalScript\VerifyGscMessage;
use App\Message\ExternalScript\SetupProxyMessage;
use App\Message\ExternalScript\SendIndexMessage;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;


#[Route('/dashboard')]
class SiteController extends AbstractController
{
    #[Route('/sites', name: 'dashboard_sites')]
    public function sites(
        SiteRepository $siteRepo,
        TemplateRepository $templateRepo,
        CloudflareAccountRepository $cfRepo,
        ArticleRepository $articleRepo
    ): Response
    {
        $sites = $siteRepo->findBy([], ['id' => 'DESC']);
        
        $tableData = [];
        foreach ($sites as $site) {
            $article = $site->getArticle();
            $query  = ($article && $article->getTask()) ? $article->getTask()->getQuery() : '';

            $tableData[] = [
                'id' => $site->getId(),
                'article_id' => $article?->getId(), 
                'main_query' => $query,
                'registrar' => $site->getRegistrar(),
                'status_registration' => $site->getStatusRegistration(),
                'webmaster' => $site->getWebmaster(),
                'y_txt_status' => $site->getYTxtStatus(),
                'indexing_status' => $site->getIndexingStatus(),
                'cf_account_id' => $site->getCloudflareAccount()?->getId(),
                'cf_api_key' => $site->getCfApiKey(),
                'status_cf' => $site->getStatusCf(),
                'status_ns_update' => $site->getStatusNsUpdate(),
                'ns1' => $site->getNs1(),
                'ns2' => $site->getNs2(),
                'ns_status' => $site->getNsStatus(),
                'status_proxy' => $site->getStatusProxy(),
                'template_id' => $site->getTemplate()?->getId(),
                'full_cycle_status' => $site->getFullCycleStatus(),
                'upload_status' => $site->getUploadStatus(),
                'publish_date' => $site->getPublishDate() ? $site->getPublishDate()->format('Y-m-d') : null,
                'site_status' => $site->getSiteStatus(),
            ];
        }

        $templates = $this->formatDict($templateRepo->findAll(), 'templateName');
        $cfAccounts = [];
        $cfKeys = []; 
        foreach ($cfRepo->findAll() as $cf) {
            $cfAccounts[] = [
                'id' => $cf->getId(),
                'name' => $cf->getCfEmail(),
                'active' => $cf->isActive()
            ];
            $cfKeys[$cf->getId()] = $cf->getCfApiKey();
        }

        $webmasters = [];
        for ($i = 1; $i <= 20; $i++) {
            $key = "Вебмастер $i";
            $webmasters[$key] = $key;
        }

        $articlesList = [];
        $articlesQueries = [];
        foreach ($articleRepo->findBy([], ['id'=>'DESC']) as $art) {
            $d = $art->getDomainUrl() ?: 'Без домена (ID: '.$art->getId().')';
            $articlesList[$art->getId()] = $d;
            $articlesQueries[$art->getId()] = $art->getTask()?->getQuery() ?: '';
        }

        return $this->render('dashboard/sites.html.twig', [
            'sites_json' => json_encode($tableData),
            'dict_templates' => json_encode($templates),
            'dict_cf' => json_encode($cfAccounts),
            'dict_cf_keys' => json_encode($cfKeys),
            'dict_webmasters' => json_encode($webmasters),
            'dict_articles' => json_encode($articlesList),
            'dict_articles_queries' => json_encode($articlesQueries),
        ]);
    }

    #[Route('/api/sites/save', name: 'api_sites_save', methods: ['POST'])]
    public function saveSites(
        Request $request, 
        EntityManagerInterface $em,
        SiteRepository $siteRepo,
        TemplateRepository $templateRepo,
        CloudflareAccountRepository $cfRepo,
        ArticleRepository $articleRepo,
        MessageBusInterface $bus 
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['status' => 'error', 'message' => 'No data']);

        $count = 0;
        $dispatchedTasks = 0;

        foreach ($data as $row) {
            if (!empty($row['id'])) {
                $site = $siteRepo->find($row['id']);
            } else {
                $site = new Site();
                $em->persist($site);
            }

            if ($site) {
                // --- Текстовые поля ---
                $site->setRegistrar($row['registrar'] ?? null);
                $site->setWebmaster($row['webmaster'] ?? null);
                $site->setNs1($row['ns1'] ?? null);
                $site->setNs2($row['ns2'] ?? null);
                
                if (!empty($row['publish_date'])) {
                    try { $site->setPublishDate(new \DateTime($row['publish_date'])); } catch (\Exception $e) {}
                } else {
                    $site->setPublishDate(null);
                }

                // --- КОМАНДЫ ---
                $statusFields = [
                    'status_registration', 'y_txt_status', 'gsc_status', 'indexing_status', 
                    'status_cf', 'status_ns_update', 'ns_status', 
                    'status_proxy', 'full_cycle_status', 'upload_status', 'site_status'
                ];

                foreach ($statusFields as $field) {
                    $val = $row[$field] ?? 'pending';
                    if (str_starts_with($val, 'cmd:')) {
                        switch ($val) {
                            case 'cmd:check_availability':
                                $site->setSiteStatus('In Queue...'); 
                                $bus->dispatch(new CheckSiteMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:add_to_cf':
                                $site->setStatusCf('In Queue...'); 
                                $bus->dispatch(new AddCloudflareMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:check_ns':
                                $site->setNsStatus('Checking...');
                                $bus->dispatch(new CheckNsMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:update_ns':
                                $site->setStatusNsUpdate('In Queue...');
                                $bus->dispatch(new UpdateNsMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:verify_yandex':
                                $site->setYTxtStatus('In Queue...');
                                $bus->dispatch(new VerifyYandexMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:verify_gsc':
                                $site->setGscStatus('In Queue...');
                                $bus->dispatch(new VerifyGscMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:setup_proxy':
                                $site->setStatusProxy('In Queue...');
                                $bus->dispatch(new SetupProxyMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            case 'cmd:send_index':
                                $site->setIndexingStatus('In Queue...');
                                $bus->dispatch(new \App\Message\ExternalScript\SendIndexMessage($site->getId()));
                                $dispatchedTasks++;
                                break;
                            default:
                                $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
                                if (method_exists($site, $setter)) $site->$setter($val);
                        }
                    } else {
                        $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
                        if (method_exists($site, $setter)) $site->$setter($val);
                    }
                }

                // --- СВЯЗИ ---
                $artId = isset($row['article_id']) ? (int)$row['article_id'] : 0;
                $site->setArticle($artId > 0 ? $articleRepo->find($artId) : null);

                $tplId = isset($row['template_id']) ? (int)$row['template_id'] : 0;
                $site->setTemplate($tplId > 0 ? $templateRepo->find($tplId) : null);

                // --- ЛОГИКА CF ACCOUNT ---
                $cfId = isset($row['cf_account_id']) ? (int)$row['cf_account_id'] : 0;
                
                if ($cfId > 0) {
                    $cfAccount = $cfRepo->find($cfId);
                    $site->setCloudflareAccount($cfAccount);
                    if ($cfAccount) {
                        if(empty($site->getCfEmail()))  $site->setCfEmail($cfAccount->getCfEmail());
                        if(empty($site->getCfApiKey())) $site->setCfApiKey($cfAccount->getCfApiKey());
                    }
                } else {
                    $registrar = $site->getRegistrar();
                    if (!empty($registrar)) {
                        $activeAccounts = $cfRepo->findBy(['is_active' => true]);
                        $candidates = array_filter($activeAccounts, fn($acc) => $acc->getStatus() < 5);

                        if (count($candidates) > 0) {
                            $luckyAccount = $candidates[array_rand($candidates)];
                            $site->setCloudflareAccount($luckyAccount);
                            $site->setCfEmail($luckyAccount->getCfEmail());
                            $site->setCfApiKey($luckyAccount->getCfApiKey());
                            $luckyAccount->setStatus($luckyAccount->getStatus() + 1);
                            $em->persist($luckyAccount);
                        } else {
                            $site->setCloudflareAccount(null); 
                        }
                    } else {
                        $site->setCloudflareAccount(null);
                    }
                }
                $count++;
            }
        }

        $em->flush();
        $msg = "Сохранено сайтов: $count";
        if ($dispatchedTasks > 0) $msg .= ". Задач запущено: $dispatchedTasks";

        return $this->json(['status' => 'success', 'message' => $msg]);
    }
    
    #[Route('/api/site/{id}/status', name: 'api_site_get_status', methods: ['GET'])]
    public function getSiteStatus(int $id, SiteRepository $siteRepo): JsonResponse
    {
        $site = $siteRepo->find($id);
        if (!$site) return $this->json(['status' => 'deleted']);
        
        return $this->json([
            'site_status' => $site->getSiteStatus(),
            'status' => $site->getSiteStatus(),
            'ns_status' => $site->getNsStatus(),
            'status_cf' => $site->getStatusCf(),
            'status_ns_update' => $site->getStatusNsUpdate(),
            'y_txt_status' => $site->getYTxtStatus(),
            'gsc_status' => $site->getGscStatus(),
            'indexing_status' => $site->getIndexingStatus(),
            'status_registration' => $site->getStatusRegistration(),
            'status_proxy' => $site->getStatusProxy(),
            'full_cycle_status' => $site->getFullCycleStatus(),
            'upload_status' => $site->getUploadStatus(),
            'ns1' => $site->getNs1(),
            'ns2' => $site->getNs2(),
        ]);
    }

    #[Route('/api/sites/delete', name: 'api_sites_delete', methods: ['POST'])]
    public function deleteSite(Request $request, SiteRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!empty($data['id']) && $item = $repo->find($data['id'])) {
            $em->remove($item);
            $em->flush();
            return $this->json(['status' => 'success']);
        }
        return $this->json(['status' => 'error']);
    }

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
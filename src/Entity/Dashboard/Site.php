<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\SiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'tbl_sites')]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // --- СВЯЗЬ С КОНТЕНТОМ ---
    #[ORM\OneToOne(targetEntity: Article::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', nullable: true)]
    private ?Article $article = null;

    // --- РЕГИСТРАЦИЯ ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $registrar = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $status_registration = 'pending';

    // --- ВЕБМАСТЕР ---
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $webmaster = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $y_txt_status = 'pending';

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $indexing_status = 'pending';

    // --- CLOUDFLARE ---
    
    // Связь с аккаунтом (для выбора из списка)
    #[ORM\ManyToOne(targetEntity: CloudflareAccount::class)]
    #[ORM\JoinColumn(name: 'cf_account_id', referencedColumnName: 'id', nullable: true)]
    private ?CloudflareAccount $cloudflareAccount = null;

    // Текстовые поля (заполняются скриптом или автоматически из связи)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cf_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cf_api_key = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $status_cf = 'pending';

    // --- NS ---
    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $status_ns_update = 'pending';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ns1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ns2 = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $ns_status = 'pending';

    // --- ПРОКСИ ---
    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $status_proxy = 'pending';

    // --- ШАБЛОН И СТАТУСЫ ---
    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: true)]
    private ?Template $template = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $full_cycle_status = 'pending';

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $upload_status = 'pending';

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publish_date = null;

    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $site_status = 'pending';

    // --- GOOGLE SEARCH CONSOLE ---
    #[ORM\Column(length: 50, nullable: true, options: ['default' => 'pending'])]
    private ?string $gsc_status = 'pending';

    public function getGscStatus(): ?string { return $this->gsc_status; }
    public function setGscStatus(?string $gsc_status): static { 
        $this->gsc_status = $gsc_status; 
        return $this; 
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int { return $this->id; }

    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $article): static { $this->article = $article; return $this; }

    public function getRegistrar(): ?string { return $this->registrar; }
    public function setRegistrar(?string $registrar): static { $this->registrar = $registrar; return $this; }

    public function getStatusRegistration(): ?string { return $this->status_registration; }
    public function setStatusRegistration(?string $status_registration): static { $this->status_registration = $status_registration; return $this; }

    public function getWebmaster(): ?string { return $this->webmaster; }
    public function setWebmaster(?string $webmaster): static { $this->webmaster = $webmaster; return $this; }

    public function getYTxtStatus(): ?string { return $this->y_txt_status; }
    public function setYTxtStatus(?string $y_txt_status): static { $this->y_txt_status = $y_txt_status; return $this; }

    public function getIndexingStatus(): ?string { return $this->indexing_status; }
    public function setIndexingStatus(?string $indexing_status): static { $this->indexing_status = $indexing_status; return $this; }

    // CF Relation
    public function getCloudflareAccount(): ?CloudflareAccount { return $this->cloudflareAccount; }
    public function setCloudflareAccount(?CloudflareAccount $cloudflareAccount): static { $this->cloudflareAccount = $cloudflareAccount; return $this; }

    public function getCfEmail(): ?string { return $this->cf_email; }
    public function setCfEmail(?string $cf_email): static { $this->cf_email = $cf_email; return $this; }

    public function getCfApiKey(): ?string { return $this->cf_api_key; }
    public function setCfApiKey(?string $cf_api_key): static { $this->cf_api_key = $cf_api_key; return $this; }

    public function getStatusCf(): ?string { return $this->status_cf; }
    public function setStatusCf(?string $status_cf): static { $this->status_cf = $status_cf; return $this; }

    public function getStatusNsUpdate(): ?string { return $this->status_ns_update; }
    public function setStatusNsUpdate(?string $status_ns_update): static { $this->status_ns_update = $status_ns_update; return $this; }

    public function getNs1(): ?string { return $this->ns1; }
    public function setNs1(?string $ns1): static { $this->ns1 = $ns1; return $this; }

    public function getNs2(): ?string { return $this->ns2; }
    public function setNs2(?string $ns2): static { $this->ns2 = $ns2; return $this; }

    public function getNsStatus(): ?string { return $this->ns_status; }
    public function setNsStatus(?string $ns_status): static { $this->ns_status = $ns_status; return $this; }

    public function getStatusProxy(): ?string { return $this->status_proxy; }
    public function setStatusProxy(?string $status_proxy): static { $this->status_proxy = $status_proxy; return $this; }

    public function getTemplate(): ?Template { return $this->template; }
    public function setTemplate(?Template $template): static { $this->template = $template; return $this; }

    public function getFullCycleStatus(): ?string { return $this->full_cycle_status; }
    public function setFullCycleStatus(?string $full_cycle_status): static { $this->full_cycle_status = $full_cycle_status; return $this; }

    public function getUploadStatus(): ?string { return $this->upload_status; }
    public function setUploadStatus(?string $upload_status): static { $this->upload_status = $upload_status; return $this; }

    public function getPublishDate(): ?\DateTimeInterface { return $this->publish_date; }
    public function setPublishDate(?\DateTimeInterface $publish_date): static { $this->publish_date = $publish_date; return $this; }

    public function getSiteStatus(): ?string { return $this->site_status; }
    public function setSiteStatus(?string $site_status): static { $this->site_status = $site_status; return $this; }
}
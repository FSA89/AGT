<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tbl_tasks')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Главный ключ
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $main_keyword = null;

    // Ключи (большой текст)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keywords = null;

    // URLs конкурентов
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competitor_urls = null;

    // Структура конкурентов (заполняет скрипт)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competitor_structures = null;

    // Количество (сколько генерировать)
    #[ORM\Column(nullable: true)]
    private ?int $count = null;

    // Готово (сколько сделано)
    #[ORM\Column(options: ['default' => 0])]
    private ?int $count_done = 0;

    // Статус (generate, processing, done)
    #[ORM\Column(length: 50, options: ['default' => 'generate'])]
    private ?string $status = 'generate';

    // Query (ID из нейроврайтера)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $query = null;

    // --- СВЯЗИ ---

    // Тип страницы
    #[ORM\ManyToOne(targetEntity: PageType::class)]
    #[ORM\JoinColumn(name: 'page_type_id', referencedColumnName: 'id', nullable: true)]
    private ?PageType $pageType = null;

    // Hreflang
    #[ORM\ManyToOne(targetEntity: Hreflang::class)]
    #[ORM\JoinColumn(name: 'hreflang_id', referencedColumnName: 'id', nullable: true)]
    private ?Hreflang $hreflang = null;

    // Схема Нейро
    #[ORM\ManyToOne(targetEntity: SchemeNeuro::class)]
    #[ORM\JoinColumn(name: 'scheme_neuro_id', referencedColumnName: 'id', nullable: true)]
    private ?SchemeNeuro $schemeNeuro = null;

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMainKeyword(): ?string
    {
        return $this->main_keyword;
    }

    public function setMainKeyword(?string $main_keyword): static
    {
        $this->main_keyword = $main_keyword;
        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getCompetitorUrls(): ?string
    {
        return $this->competitor_urls;
    }

    public function setCompetitorUrls(?string $competitor_urls): static
    {
        $this->competitor_urls = $competitor_urls;
        return $this;
    }

    public function getCompetitorStructures(): ?string
    {
        return $this->competitor_structures;
    }

    public function setCompetitorStructures(?string $competitor_structures): static
    {
        $this->competitor_structures = $competitor_structures;
        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function getCountDone(): ?int
    {
        return $this->count_done;
    }

    public function setCountDone(int $count_done): static
    {
        $this->count_done = $count_done;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function getPageType(): ?PageType
    {
        return $this->pageType;
    }

    public function setPageType(?PageType $pageType): static
    {
        $this->pageType = $pageType;
        return $this;
    }

    public function getHreflang(): ?Hreflang
    {
        return $this->hreflang;
    }

    public function setHreflang(?Hreflang $hreflang): static
    {
        $this->hreflang = $hreflang;
        return $this;
    }

    public function getSchemeNeuro(): ?SchemeNeuro
    {
        return $this->schemeNeuro;
    }

    public function setSchemeNeuro(?SchemeNeuro $schemeNeuro): static
    {
        $this->schemeNeuro = $schemeNeuro;
        return $this;
    }
}
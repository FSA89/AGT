<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'tbl_articles')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Поле "№" (например: "42(2)", "31")
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $task_custom_id = null;

    // Title
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $title = null;

    // Desc
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // Готово (HTML контент) - используем LONGTEXT для больших статей
    #[ORM\Column(type: Types::TEXT, length: 4294967295, nullable: true)]
    private ?string $content = null;

    // Оценка (число)
    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    // Статус (ready, publish, error)
    // Мы будем хранить коды, а на фронте выводить: Готово, Разместить, Ошибка
    #[ORM\Column(length: 50, options: ['default' => 'ready'])]
    private ?string $status = 'ready';

    // URL домена (строка)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $domain_url = null;

    // --- СВЯЗЬ С ЗАДАЧЕЙ ---
    // Через эту связь мы получим и query, и scheme_neuro
    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Task $task = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $scheme_neuro_snapshot = null;

    // --- GETTERS & SETTERS ---

    public function getId(): ?int { return $this->id; }

    public function getTaskCustomId(): ?string { return $this->task_custom_id; }
    public function setTaskCustomId(?string $task_custom_id): static { $this->task_custom_id = $task_custom_id; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): static { $this->content = $content; return $this; }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): static { $this->rating = $rating; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getDomainUrl(): ?string { return $this->domain_url; }
    public function setDomainUrl(?string $domain_url): static { $this->domain_url = $domain_url; return $this; }

    public function getTask(): ?Task { return $this->task; }
    public function setTask(?Task $task): static { $this->task = $task; return $this; }

    public function getSchemeNeuroSnapshot(): ?string { return $this->scheme_neuro_snapshot; }
    public function setSchemeNeuroSnapshot(?string $scheme_neuro_snapshot): static {
        $this->scheme_neuro_snapshot = $scheme_neuro_snapshot;
        return $this;
    }
}
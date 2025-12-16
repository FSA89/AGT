<?php

declare(strict_types=1);

namespace App\Entity\ContentGenerator;

use App\Repository\ContentGenerator\PromptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromptRepository::class)]
#[ORM\Table(name: 'tbl_prompts')]
class Prompt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Уникальный код промта. Например: 'structure_analyzer', 'meta_generator'
     */
    #[ORM\Column(length: 100, unique: true)]
    private ?string $code = null;

    /**
     * Основной текст промта (User Message). Поддерживает Twig синтаксис.
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    /**
     * Системное сообщение (System Message). Задает роль бота.
     * Тоже может поддерживать Twig, но обычно статично.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $systemMessage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getSystemMessage(): ?string
    {
        return $this->systemMessage;
    }

    public function setSystemMessage(?string $systemMessage): static
    {
        $this->systemMessage = $systemMessage;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
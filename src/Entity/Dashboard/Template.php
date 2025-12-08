<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\TemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\Table(name: 'tbl_templates')]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $template_name = null;

    #[ORM\Column(length: 255)]
    private ?string $server_name = null;

    #[ORM\Column(length: 255)]
    private ?string $button_url = null;

    #[ORM\Column(type: Types::JSON)]
    private ?array $json_template = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $is_active = true;

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplateName(): ?string
    {
        return $this->template_name;
    }

    public function setTemplateName(string $template_name): static
    {
        $this->template_name = $template_name;

        return $this;
    }

    public function getServerName(): ?string
    {
        return $this->server_name;
    }

    public function setServerName(string $server_name): static
    {
        $this->server_name = $server_name;

        return $this;
    }

    public function getButtonUrl(): ?string
    {
        return $this->button_url;
    }

    public function setButtonUrl(string $button_url): static
    {
        $this->button_url = $button_url;

        return $this;
    }

    public function getJsonTemplate(): ?array
    {
        return $this->json_template;
    }

    public function setJsonTemplate(array $json_template): static
    {
        $this->json_template = $json_template;

        return $this;
    }
}
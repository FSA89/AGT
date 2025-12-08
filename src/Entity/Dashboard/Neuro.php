<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\NeuroRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NeuroRepository::class)]
#[ORM\Table(name: 'tbl_neuro')]
class Neuro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $model_name = null;

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

    public function getModelName(): ?string
    {
        return $this->model_name;
    }

    public function setModelName(string $model_name): static
    {
        $this->model_name = $model_name;

        return $this;
    }
}
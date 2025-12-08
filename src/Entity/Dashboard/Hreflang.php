<?php

namespace App\Entity\Dashboard;

use App\Repository\HreflangRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HreflangRepository::class)]
#[ORM\Table(name: 'tbl_hreflang')]
class Hreflang
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $code = null;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }
}
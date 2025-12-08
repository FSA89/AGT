<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\CloudflareAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CloudflareAccountRepository::class)]
#[ORM\Table(name: 'tbl_cloudflare_accounts')]
class CloudflareAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $cf_email = null;

    #[ORM\Column(length: 255)]
    private ?string $cf_pass = null;

    #[ORM\Column(length: 255)]
    private ?string $cf_api_key = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private ?int $status = 0;

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

    public function getCfEmail(): ?string
    {
        return $this->cf_email;
    }

    public function setCfEmail(string $cf_email): static
    {
        $this->cf_email = $cf_email;

        return $this;
    }

    public function getCfPass(): ?string
    {
        return $this->cf_pass;
    }

    public function setCfPass(string $cf_pass): static
    {
        $this->cf_pass = $cf_pass;

        return $this;
    }

    public function getCfApiKey(): ?string
    {
        return $this->cf_api_key;
    }

    public function setCfApiKey(string $cf_api_key): static
    {
        $this->cf_api_key = $cf_api_key;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }
}
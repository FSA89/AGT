<?php

namespace App\Entity\Dashboard;

use App\Repository\Dashboard\SchemeNeuroRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchemeNeuroRepository::class)]
#[ORM\Table(name: 'tbl_scheme_neuro')]
class SchemeNeuro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Хранит строку с ID моделей через плюс (например: "15+22")
    #[ORM\Column(type: Types::TEXT)]
    private ?string $scheme_neuro = null;

    // Хранит полную строку с ID моделей через плюс (например: "10+10+15+22+25")
    #[ORM\Column(type: Types::TEXT)]
    private ?string $scheme_neuro_full = null;

    // --- Жесткие связи с таблицей Neuro (Foreign Keys) ---

    #[ORM\ManyToOne(targetEntity: Neuro::class)]
    #[ORM\JoinColumn(name: 'structure_analyzer_id', referencedColumnName: 'id', nullable: true)]
    private ?Neuro $structureAnalyzer = null;

    #[ORM\ManyToOne(targetEntity: Neuro::class)]
    #[ORM\JoinColumn(name: 'meta_header_generator_id', referencedColumnName: 'id', nullable: true)]
    private ?Neuro $metaHeaderGenerator = null;

    #[ORM\ManyToOne(targetEntity: Neuro::class)]
    #[ORM\JoinColumn(name: 'writer_id', referencedColumnName: 'id', nullable: true)]
    private ?Neuro $writer = null;

    #[ORM\ManyToOne(targetEntity: Neuro::class)]
    #[ORM\JoinColumn(name: 'meta_corrector_id', referencedColumnName: 'id', nullable: true)]
    private ?Neuro $metaCorrector = null;

    #[ORM\ManyToOne(targetEntity: Neuro::class)]
    #[ORM\JoinColumn(name: 'text_corrector_id', referencedColumnName: 'id', nullable: true)]
    private ?Neuro $textCorrector = null;

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

    public function getSchemeNeuro(): ?string
    {
        return $this->scheme_neuro;
    }

    public function setSchemeNeuro(string $scheme_neuro): static
    {
        $this->scheme_neuro = $scheme_neuro;

        return $this;
    }

    public function getSchemeNeuroFull(): ?string
    {
        return $this->scheme_neuro_full;
    }

    public function setSchemeNeuroFull(string $scheme_neuro_full): static
    {
        $this->scheme_neuro_full = $scheme_neuro_full;

        return $this;
    }

    public function getStructureAnalyzer(): ?Neuro
    {
        return $this->structureAnalyzer;
    }

    public function setStructureAnalyzer(?Neuro $structureAnalyzer): static
    {
        $this->structureAnalyzer = $structureAnalyzer;

        return $this;
    }

    public function getMetaHeaderGenerator(): ?Neuro
    {
        return $this->metaHeaderGenerator;
    }

    public function setMetaHeaderGenerator(?Neuro $metaHeaderGenerator): static
    {
        $this->metaHeaderGenerator = $metaHeaderGenerator;

        return $this;
    }

    public function getWriter(): ?Neuro
    {
        return $this->writer;
    }

    public function setWriter(?Neuro $writer): static
    {
        $this->writer = $writer;

        return $this;
    }

    public function getMetaCorrector(): ?Neuro
    {
        return $this->metaCorrector;
    }

    public function setMetaCorrector(?Neuro $metaCorrector): static
    {
        $this->metaCorrector = $metaCorrector;

        return $this;
    }

    public function getTextCorrector(): ?Neuro
    {
        return $this->textCorrector;
    }

    public function setTextCorrector(?Neuro $textCorrector): static
    {
        $this->textCorrector = $textCorrector;

        return $this;
    }
}
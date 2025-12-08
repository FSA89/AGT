<?php

namespace App\DTO;

class CategoryDTO
{
    private int $domainId;
    private int $regionId;
    private bool $isMobile;
    private array $categoriesId;
    private string $title;
    private \DateTimeInterface $dateTime;


    final public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }

    final public function setDateTime(\DateTimeInterface $dateTime): CategoryDTO
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    final public function getDomainId(): int
    {
        return $this->domainId;
    }

    final public function setDomainId(int $domainId): CategoryDTO
    {
        $this->domainId = $domainId;
        return $this;
    }

    final public function getRegionId(): int
    {
        return $this->regionId;
    }

    final public function setRegionId(int $regionId): CategoryDTO
    {
        $this->regionId = $regionId;
        return $this;
    }

    final public function isMobile(): bool
    {
        return $this->isMobile;
    }

    final public function setIsMobile(bool $isMobile): CategoryDTO
    {
        $this->isMobile = $isMobile;
        return $this;
    }

    final public function getCategoriesId(): array
    {
        return $this->categoriesId;
    }

    final public function setCategoriesId(array $categories): CategoryDTO
    {
        $this->categoriesId = $categories;
        return $this;
    }
    final public function addCategoryId(int $id): CategoryDTO
    {
        $this->categoriesId[] = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): CategoryDTO
    {
        $this->title = $title;
        return $this;
    }


}
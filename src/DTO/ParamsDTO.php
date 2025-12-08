<?php
declare(strict_types=1);
namespace App\DTO;
class ParamsDTO
{
    private \DateTimeInterface $date;
    private int $domainId;
    private int $integrationId;
    private \DateTimeInterface $integrationDate;
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;
    private \DateTimeInterface $nextSunday;
    private int $taskId;
    private int $regionId;
    private bool $isMobile;
    private array $categoryId;
    private string $title;
    private string|null $user;
    private string|null $department;
    private string|null $tagvalue;

    public function __construct()
    {
        $this->setUser(null);
        $this->setDepartment(null);
        $this->setTagvalue(null);
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): ParamsDTO
    {
        $this->date = $date;
        return $this;
    }

    public function getDomainId(): int
    {
        return $this->domainId;
    }

    public function setDomainId(int $domainId): ParamsDTO
    {
        $this->domainId = $domainId;
        return $this;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId):ParamsDTO
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function getIntegrationDate(): \DateTimeInterface
    {
        return $this->integrationDate;
    }

    public function setIntegrationDate(\DateTimeInterface $integrationDate): ParamsDTO
    {
        $this->integrationDate = $integrationDate;
        return $this;
    }

    public function getRegionId(): int
    {
        return $this->regionId;
    }

    public function setRegionId(int $regionId): ParamsDTO
    {
        $this->regionId = $regionId;
        return $this;
    }

    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    public function setIsMobile(bool $isMobile): ParamsDTO
    {
        $this->isMobile = $isMobile;
        return $this;
    }

    public function getCategoryId(): array
    {
        return $this->categoryId;
    }

    public function setCategoryId(array $categoryId):ParamsDTO
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ParamsDTO
    {
        $this->title = $title;
        return $this;
    }

    public function setNextSunday(\DateTimeInterface $nextSunday):ParamsDTO
    {
        $this->nextSunday = $nextSunday;
        return $this;
    }

    public function getNextSunday(): \DateTimeInterface
    {
        return $this->nextSunday;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): ParamsDTO
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): ParamsDTO
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getUser(): string|null
    {
        return $this->user;
    }

    public function setUser(string|null $user): ParamsDTO
    {
        $this->user = $user;
        return $this;
    }

    public function getDepartment(): string|null
    {
        return $this->department;
    }

    public function setDepartment(string|null $department): ParamsDTO
    {
        $this->department = $department;
        return $this;
    }

    public function getTagvalue(): string|null
    {
        return $this->tagvalue;
    }

    public function setTagvalue(string|null $tagvalue): ParamsDTO
    {
        $this->tagvalue = $tagvalue;
        return $this;
    }
}
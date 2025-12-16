<?php

namespace App\Repository\ContentGenerator;

use App\Entity\ContentGenerator\Prompt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prompt>
 */
class PromptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prompt::class);
    }

    public function findByCode(string $code): ?Prompt
    {
        return $this->findOneBy(['code' => $code]);
    }
}
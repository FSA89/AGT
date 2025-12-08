<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\SchemeNeuro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SchemeNeuro>
 */
class SchemeNeuroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchemeNeuro::class);
    }
}
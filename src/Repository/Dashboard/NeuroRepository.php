<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\Neuro;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Neuro>
 */
class NeuroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Neuro::class);
    }
}
<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\Hreflang; 
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hreflang>
 */
class HreflangRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hreflang::class);
    }
}
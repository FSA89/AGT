<?php

namespace App\Repository\Dashboard;

use App\Entity\Dashboard\CloudflareAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CloudflareAccount>
 */
class CloudflareAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CloudflareAccount::class);
    }
}
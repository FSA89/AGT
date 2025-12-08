<?php
declare(strict_types=1);

namespace App\Repository\api\v1;

use App\Repository\DefaultRepository;
use Doctrine\Persistence\ManagerRegistry;

class AntUsersRepository extends  DefaultRepository
{
    /**
     * Получение всех пользователей.
     */
    final public function findUsers(ManagerRegistry $doctrine): array
    {
        $connection = $doctrine->getConnection('antusers');
        $users = $connection->fetchAllAssociative("SELECT id_user, name, department
                FROM users u
                WHERE is_active = 1
                AND department LIKE '%SEO%'");
        return ['status' => 'success', 'data' => $users];
    }
}

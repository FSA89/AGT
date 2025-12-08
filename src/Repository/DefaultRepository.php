<?php
declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\LazyServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Connection;
abstract class DefaultRepository extends LazyServiceEntityRepository
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->setSqlMode();
    }

    private function setSqlMode(): void
    {
        try {
            $this->connection->executeQuery("SET sql_mode = '';");
        } catch (Exception $e) {
            throw new \RuntimeException('Database error: ' . $e->getMessage());
        }
    }

    protected function executeQuery(string $sql, array $parameters = []): array
    {
        try {
            $stmt = $this->connection->executeQuery($sql, $parameters);
            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            return ['status' => 'fail', 'message' => $e->getMessage(), 'code' => '400'];
        }
    }

}
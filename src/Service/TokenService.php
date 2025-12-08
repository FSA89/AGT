<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Сервис для работы с токенами/авторизацией.
 *
 * Refactored: Ключи вынесены в ENV.
 */
class TokenService
{
    /**
     * Используем Constructor Property Promotion (PHP 8.0+).
     * Свойство создается и присваивается автоматически.
     * readonly (PHP 8.1+) гарантирует неизменяемость.
     */
    public function __construct(
        private readonly string $externalApiKey,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Пример метода, который использовал хардкод.
     * Теперь используем $this->externalApiKey.
     */
    public function getAuthHeaders(): array
    {
        // В реальном сценарии здесь может быть сложная логика подписи
        // или получения временного токена.
        
        return [
            'Authorization' => 'Bearer ' . $this->externalApiKey,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Валидация входящего токена (пример)
     */
    public function isValid(string $inputToken): bool
    {
        if (empty($inputToken)) {
            $this->logger->warning('Token validation failed: input is empty');
            return false;
        }

        // hash_equals предотвращает timing attacks
        return hash_equals($this->externalApiKey, $inputToken);
    }
}
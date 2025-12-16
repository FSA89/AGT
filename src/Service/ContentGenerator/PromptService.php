<?php

declare(strict_types=1);

namespace App\Service\ContentGenerator;

use App\Repository\ContentGenerator\PromptRepository;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PromptService
{
    public function __construct(
        private readonly PromptRepository $promptRepository,
        private readonly Environment $twig // Используем штатный Twig Symfony
    ) {
    }

    /**
     * Получает промт из БД и подставляет в него данные.
     * Возвращает массив ['system' => string, 'user' => string]
     *
     * @param string $promptCode Код промта (slug)
     * @param array $context Данные для подстановки (ключи, заголовки и т.д.)
     * @return array{system: string, user: string}
     */
    public function render(string $promptCode, array $context = []): array
    {
        $prompt = $this->promptRepository->findByCode($promptCode);

        if (!$prompt) {
            throw new RuntimeException("Prompt with code '{$promptCode}' not found in database.");
        }

        // Рендерим System Message (если есть)
        $systemMsg = '';
        if ($prompt->getSystemMessage()) {
            $systemMsg = $this->renderString($prompt->getSystemMessage(), $context);
        }

        // Рендерим User Message
        $userMsg = $this->renderString($prompt->getText(), $context);

        return [
            'system' => $systemMsg,
            'user' => $userMsg,
        ];
    }

    /**
     * Создает временный шаблон Twig из строки и рендерит его.
     */
    private function renderString(string $templateContent, array $context): string
    {
        try {
            $template = $this->twig->createTemplate($templateContent);
            return $template->render($context);
        } catch (\Throwable $e) {
            throw new RuntimeException("Error rendering prompt template: " . $e->getMessage());
        }
    }
}
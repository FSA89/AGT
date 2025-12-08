<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    // #[Route('/connect/google', name: 'connect_google_start')]
    // public function connect(ClientRegistry $clientRegistry): RedirectResponse
    // {
    //     // Запрос прав profile и email у Google
    //     return $clientRegistry->getClient('google')->redirect(['profile', 'email']);
    // }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // Добавляем prompt => select_account, чтобы Google каждый раз показывал окно выбора аккаунта
        return $clientRegistry->getClient('google')->redirect(
            ['profile', 'email'],
            ['prompt' => 'select_account']
        );
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(): void
    {
        // Здесь пусто — обработка логики в GoogleAuthenticator
    }
}
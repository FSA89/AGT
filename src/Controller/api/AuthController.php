<?php

// src/Controller/api/AuthController.php

declare(strict_types=1);

namespace App\Controller\api;

use App\Repository\api\DatesRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use App\Service\TokenService;

#[Route('/api', name: 'api_check_')]
class AuthController extends BaseController
{
    private TokenService $tokenService;
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }
    #[Route('/generate-token', methods: ['POST'])]
    #[OA\Get(summary: "Генерация токена", tags: ["Auth"])]
    final public function index(Request $request): JsonResponse
    {

        $userIP = $request->getClientIp();

        $secret = '5ec88e31cbb3e6e8897311f1e9bd78fe973b757a449035c078159d11e292610e';
        $timestamp = time();
        $message = "{$userIP}:{$timestamp}";
        $signature = hash_hmac('sha256', $message, $secret,true);
        $signatureBase64 = base64_encode($signature);
        return $this->json(['token' => "{$userIP}:{$timestamp}:{$signatureBase64}"]);
    }

    #[Route('/check-token', methods: ['GET'])]
    #[OA\Get(summary: "Валидация токена", tags: ["Auth"])]
    public function checkToken(Request $request): JsonResponse
    {
        try {
            $userId = $this->tokenService->validateToken($request); // Проверка токена
            return new JsonResponse(['status' => 'Token is valid']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
    }


}
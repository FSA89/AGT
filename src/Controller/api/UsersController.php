<?php
declare(strict_types=1);
namespace App\Controller\api;


use App\Repository\api\v1\AntUsersRepository;

use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1', name: 'api_users_')]
class UsersController extends AbstractController
{
    #[Route('/users', methods: ['GET'])]
    #[OA\Get(summary: "Получить всех пользователей", tags: ["Пользователи"])]
    #[OA\Response(response: 200, description: "Пользователи найдены")]
    #[OA\Response(response: 404, description: "Пользователи не найдены")]
    final public function index(ManagerRegistry $doctrine,AntUsersRepository $usersRepository): JsonResponse
    {
        $users = $usersRepository->findUsers($doctrine);
        $status = (!empty($users)) ? 200 : 400;
        return $this->json($users, $status);
    }



}

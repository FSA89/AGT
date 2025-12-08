<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class MainController extends AbstractController
{
    #[Route('', name: 'dashboard_index')]
    public function index(): Response
    {
        // Перенаправляем на задачи по умолчанию
        return $this->redirectToRoute('dashboard_tasks');
    }
}
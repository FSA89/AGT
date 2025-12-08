<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'app_')]
final class DefaultController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        $user = $this->getUser();

        return $this->render('home.html.twig', [
            'controller_name' => 'DefaultController',
            'user_roles' => $user ? $user->getRoles() : [],
            'user_authenticated' => (bool)$user,
        ]);
    }
    
    // Если методы generate и root нужны были только для старого функционала, 
    // их можно удалить. Если нужны как заглушки - оставь.
    #[Route('/generate', name: 'generate')]
    public function generate(): Response
    {
        return $this->render('generate.html.twig');
    }

    #[Route('/root', name: 'auch')]
    public function root(): Response
    {
        return $this->render('root/index.html.twig');
    }
}
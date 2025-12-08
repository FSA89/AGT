<?php

namespace App\Security;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        #[Autowire(service: 'doctrine.orm.default_entity_manager')]
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private ManagerRegistry $managerRegistry,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUser();
        $email = $googleUser->getEmail();

        $allowedDomain = 'traffic-hunters.com';
        if (!str_ends_with($email, '@' . $allowedDomain)) {
            throw new AuthenticationException("Email не принадлежит домену $allowedDomain. Вам запрещено авторизоваться!!!");
        }

        return new Passport(
            new UserBadge($email, function () use ($email) {
                $em = $this->managerRegistry->getManager();
                $userRepository = $em->getRepository(User::class);
                $user = $userRepository->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setRoles(['ROLE_USER']);
                    $em->persist($user);
                    $em->flush();
                }

                return $user;
            }),
            new AlwaysValidCredentials()
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse('/');
    }
}
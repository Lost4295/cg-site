<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class DiscordAuthenticator extends AbstractAuthenticator
{
    public const DISCORD_AUTH_KEY = 'discord-auth';

    public function __construct(
        private readonly RouterInterface $router
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        // Cet authenticator ne traite pas le callback OAuth.
        // Le contrôleur gère l'échange du code et la vérification du state.
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        throw new AuthenticationException(
            'Cet authenticator ne gère pas directement les requêtes.'
        );
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var Session $session */
        $session = $request->getSession();

        $session->remove(self::DISCORD_AUTH_KEY);
        $session->getFlashBag()->add('danger', $exception->getMessage());

        return new RedirectResponse($this->router->generate('app_home'));
    }
}

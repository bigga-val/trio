<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractAuthenticator
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login';
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('adresseEmail');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        if (empty($email)) {
            throw new AuthenticationException('L\'adresse e-mail est requise.');
        }

        if (empty($password)) {
            throw new AuthenticationException('Le mot de passe est requis.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Stocker le nom d'utilisateur en session
        $request->getSession()->set('user_name', $token->getUser()->getAdresseEmail());
        $request->getSession()->set('last_activity', time());
        
        // Vérifier le rôle de l'utilisateur et rediriger en conséquence
        $user = $token->getUser();
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            // Rediriger l'admin vers le tableau de bord
            return new Response(null, Response::HTTP_FOUND, ['Location' => $this->urlGenerator->generate('app_admin_tableau')]);
        }
        
        // Rediriger le client normal vers le chat
        return new Response(null, Response::HTTP_FOUND, ['Location' => $this->urlGenerator->generate('app_chat_index')]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Stocker l'erreur en session pour l'afficher sur la page de login
        $request->getSession()->set('_security.last_error', $exception);
        return new Response(null, Response::HTTP_FOUND, ['Location' => $this->urlGenerator->generate('app_login')]);
    }
}

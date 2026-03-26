<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SessionTimeoutListener implements EventSubscriberInterface
{
    private const INACTIVITY_TIMEOUT = 300; // 5 minutes en secondes
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        // Ignorer les routes de login/logout/keep-alive
        $route = $request->attributes->get('_route');
        if (in_array($route, ['app_login', 'logout', 'app_keep_alive'])) {
            return;
        }

        // Vérifier si l'utilisateur est connecté
        if ($session->has('last_activity')) {
            $lastActivity = $session->get('last_activity');
            $currentTime = time();
            $elapsedTime = $currentTime - $lastActivity;

            // Si l'inactivité dépasse le timeout, rediriger vers logout
            if ($elapsedTime > self::INACTIVITY_TIMEOUT) {
                $session->invalidate();
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
                return;
            }
        }
    }
}


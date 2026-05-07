<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AccessDeniedListener
{
    private $router;
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();
            $referer = $request->headers->get('referer');

            if ($referer) {
                $response = new RedirectResponse($referer);
            } else {
                $response = new RedirectResponse($this->router->generate('app_homepage_index'));
            }

            $session->getFlashBag()->add('error', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');

            $event->setResponse($response);
        }
    }
}
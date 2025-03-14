<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessDeniedListener implements EventSubscriberInterface
{
  private $urlGenerator;

  public function __construct(UrlGeneratorInterface $urlGenerator)
  {
    $this->urlGenerator = $urlGenerator;
  }

  public static function getSubscribedEvents(): array
  {
    return [
      KernelEvents::EXCEPTION => ['onKernelException', 2],
    ];
  }

  public function onKernelException(ExceptionEvent $event): void
  {
    $exception = $event->getThrowable();

    if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
      $request = $event->getRequest();
      $session = $request->getSession();

      if ($session instanceof \Symfony\Component\HttpFoundation\Session\Session) {
        $session->getFlashBag()->add('warning', 'You need admin privileges to view this page.');
      }

      $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
      $event->setResponse($response);
    }
  }
}

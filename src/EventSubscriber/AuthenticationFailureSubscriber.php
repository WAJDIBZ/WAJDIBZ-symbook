<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthenticationFailureSubscriber implements EventSubscriberInterface
{
    private $translator;
    private $requestStack;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        if ($exception instanceof BadCredentialsException) {
            // Remplacer l'exception par une nouvelle avec le message traduit
            $translatedMessage = "L'adresse e-mail ou le mot de passe est incorrect.";

            // Stocker ce message dans la session flash pour l'utiliser dans le template
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('login_error', $translatedMessage);
        }
    }
}

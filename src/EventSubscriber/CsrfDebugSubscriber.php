<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfDebugSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LoggerInterface $logger
    ) {}

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

        if ($request->getPathInfo() === '/login' && $request->isMethod('POST')) {
            $csrfToken = $request->request->get('_csrf_token');

            if ($csrfToken) {
                $tokenId = 'authenticate';
                $isValid = $this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $csrfToken));

                $this->logger->info('CSRF Token Debug', [
                    'token_value' => $csrfToken,
                    'token_id' => $tokenId,
                    'is_valid' => $isValid ? 'YES' : 'NO',
                ]);
            } else {
                $this->logger->warning('No CSRF token found in login request');
            }
        }
    }
}

<?php

namespace App\Mailer\Transport;

use App\Service\OAuthTokenManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

/**
 * OAuth2SmtpTransport extends EsmtpTransport to use XOAUTH2 for authentication.
 */
class OAuth2SmtpTransport extends EsmtpTransport
{
    private ?OAuthTokenManager $tokenManager = null;
    private ?LoggerInterface $logger = null;
    public function __construct(
        string $host = 'localhost',
        int $port = 465,
        ?OAuthTokenManager $tokenManager = null,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        // Call the parent constructor with the host, port, and encryption set to SSL (true for TLS, 'ssl' for SSL)
        parent::__construct($host, $port, true, $dispatcher, $logger);

        if ($tokenManager) {
            $this->tokenManager = $tokenManager;
        }
        if ($logger) {
            $this->logger = $logger;
        }

        // Set TLS encryption via the third parameter in the parent constructor
        // This is already done in the parent constructor call
    }
    protected function doAuthLogin(): void
    {
        if (!$this->tokenManager) {
            $this->logger->warning('OAuthTokenManager not available, falling back to standard authentication');
            parent::doAuthLogin();
            return;
        }

        try {
            // Let the system authenticators handle this
            // The XOAuth2Authenticator should be registered and will handle OAuth2 auth
            parent::doAuthLogin();
            $this->logger->info('Authentication successful using registered authenticators');
        } catch (TransportException $e) {
            $this->logger->error('Authentication failed using registered authenticators', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

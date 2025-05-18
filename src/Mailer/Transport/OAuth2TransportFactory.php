<?php

namespace App\Mailer\Transport;

use App\Service\OAuthTokenManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class OAuth2TransportFactory extends AbstractTransportFactory
{
    protected OAuthTokenManager $tokenManager;
    protected ?LoggerInterface $logger;

    public function __construct(
        OAuthTokenManager $tokenManager,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
    }
    public function create(Dsn $dsn): TransportInterface
    {
        // Extract host, port, username, etc.
        $host = $dsn->getHost();
        $port = $dsn->getPort(587);
        $username = $dsn->getUser();
        // Get encryption option (TLS is default)
        $tls = $dsn->getOption('encryption') !== 'off';

        // Create OAuth2 SMTP transport with TLS
        $transport = new OAuth2SmtpTransport($host, $port, $this->tokenManager, $this->logger);

        // Set authentication credentials
        if ($username) {
            $transport->setUsername($username);
            // Password isn't used for OAuth2, but we set it to satisfy the interface
            $transport->setPassword($dsn->getPassword() ?: '');
        }

        return $transport;
    }
    protected function getSupportedSchemes(): array
    {
        return ['smtp+oauth2'];
    }

    public function supports(Dsn $dsn): bool
    {
        return in_array($dsn->getScheme(), $this->getSupportedSchemes());
    }
}

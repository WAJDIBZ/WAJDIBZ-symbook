<?php

namespace App\Mailer\Transport;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Creates an OAuth2-enabled SMTP transport.
 */
class OAuth2TransportFactoryBridge extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $host = $dsn->getHost();
        $port = $dsn->getPort(587);

        $transport = new OAuth2SmtpTransport($host, $port);

        // Set encryption if specified
        $encryption = $dsn->getOption('encryption');
        if (!empty($encryption)) {
            $transport->setEncryption($encryption);
        }

        // Set authentication credentials
        $user = $dsn->getUser();
        if ($user) {
            $transport->setUsername($user);
            // Password isn't actually used for OAuth2, but we set it to satisfy the interface
            $transport->setPassword($dsn->getPassword() ?: '');
        }

        return $transport;
    }

    protected function getSupportedSchemes(): array
    {
        return ['smtp+oauth2'];
    }
}

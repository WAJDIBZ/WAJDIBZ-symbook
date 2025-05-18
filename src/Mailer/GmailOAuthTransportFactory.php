<?php

namespace App\Mailer;

use App\Service\GoogleOAuth2TokenFetcher;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class GmailOAuthTransportFactory extends AbstractTransportFactory
{
    private GoogleOAuth2TokenFetcher $tokenFetcher;

    public function __construct(GoogleOAuth2TokenFetcher $tokenFetcher)
    {
        $this->tokenFetcher = $tokenFetcher;
        parent::__construct();
    }

    protected function getSupportedSchemes(): array
    {
        return ['gmail+oauth'];
    }
    public function create(Dsn $dsn): TransportInterface
    {
        // Create a standard SMTP transport - let the DSN handle the auth mode
        $transport = new EsmtpTransport(
            $dsn->getHost(),
            $dsn->getPort() ?: 587,
            true
        );

        $transport->setUsername($dsn->getUser());
        $transport->setPassword($this->tokenFetcher->getAccessToken());

        return $transport;
    }
}

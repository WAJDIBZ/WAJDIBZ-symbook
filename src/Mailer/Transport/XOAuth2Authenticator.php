<?php

namespace App\Mailer\Transport;

use App\Service\OAuthTokenManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\AuthenticatorInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class XOAuth2Authenticator implements AuthenticatorInterface
{
    private OAuthTokenManager $tokenManager;
    private LoggerInterface $logger;

    public function __construct(
        OAuthTokenManager $tokenManager,
        LoggerInterface $logger
    ) {
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
    }

    public function getAuthKeyword(): string
    {
        return 'XOAUTH2';
    }
    /**
     * @throws \Exception if authentication fails
     */    public function authenticate(EsmtpTransport $transport): void
    {
        try {
            // Get username
            $username = $transport->getUsername();

            if (empty($username)) {
                throw new \LogicException('No username provided for XOAUTH2 authentication');
            }

            $this->logger->debug('Starting XOAUTH2 authentication for ' . $username);

            // Get fresh token
            $accessToken = $this->tokenManager->getAccessToken();
            $this->logger->debug('Access token obtained: ' . substr($accessToken, 0, 10) . '...[truncated]');

            // Create XOAUTH2 token string exactly as Gmail expects
            // Format: "user={User}\1auth=Bearer {Access Token}\1\1"
            // Note: \1 is the ASCII SOH (Start of Heading) character, equivalent to Control+A or Chr(1)
            $authStringRaw = "user=$username\1auth=Bearer $accessToken\1\1";
            $authString = base64_encode($authStringRaw);

            $this->logger->debug('XOAUTH2 authentication string created (encoded)');

            // SMTP XOAUTH2 auth exchange - handle 334 response (challenge)
            try {
                $this->logger->debug('Sending XOAUTH2 auth command');
                $response = $transport->executeCommand("AUTH XOAUTH2 $authString\r\n", [235, 334]);

                // If we got a 334 challenge, we need to respond with an empty line as per protocol
                if (strpos($response, '334') === 0) {
                    $this->logger->debug('Received 334 challenge response, decoding: ' . $response);

                    // Extract and decode the base64 encoded error
                    $encodedError = trim(substr($response, 4));
                    $decodedError = base64_decode($encodedError);
                    $this->logger->debug('Decoded challenge: ' . $decodedError);

                    // Send empty response as required by protocol
                    $this->logger->debug('Sending empty response to challenge');
                    $transport->executeCommand("\r\n", [235]);
                }

                $this->logger->debug('XOAUTH2 authentication successful');
            } catch (\Exception $e) {
                $this->logger->error('XOAUTH2 auth error response: ' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            // If authentication fails, invalidate the cached token as it might be expired
            $this->tokenManager->invalidateToken();
            $this->logger->error('XOAUTH2 authentication failed: ' . $e->getMessage());
            throw $e;
        }
    }
}

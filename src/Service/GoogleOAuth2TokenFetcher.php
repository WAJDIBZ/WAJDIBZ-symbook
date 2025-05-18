<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GoogleOAuth2TokenFetcher
{
    private ?string $initialAccessToken = null;

    public function __construct(
        private HttpClientInterface $client,
        private string $clientId,
        private string $clientSecret,
        private string $refreshToken,
        ?string $accessToken = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->initialAccessToken = $accessToken;
    }

    public function getAccessToken(): string
    {
     
        if ($this->initialAccessToken) {
            $token = $this->initialAccessToken;
         
            $this->initialAccessToken = null;
            return $token;
        }

        try {
            $response = $this->client->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'])) {
                if ($this->logger) {
                    $this->logger->error('Failed to get access token: ' . json_encode($data));
                }
                throw new \RuntimeException('No access token in response: ' . json_encode($data));
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Error refreshing token: ' . $e->getMessage());
            }
            throw new \RuntimeException('Failed to refresh OAuth2 token: ' . $e->getMessage(), 0, $e);
        }
    }
}

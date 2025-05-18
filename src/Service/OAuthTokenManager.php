<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class OAuthTokenManager
{
    private FilesystemAdapter $cache;
    private const TOKEN_CACHE_KEY = 'email_oauth_token';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
        $this->cache = new FilesystemAdapter('oauth', 3600, $this->params->get('kernel.cache_dir'));
    }
    public function getAccessToken(): string
    {
      
        $this->logger->debug('OAuthTokenManager::getAccessToken() called');

        return $this->cache->get(self::TOKEN_CACHE_KEY, function (ItemInterface $item) {
            $this->logger->info('Fetching new OAuth access token for email service');
            $item->expiresAfter(3500); 

            return $this->fetchNewAccessToken();
        });
    }
    private function fetchNewAccessToken(): string
    {
        try {
            $clientId = $this->params->get('oauth.client_id');
            $clientSecret = $this->params->get('oauth.client_secret');
            $refreshToken = $this->params->get('oauth.refresh_token');
            $tokenUrl = $this->params->get('oauth.token_url', 'https://oauth2.googleapis.com/token');

            
            if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
                throw new \InvalidArgumentException(
                    'Missing OAuth2 credentials. Ensure oauth.client_id, oauth.client_secret, and oauth.refresh_token are configured.'
                );
            }

            $this->logger->debug('Requesting new OAuth2 access token', [
                'token_url' => $tokenUrl,
                'client_id' => substr($clientId, 0, 5) . '...' 
            ]);
       
            $params = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
                'scope' => 'https://mail.google.com/' 
            ];

            $this->logger->debug('Requesting OAuth token with params', [
                'tokenUrl' => $tokenUrl,
                'client_id' => substr($clientId, 0, 10) . '...[truncated]',
                'refresh_token' => substr($refreshToken, 0, 10) . '...[truncated]',
                'scope' => 'https://mail.google.com/'
            ]);

            $response = $this->httpClient->request('POST', $tokenUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => $params
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \RuntimeException("OAuth token request failed with status code: $statusCode");
            }
            $data = $response->toArray();

            $this->logger->debug('OAuth response received', [
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->getHeaders()['content-type'][0] ?? 'unknown',
                'has_token' => isset($data['access_token']) ? 'yes' : 'no',
                'expires_in' => $data['expires_in'] ?? 'unknown',
                'scope' => $data['scope'] ?? 'unknown',
                'token_type' => $data['token_type'] ?? 'unknown'
            ]);

            if (!isset($data['access_token'])) {
                $this->logger->error('OAuth response missing access_token', [
                    'response' => $data
                ]);
                throw new \RuntimeException('No access token found in OAuth response');
            }

            // Log token expiration if available
            if (isset($data['expires_in'])) {
                $this->logger->info('Successfully obtained new OAuth access token', [
                    'expires_in' => $data['expires_in'] . ' seconds',
                    'token_preview' => substr($data['access_token'], 0, 10) . '...[truncated]'
                ]);
            } else {
                $this->logger->info('Successfully obtained new OAuth access token');
            }

            return $data['access_token'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch OAuth token: ' . $e->getMessage(), [
                'exception_class' => get_class($e)
            ]);
            throw $e;
        }
    }

    public function invalidateToken(): void
    {
        $this->cache->delete(self::TOKEN_CACHE_KEY);
        $this->logger->info('OAuth token cache invalidated');
    }
}

<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:generate-oauth-token',
    description: 'Help generate OAuth2 tokens for email configuration',
)]
class GenerateOAuthTokenCommand extends Command
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
    }

    protected function configure(): void
    {
        $this
            ->addOption('client-id', null, InputOption::VALUE_REQUIRED, 'OAuth2 client ID')
            ->addOption('client-secret', null, InputOption::VALUE_REQUIRED, 'OAuth2 client secret')
            ->addOption('auth-code', null, InputOption::VALUE_REQUIRED, 'Authorization code (if you already have one)')
            ->addOption('refresh-token', null, InputOption::VALUE_REQUIRED, 'Refresh token (if you already have one)')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'OAuth2 provider (google, microsoft, or custom)', 'google')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('OAuth2 Token Generator for Email Authentication');

        // Determine provider
        $provider = strtolower($input->getOption('provider'));

        // Get token endpoints based on provider
        $endpoints = $this->getProviderEndpoints($provider);
        if (!$endpoints) {
            $io->error("Unknown provider: $provider");
            return Command::FAILURE;
        }

        $io->section('Step 1: Collect OAuth2 Credentials');

        // Get client ID
        $clientId = $input->getOption('client-id');
        if (!$clientId) {
            $clientId = $io->ask('Enter your OAuth2 client ID:');
        }

        // Get client secret
        $clientSecret = $input->getOption('client-secret');
        if (!$clientSecret) {
            // Hide input for security
            $question = new Question('Enter your OAuth2 client secret:');
            $question->setHidden(true);
            $clientSecret = $io->askQuestion($question);
        }

        // Check if we already have a refresh token
        $refreshToken = $input->getOption('refresh-token');
        if ($refreshToken) {
            $io->section('Using provided refresh token to generate access token');
            return $this->handleRefreshToken($io, $clientId, $clientSecret, $refreshToken, $endpoints);
        }

        // Get auth code if provided
        $authCode = $input->getOption('auth-code');

        // If no auth code, guide user to get one
        if (!$authCode) {
            $io->section('Step 2: Get Authorization Code');

            $scopes = $endpoints['scopes'];
            $authUrl = $this->buildAuthUrl($clientId, $endpoints['auth_url'], $scopes);

            $io->writeln([
                'Visit the following URL in your browser to authorize access:',
                '',
                "<href=$authUrl>$authUrl</>"
            ]);

            $io->writeln([
                '',
                '1. Click the URL or copy it to your browser',
                '2. Log in and authorize the requested permissions',
                '3. After authorization, you\'ll be redirected to a page with an authorization code',
                '4. Copy the authorization code from the URL or page',
                ''
            ]);

            $authCode = $io->ask('Enter the authorization code you received:');
        }

        $io->section('Step 3: Exchange Authorization Code for Tokens');

        try {
            // Exchange auth code for tokens
            $response = $this->httpClient->request('POST', $endpoints['token_url'], [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $authCode,
                    'redirect_uri' => $endpoints['redirect_uri'],
                    'grant_type' => 'authorization_code'
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token']) || !isset($data['refresh_token'])) {
                $io->error('Failed to retrieve tokens. Response: ' . json_encode($data));
                return Command::FAILURE;
            }

            $io->success('Successfully retrieved OAuth2 tokens!');

            $this->displayTokenInfo($io, $data);

            $io->section('Step 4: Configure Your Application');

            $io->writeln([
                'Add the following lines to your .env.local file:',
                '',
                '```',
                'OAUTH_CLIENT_ID=' . $clientId,
                'OAUTH_CLIENT_SECRET=' . $clientSecret,
                'OAUTH_REFRESH_TOKEN=' . $data['refresh_token'],
                'MAILER_DSN=smtp+oauth2://your_email@domain.com@' . $endpoints['smtp_host'] . ':' . $endpoints['smtp_port'],
                '```',
                '',
                'Replace your_email@domain.com with your actual email address.',
                '',
                'IMPORTANT: Store your refresh token securely! It doesn\'t expire and allows generating new access tokens.'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to exchange code for tokens: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Handle case when user already has a refresh token
     */
    private function handleRefreshToken(
        SymfonyStyle $io,
        string $clientId,
        string $clientSecret,
        string $refreshToken,
        array $endpoints
    ): int {
        try {
            // Use refresh token to get a new access token
            $response = $this->httpClient->request('POST', $endpoints['token_url'], [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token'
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'])) {
                $io->error('Failed to retrieve access token. Response: ' . json_encode($data));
                return Command::FAILURE;
            }

            $io->success('Successfully retrieved new access token!');

            // Add the refresh token to the data if not present
            if (!isset($data['refresh_token'])) {
                $data['refresh_token'] = $refreshToken;
                $io->note('Using your provided refresh token as it was not returned in the response.');
            }

            $this->displayTokenInfo($io, $data);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to refresh token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display token information in a table
     */
    private function displayTokenInfo(SymfonyStyle $io, array $data): void
    {
        $table = new Table($io);
        $table
            ->setHeaderTitle('OAuth2 Token Information')
            ->setHeaders(['Token Type', 'Value', 'Expiration']);

        // Add access token
        $expiresIn = isset($data['expires_in']) ? $data['expires_in'] . ' seconds' : 'Unknown';
        $table->addRow([
            'Access Token',
            substr($data['access_token'], 0, 10) . '...[redacted for security]',
            $expiresIn
        ]);

        // Add refresh token if available
        if (isset($data['refresh_token'])) {
            $table->addRow([
                'Refresh Token',
                substr($data['refresh_token'], 0, 10) . '...[redacted for security]',
                'Does not expire'
            ]);
        }

        // Add token type if available
        if (isset($data['token_type'])) {
            $table->addRow(['Token Type', $data['token_type'], 'N/A']);
        }

        $table->render();
    }

    /**
     * Get OAuth2 endpoints based on provider
     */
    private function getProviderEndpoints(string $provider): ?array
    {
        $providers = [
            'google' => [
                'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
                'scopes' => 'https://mail.google.com/',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => '587'
            ],
            'microsoft' => [
                'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
                'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
                'redirect_uri' => 'https://login.microsoftonline.com/common/oauth2/nativeclient',
                'scopes' => 'https://outlook.office.com/SMTP.Send',
                'smtp_host' => 'smtp.office365.com',
                'smtp_port' => '587'
            ]
        ];

        return $providers[$provider] ?? null;
    }

    /**
     * Build authorization URL
     */
    private function buildAuthUrl(string $clientId, string $authUrl, string $scopes): string
    {
        return $authUrl . '?' . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
            'scope' => $scopes,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);
    }
}

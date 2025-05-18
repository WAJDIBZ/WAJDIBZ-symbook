<?php

namespace App\Command;

use App\Service\OAuthTokenManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[AsCommand(
    name: 'app:send-oauth-email',
    description: 'Send a test email using OAuth2 authentication',
)]
class SendOAuth2EmailCommand extends Command
{
    private MailerInterface $mailer;
    private string $adminEmail;
    private ?OAuthTokenManager $tokenManager;

    public function __construct(
        MailerInterface $mailer,
        string $adminEmail,
        ?OAuthTokenManager $tokenManager = null
    ) {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->tokenManager = $tokenManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('recipient', InputArgument::OPTIONAL, 'Email recipient', 'wajdibz80@gmail.com')
            ->addOption('token-info', null, InputOption::VALUE_NONE, 'Display token information')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient');

        // Check if token manager is available
        if ($this->tokenManager === null) {
            $io->warning('OAuthTokenManager not available. Email will be sent without OAuth2 authentication.');
        } else {
            $io->info('OAuthTokenManager is available and will be used for OAuth2 authentication.');

            // Display token info if requested
            if ($input->getOption('token-info')) {
                try {
                    $token = $this->tokenManager->getAccessToken();
                    $io->success('Successfully retrieved OAuth2 access token');
                    $io->writeln('Token: ' . substr($token, 0, 10) . '...[redacted]');
                } catch (\Exception $e) {
                    $io->error('Failed to retrieve OAuth2 token: ' . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }

        try {
            $io->info('Preparing to send a test email to: ' . $recipient);
            $email = (new TemplatedEmail())
                ->from($this->adminEmail)
                ->to($recipient)
                ->subject('OAuth2 Test Email from Symfony App')
                ->text('This is a fallback plain text version of the email')
                ->htmlTemplate('emails/oauth2_test_email.html.twig')
                ->context([
                    'name' => 'OAuth2 Test User',
                    'timestamp' => new \DateTime(),
                    'note' => 'This email was sent using automated OAuth2 token refresh'
                ]);

            $io->info('Sending email - using OAuth2 authentication if configured...');
            $this->mailer->send($email);

            $io->success('Test email sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send test email: ' . $e->getMessage());

            // Provide more detailed debugging information
            $io->error('Exception type: ' . get_class($e));
            $io->error('Stack trace:');
            $io->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}

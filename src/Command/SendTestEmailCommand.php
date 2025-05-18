<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Sends a test email to verify mailer configuration',
)]
class SendTestEmailCommand extends Command
{
    private MailerInterface $mailer;
    private string $adminEmail;

    public function __construct(MailerInterface $mailer, string $adminEmail)
    {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        parent::__construct();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $io->info('Preparing to send a test email with OAuth2 authentication...');

            $email = (new TemplatedEmail())
                ->from($this->adminEmail)
                ->to("wajdibz80@gmail.com")
                ->subject('Test Email with OAuth2 from Symfony App')
                ->text('This is a fallback plain text version of the email')
                ->htmlTemplate('emails/test_email.html.twig')
                ->context([
                    'name' => 'Admin',
                    'timestamp' => new \DateTime(),
                    'note' => 'This email was sent using OAuth2 authentication with automatic token refresh'
                ]);

            $io->info('Sending email - OAuth2 tokens will be refreshed if needed...');
            $this->mailer->send($email);

            $io->success('Test email sent successfully with OAuth2 authentication!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send test email: ' . $e->getMessage());
            $io->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

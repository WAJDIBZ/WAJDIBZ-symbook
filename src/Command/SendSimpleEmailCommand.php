<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-simple-email',
    description: 'Sends a simple test email without using templates',
)]
class SendSimpleEmailCommand extends Command
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
            // Debug info
            $io->info("Sending from: " . $this->adminEmail . " to: " . $this->adminEmail);
            $io->info("Current time: " . (new \DateTime())->format('Y-m-d H:i:s'));

            // Create a simple email with both HTML and text content
            $email = (new Email())
                ->from($this->adminEmail)
                ->to("wa2jdi3@gmail.com")
                ->subject(' YAA   000000 ' . time())
                ->text('This is a simple text test email sent at ' . (new \DateTime())->format('Y-m-d H:i:s'))
                ->html('<p>This is a <b>simple HTML test email</b> sent at ' . (new \DateTime())->format('Y-m-d H:i:s') . '</p>');

            $this->mailer->send($email);

            $io->success('Simple test email sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send simple test email: ' . $e->getMessage());
            $io->error('Exception trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

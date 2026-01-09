<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
	name: 'app:send-simple-test-email',
	description: 'Sends a simple test email (dev utility)',
)]
class SendSimpleTestEmailCommand extends Command
{
	public function __construct(
		private readonly MailerInterface $mailer,
		private readonly string $adminEmail,
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		try {
			$email = (new Email())
				->from($this->adminEmail)
				->to($this->adminEmail)
				->subject('Simple test email ' . time())
				->text('Test email sent at ' . (new \DateTimeImmutable())->format('Y-m-d H:i:s'));

			$this->mailer->send($email);

			$io->success('Simple test email sent successfully.');
			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$io->error('Failed to send test email: ' . $e->getMessage());
			return Command::FAILURE;
		}
	}
}


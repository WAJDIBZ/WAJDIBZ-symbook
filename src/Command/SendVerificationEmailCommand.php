<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\MailerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:send-verification-email',
    description: 'Send a verification email to a user',
)]
class SendVerificationEmailCommand extends Command
{
    private UserRepository $userRepository;
    private MailerService $mailerService;
    private TokenGeneratorInterface $tokenGenerator;
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        UserRepository $userRepository,
        MailerService $mailerService,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->userRepository = $userRepository;
        $this->mailerService = $mailerService;
        $this->tokenGenerator = $tokenGenerator;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the user')
            ->addArgument('reset', InputArgument::OPTIONAL, 'Reset verification status (yes/no)', 'no');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $reset = $input->getArgument('reset') === 'yes';

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('No user found with email "%s"', $email));
            return Command::FAILURE;
        }

        if ($reset) {
            $io->info('Resetting verification status for this user');
            $user->setIsVerified(false);
        }

        // Check if user is already verified
        if ($user->isVerified() && !$reset) {
            $io->warning('This user is already verified. Use --reset=yes to reset verification status.');
            return Command::SUCCESS;
        }

        // Generate a new verification token
        $verificationToken = $this->tokenGenerator->generateToken();
        $user->setVerificationToken($verificationToken);
        $this->entityManager->flush();

        $io->info(sprintf('Sending verification email to %s', $email));

        // Send verification email with OAuth2
        try {
            $this->mailerService->sendEmailVerification($user);
            $io->success('Verification email sent successfully!');
            $io->note('Verification URL: ' . $this->getVerificationUrl($user));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send verification email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    private function getVerificationUrl($user): string
    {
        // Generate a proper URL using Symfony's router
        return $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}

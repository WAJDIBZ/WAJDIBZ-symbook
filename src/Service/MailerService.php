<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class MailerService
{
    private $mailer;
    private $adminEmail;
    private $oauthTokenManager;
    private $logger;

    public function __construct(
        MailerInterface $mailer,
        string $adminEmail = 'wajdibz8@gmail.com',
        ?OAuthTokenManager $oauthTokenManager = null,
        ?LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->oauthTokenManager = $oauthTokenManager;
        $this->logger = $logger;
    }
    public function sendRegistrationConfirmationEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Bienvenue sur notre plateforme!')
            ->htmlTemplate('emails/registration_confirmation.html.twig')
            ->context([
                'user' => $user
            ]);

        $this->sendWithOAuth2($email);
    }

    public function sendEmailVerification(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from($this->adminEmail)
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse email')
            ->htmlTemplate('emails/verification_email.html.twig')
            ->context([
                'user' => $user,
                'token' => $user->getVerificationToken()
            ]);

        $this->sendWithOAuth2($email);
    }

    /**
     * Send a generic email with OAuth2 support
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $template Twig template path
     * @param array $context Context variables for the template
     * @param string|null $from From email address (defaults to admin email)
     */
    public function sendEmail(string $to, string $subject, string $template, array $context = [], ?string $from = null): void
    {
        $email = (new TemplatedEmail())
            ->from($from ?? $this->adminEmail)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);
            
        $this->sendWithOAuth2($email);
    }

    /**
     * Send a simple text email with OAuth2 support
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $text Plain text content
     * @param string|null $from From email address (defaults to admin email)
     */
    public function sendSimpleEmail(string $to, string $subject, string $text, ?string $from = null): void
    {
        $email = (new Email())
            ->from($from ?? $this->adminEmail)
            ->to($to)
            ->subject($subject)
            ->text($text);

        $this->sendWithOAuth2($email);
    }

    /**
     * Send an email with automatic OAuth2 token refresh if needed
     */
    private function sendWithOAuth2(Email $email): void
    {
        try {
           
            if ($this->oauthTokenManager) {
             
                $this->oauthTokenManager->getAccessToken();
                if ($this->logger) {
                  
                    $recipients = [];
                    $addresses = $email->getTo();

                    foreach ($addresses as $address) {
                        if ($address instanceof \Symfony\Component\Mime\Address) {
                            $recipients[] = $address->getAddress();
                        } else {
                            $recipients[] = (string)$address;
                        }
                    }

                    $this->logger->info('Using OAuth2 for sending email', [
                        'to' => !empty($recipients) ? implode(', ', $recipients) : 'no recipients',
                        'subject' => $email->getSubject() ?? 'no subject'
                    ]);
                }
            }

            $this->mailer->send($email);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to send email: ' . $e->getMessage());
            }

       
            if ($this->oauthTokenManager) {
                $this->oauthTokenManager->invalidateToken();

                try {
                  
                    if ($this->logger) {
                        $this->logger->info('Retrying with fresh OAuth2 token');
                    }
                    $this->mailer->send($email);
                } catch (\Exception $retryException) {
                    if ($this->logger) {
                        $this->logger->error('Email retry failed: ' . $retryException->getMessage());
                    }
                    throw $retryException;
                }
            } else {
                throw $e;
            }
        }
    }
}

<?php

namespace App\Controller;

use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demo')]
class EmailDemoController extends AbstractController
{
    private MailerService $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    #[Route('/send-oauth-email', name: 'app_demo_send_oauth_email')]
    public function sendOAuthEmail(Request $request): Response
    {
        $sent = false;
        $error = null;
        $recipient = $request->query->get('email', 'wajdibz80@gmail.com');

        if ($request->isMethod('POST')) {
            $recipient = $request->request->get('email', $recipient);

            try {
                // Send email using our OAuth2-enabled mailer service
                $this->mailerService->sendEmail(
                    $recipient,
                    'Test OAuth2 Email from Symfony Demo',
                    'emails/oauth2_test_email.html.twig',
                    [
                        'name' => 'Demo User',
                        'timestamp' => new \DateTime(),
                        'note' => 'This email was sent from the web interface demo'
                    ]
                );

                $this->addFlash('success', "Email successfully sent to $recipient using OAuth2 authentication!");
                $sent = true;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->addFlash('error', "Failed to send email: " . $e->getMessage());
            }
        }

        return $this->render('demo/send_oauth_email.html.twig', [
            'recipient' => $recipient,
            'sent' => $sent,
            'error' => $error
        ]);
    }
}

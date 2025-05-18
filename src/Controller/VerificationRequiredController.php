<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VerificationRequiredController extends AbstractController
{
    #[Route('/email-verification-required/{email}', name: 'app_email_verification_required')]
    public function emailVerificationRequired(string $email): Response
    {
        return $this->render('security/email_not_verified.html.twig', [
            'email' => $email,
        ]);
    }
}

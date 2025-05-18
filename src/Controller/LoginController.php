<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route("/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupération de l'erreur d'authentification
        $error = $authenticationUtils->getLastAuthenticationError();

        // Personnalisation du message d'erreur pour "Invalid credentials"
        if ($error && ($error->getMessageKey() === 'Invalid credentials.' || $error->getMessageKey() === 'Bad credentials.')) {
            $this->addFlash('login_error', "L'adresse e-mail ou le mot de passe est incorrect.");
        }

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route("/logout", name: "app_logout")]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Find the user by verification token
        $user = $userRepository->findOneBy(['verificationToken' => $token]);

        // Check if user exists and token is valid
        if (null === $user) {
            $this->addFlash('error', 'Le lien de vérification est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Mark the user as verified
        $user->setIsVerified(true);
        $user->setVerificationToken(null); // Clear the token after verification
        $entityManager->flush();

        // Flash message & redirect
        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}

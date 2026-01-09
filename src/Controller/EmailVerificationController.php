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

        $user = $userRepository->findOneBy(['verificationToken' => $token]);

       
        if (null === $user) {
            $this->addFlash('error', 'Le lien de vérification est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Marker utlisateur est verifie
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

       
        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}

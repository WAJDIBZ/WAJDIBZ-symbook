<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_reset_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager,
        MailerService $mailerService
    ): Response {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

        
            if ($user) {
                
                $token = $tokenGenerator->generateToken();

             
                $expiration = new \DateTime();
                $expiration->modify('+2 hours');

                $user->setResetToken($token);
                $user->setResetTokenExpiration($expiration);
                $entityManager->flush();

            
                $mailerService->sendEmail(
                    $user->getEmail(),
                    'Réinitialisation de votre mot de passe',
                    'emails/reset_password.html.twig',
                    [
                        'user' => $user,
                        'token' => $token,
                        'expiration' => $expiration
                    ]
                );
            }

          
            $this->addFlash('success', 'Si votre adresse email est associée à un compte, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/mot-de-passe-oublie/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
  
        $user = $userRepository->findOneBy(['resetToken' => $token]);

   
        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_reset_password_request');
        }

     
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
  
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

       
            $user->setMotDePasse($hashedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiration(null);
            $entityManager->flush();

        
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}

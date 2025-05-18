<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use App\Repository\UserRepository;

class RegistrationController extends AbstractController
{
    #[Route("/register/confirmation/{email}", name: "app_registration_confirmation")]
    public function confirmRegistration(string $email): Response
    {
        return $this->render('registration/confirmation.html.twig', [
            'email' => $email
        ]);
    }

    #[Route("/register/resend-verification/{email}", name: "app_resend_verification")]
    public function resendVerificationEmail(
        string $email,
        UserRepository $userRepository,
        MailerService $mailerService,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Adresse email introuvable.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre adresse email est déjà vérifiée. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }        // Generate a new verification token
        $verificationToken = $tokenGenerator->generateToken();
        $user->setVerificationToken($verificationToken);
        $entityManager->flush();

        // OAuth2
        try {
            $mailerService->sendEmailVerification($user);
            $this->addFlash('success', 'Un nouvel email de vérification a été envoyé.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'L\'envoi de l\'email a échoué. Veuillez réessayer plus tard.');

            
        }

        return $this->redirectToRoute('app_registration_confirmation', ['email' => $email]);
    }
    #[Route("/register", name: "app_register")]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
             
                $errorMessages = [];
                foreach ($form->getErrors(true) as $error) {
                    $errorMessages[] = $error->getMessage();
                }

            
                $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez les corriger.');

                foreach ($errorMessages as $message) {
                    $this->addFlash('form_error', $message);
                }

              
                if ($form->get('plainPassword')->get('first')->getErrors()->count() > 0) {
                    $this->addFlash('password_hint', 'Votre mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.');
                }
            }

            if ($form->isValid()) {
              
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                $user->setMotDePasse($hashedPassword);

             
                if (empty($user->getRoles())) {
                    $user->setRoles(['ROLE_USER']);
                }
                // Générer un token de vérification
                $verificationToken = $tokenGenerator->generateToken();
                $user->setVerificationToken($verificationToken);
                $user->setIsVerified(false); 

                $entityManager->persist($user);
                $entityManager->flush();            // Envoyer l'email  OAuth2
                try {
                    $mailerService->sendEmailVerification($user);
                    $this->addFlash('success', 'Votre compte a été créé avec succès. Veuillez vérifier votre boîte mail pour confirmer votre adresse email.');
                } catch (\Exception $e) {
                    // Log the exception
                    $this->addFlash('warning', 'Votre compte a été créé, mais l\'envoi de l\'email de vérification a échoué. Veuillez contacter l\'administrateur.');

                   
                }
                return $this->redirectToRoute('app_registration_confirmation', ['email' => $user->getEmail()]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

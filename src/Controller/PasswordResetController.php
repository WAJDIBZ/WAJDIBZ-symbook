<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user  = $users->findOneBy(['email' => $email]);
            if ($user) {
               
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiration(new \DateTime('+1 hour'));
                $em->flush();

                // Envoi du mail
                $link = $this->generateUrl('app_reset_password', ['token' => $token], true);
                $mailer->send((new Email())
                    ->from('no-reply@monsite.com')
                    ->to($email)
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html($this->renderView('security/forgot_password_email.html.twig', [
                        'username' => $user->getPrenom(),
                        'resetLink' => $link,
                    ]))
                );
            }

            $this->addFlash('success', 'Si cet e-mail existe, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $users->findOneBy(['resetToken' => $token]);
        if (!$user || $user->getResetTokenExpiration() < new \DateTime()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));
            $user->setResetToken(null);
            $user->setResetTokenExpiration(null);
            $em->flush();

            return $this->redirectToRoute('app_reset_password_confirmation');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password-confirmation', name: 'app_reset_password_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('security/reset_password_confirmation.html.twig');
    }
}
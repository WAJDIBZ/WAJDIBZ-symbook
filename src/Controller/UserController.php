<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/users', name: 'app_users')]
    public function index(UserRepository $rep): Response
    {
        // Récupération de tous les utilisateurs depuis le dépôt (correct)
        $users = $rep->findAll();
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/all', name: 'app_users_all')]
    public function showAll(UserRepository $rep, PaginatorInterface $paginator, Request $request): Response
    {
        // Pagination de la liste d'utilisateurs (correct)
        $query = $rep->findAll();
        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );
        return $this->render('user/all.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/create', name: 'app_users_create')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();  // Instanciation d'un nouvel utilisateur (correct)
        $form = $this->createForm(UserType::class, $user, [
            'require_password' => true, // Mot de passe requis en création (correct)
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // Validation du formulaire (correct)
            // Récupérer le mot de passe en clair (correct)
            $plainPassword = $form->get('motDePasse')->getData();
            // Hashage du mot de passe (correct)
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Attribution des rôles depuis le champ non mappé (correct)
            $roleChoice = $form->get('userRole')->getData();
            $user->setRoles($roleChoice === 'admin' ? ['ROLE_ADMIN'] : ['ROLE_USER']);

            $em->persist($user); // Préparation à la persistance (correct)
            $em->flush();        // Écriture en base (correct)

            $this->addFlash('success', 'Utilisateur ajouté avec succès'); // Message flash (correct)
            return $this->redirectToRoute('app_users_all');            // Redirection (correct)
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView(), // Affichage du formulaire (correct)
        ]);
    }

    #[Route('/admin/users/update/{id}', name: 'app_users_update')]
    public function update(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $originalPassword = $user->getPassword(); // Sauvegarde du hash existant (correct)

        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false, // Mot de passe non requis en édition (correct)
        ]);

        // Prérégler le champ userRole selon le rôle actuel (correct)
        $form->get('userRole')->setData(
            in_array('ROLE_ADMIN', $user->getRoles()) ? 'admin' : 'client'
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { // Validation du formulaire (correct)
            // Récupérer le mot de passe en clair (correct)
            $plainPassword = $form->get('motDePasse')->getData();
            if (!empty($plainPassword)) {
                // Hash et mise à jour seulement si un nouveau mot de passe (correct)
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                // Conserver l'ancien hash si aucun nouveau mot de passe (correct)
                $user->setPassword($originalPassword);
            }

            // Mise à jour des rôles (correct)
            $roleChoice = $form->get('userRole')->getData();
            $user->setRoles($roleChoice === 'admin' ? ['ROLE_ADMIN'] : ['ROLE_USER']);

            $em->flush(); // Sauvegarde des modifications (correct)

            $this->addFlash('success', 'Utilisateur mis à jour avec succès'); // Message flash (correct)
            return $this->redirectToRoute('app_users_all');                // Redirection (correct)
        }

        return $this->render('user/update.html.twig', [
            'form' => $form->createView(), // Affichage du formulaire (correct)
            'user' => $user,               // Passe l'utilisateur à la vue (correct)
        ]);
    }

    #[Route('/admin/users/show/{id}', name: 'app_users_show')]
    public function show(User $user): Response
    {
        return $this->render('user/detail.html.twig', [
            'user' => $user, // Affichage du détail (correct)
        ]);
    }

    #[Route('/admin/users/delete/{id}', name: 'app_users_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user); // Suppression (correct)
        $em->flush();       // Persistence 

        $this->addFlash('success', 'Utilisateur supprimé avec succès'); // Message flash (correct)
        return $this->redirectToRoute('app_users_all');                 // Redirection (correct)
    }

    #[Route('/admin/users/admins', name: 'app_users_admins')]
    public function listAdmins(UserRepository $repository): Response
    {
        $admins = $repository->findAdmins(); // Méthode personnalisée (correct)
        return $this->render('user/admins.html.twig', [
            'users' => $admins, // Affiche les admins (correct)
        ]);
    }

    #[Route('/admin/users/clients', name: 'app_users_clients')]
    public function listClients(UserRepository $repository): Response
    {
        $clients = $repository->findClients(); // Méthode personnalisée (correct)
        return $this->render('user/clients.html.twig', [
            'users' => $clients, // Affiche les clients (correct)
        ]);
    }

    #[Route('/mes-commandes', name: 'app_mes_commandes')]
    public function mesCommandes(PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }
        $commandes = $user->getCommandes()->toArray();
        $pagination = $paginator->paginate(
            $commandes,
            $request->query->getInt('page', 1),
            5
        );
        return $this->render('commande/mes_commandes.html.twig', [
            'commandes' => $pagination,
        ]);
    }

    #[Route('/mes-commandes/{id}', name: 'app_mes_commande_show1')]
    public function showMesCommande(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $commande = $em->getRepository(\App\Entity\Commande::class)->find($id);
        $user = $this->getUser();
        if (!$commande || $commande->getUser() !== $user) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }
        return $this->render('commande/detail_user.html.twig', [
            'commande' => $commande,
        ]);
    }
}

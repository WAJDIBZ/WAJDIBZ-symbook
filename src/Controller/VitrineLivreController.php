<?php

namespace App\Controller;

use App\Entity\Livres;
use App\Repository\LivresRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Commande;
use App\Entity\ArticleCommande;
use App\Entity\User;

class VitrineLivreController extends AbstractController
{
    #[Route('/boutique/livres', name: 'app_boutique_livres')]
    public function index(
        LivresRepository $livresRepository,
        CategorieRepository $categorieRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie');

        // Préparer la requête
        $query = $livresRepository->createQueryBuilder('l');

        // Appliquer le filtre de recherche par titre
        if ($search) {
            $query->andWhere('l.titre LIKE :search OR l.editeur LIKE :search OR l.resume LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Appliquer le filtre par catégorie
        if ($categorieId) {
            $query->andWhere('l.Categorie = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        // Paginer les résultats avec KnpPaginator et gestion du tri
        $livres = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12, // Nombre de livres par page
            [
                'defaultSortFieldName' => 'l.titre',
                'defaultSortDirection' => 'asc',
                'sortFieldWhitelist' => ['l.titre', 'l.prix', 'l.dateEdition']
            ]
        );

        // Récupérer toutes les catégories pour le filtre
        $categories = $categorieRepository->findAll();

        return $this->render('vitrine/livres/index.html.twig', [
            'livres' => $livres,
            'categories' => $categories,
            'currentSearch' => $search,
            'currentCategorie' => $categorieId
        ]);
    }


    #[Route('/boutique/livre/{id}', name: 'app_boutique_livre_show')]
    public function show(Livres $livre): Response
    {
        return $this->render('vitrine/livres/show.html.twig', [
            'livre' => $livre
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter')]
    public function ajouterAuPanier(
        Livres $livre,
        Request $request,
        SessionInterface $session
    ): Response {
        // Récupérer le panier actuel de la session
        $panier = $session->get('panier', []);

        // Récupérer la quantité depuis le formulaire
        $quantite = $request->request->get('quantite', 1);

        // Si le livre est déjà dans le panier, augmenter la quantité
        if (isset($panier[$livre->getId()])) {
            $panier[$livre->getId()]['quantite'] += $quantite;
        } else {
            // Sinon, ajouter le livre au panier
            $panier[$livre->getId()] = [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'prix' => $livre->getPrix(),
                'image' => $livre->getImage(),
                'quantite' => $quantite
            ];
        }

        // Sauvegarder le panier dans la session
        $session->set('panier', $panier);

        $this->addFlash('success', 'Le livre a été ajouté au panier');

        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_boutique_livres'));
    }

    #[Route('/panier', name: 'app_panier')]
    public function panier(SessionInterface $session): Response
    {
        // le panier
        $panier = $session->get('panier', []);

        // total
        $total = 0;
        foreach ($panier as $item) {
            $total += $item['prix'] * $item['quantite'];
        }

        return $this->render('vitrine/panier/index.html.twig', [
            'items' => $panier,
            'total' => $total
        ]);
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer')]
    public function supprimerDuPanier(int $id, SessionInterface $session): Response
    {
        // Récupérer le panier
        $panier = $session->get('panier', []);

        // Supprimer l'élément s'il existe
        if (isset($panier[$id])) {
            unset($panier[$id]);
        }

        // Mettre à jour le panier
        $session->set('panier', $panier);

        $this->addFlash('success', 'Le livre a été supprimé du panier');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/mettre-a-jour/{id}', name: 'app_panier_maj', methods: ['POST'])]
    public function mettreAJourQuantite(
        int $id,
        Request $request,
        SessionInterface $session
    ): Response {
        // Récupérer le panier
        $panier = $session->get('panier', []);

        // Récupérer la nouvelle quantité
        $quantite = $request->request->get('quantite', 1);

        // Mettre à jour la quantité si l'élément existe
        if (isset($panier[$id])) {
            $panier[$id]['quantite'] = max(1, (int)$quantite);
        }

        // Mettre à jour le panier
        $session->set('panier', $panier);

        $this->addFlash('success', 'La quantité a été mise à jour');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/vider', name: 'app_panier_vider')]
    public function viderPanier(SessionInterface $session): Response
    {
        // Supprimer le panier
        $session->remove('panier');

        $this->addFlash('success', 'Le panier a été vidé');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/create-checkout-session', name: 'panier_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, SessionInterface $session, LivresRepository $livresRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $panier = $session->get('panier', []);
        $elements = [];
        foreach ($panier as $item) {
            $elements[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['titre'],
                    ],
                    'unit_amount' => (int)($item['prix'] * 100),
                ],
                'quantity' => $item['quantite'],
            ];
        }

        if (empty($elements)) {
            return new JsonResponse(['error' => 'Panier vide'], Response::HTTP_BAD_REQUEST);
        }

        Stripe::setApiKey('sk_test_51QfUHZRxEehTOYAclh7XUpX6XG2rSBN3sSNU3PJ9IxLUSjqDE6njEuAefU6oiX9dgWf3QoS6R13gBzO1IodyimkN00kXWDJCLA');

        try {
            $checkout_session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => $elements,
                'mode' => 'payment',
                'success_url' => $request->getSchemeAndHttpHost() . $this->generateUrl('panier_stripe_success'),
                'cancel_url' => $request->getSchemeAndHttpHost() . $this->generateUrl('app_panier'),
                'client_reference_id' => $user->getId(),
                'metadata' => [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail()
                ]
            ]);

            return new JsonResponse(['id' => $checkout_session->id]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/panier/stripe-success', name: 'panier_stripe_success')]
    public function stripeSuccess(SessionInterface $session, EntityManagerInterface $em, LivresRepository $livresRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $panier = $session->get('panier', []);
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier');
        }

        $commande = new Commande();
        $commande->setUser($user);
        $commande->setDateCommande(new \DateTimeImmutable());
        $commande->setStatut('payée');
        $total = 0;

        foreach ($panier as $item) {
            $livre = $livresRepository->find($item['id']);
            if ($livre) {
                $article = new ArticleCommande();
                $article->setLivre($livre);
                $article->setQuantite($item['quantite']);
                $article->setPrix($item['prix']);
                $article->setCommande($commande);
                $em->persist($article);
                $commande->addArticleCommande($article);
                $total += $item['prix'] * $item['quantite'];
            }
        }
        $commande->setMontantTotal($total);
        $em->persist($commande);
        $em->flush();

        $session->remove('panier');
        $this->addFlash('success', 'Votre commande a été enregistrée et payée avec succès.');
        return $this->redirectToRoute('app_panier');
    }
}

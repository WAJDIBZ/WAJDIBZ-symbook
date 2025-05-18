<?php

namespace App\EventSubscriber;

use App\Repository\CategorieRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalVariablesSubscriber implements EventSubscriberInterface
{
    private CategorieRepository $categorieRepository;
    private Environment $twig;

    public function __construct(CategorieRepository $categorieRepository, Environment $twig)
    {
        $this->categorieRepository = $categorieRepository;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // N'exécutez pas cette logique pour toutes les sous-requêtes
        if (!$event->isMainRequest()) {
            return;
        }

        // Récupérer toutes les catégories et les ajouter comme variable globale Twig
        $this->twig->addGlobal('categories', $this->categorieRepository->findAll());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}

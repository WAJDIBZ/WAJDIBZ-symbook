<?php

namespace App\Controller\Admin;

use App\Repository\ArticleCommandeRepository;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        Request $request,
        ArticleCommandeRepository $acRepo,
        CommandeRepository $cRepo
    ): Response {
        // Set default time period and allow filter
        $to = new \DateTimeImmutable('now');
        $period = $request->query->get('period', '30');

        $from = match ($period) {
            '7' => $to->modify('-7 days'),
            '90' => $to->modify('-90 days'),
            '365' => $to->modify('-365 days'),
            default => $to->modify('-30 days'),
        };

        // Get best selling book
        $best = $acRepo->findBestSellingBook($from, $to);

        // Get top 5 selling books
        $topBooks = $acRepo->findTopSellingBooks($from, $to, 5);

        // Prepare top books chart data
        $topBooksChart = [['Livre', 'Quantité vendue', ['role' => 'style']]];
        $colors = ['#B794F4', '#9F7AEA', '#805AD5', '#6B46C1', '#553C9A'];
        foreach ($topBooks as $index => $book) {
            $topBooksChart[] = [
                $book['titre'],
                (int) $book['totalVendu'],
                $colors[$index % count($colors)]
            ];
        }

        // Get orders data
        $raw = $cRepo->countOrdersPerDay($from, $to);
        $orderStats = $cRepo->getOrderStatsByPeriod($from, $to);

        // Prepare orders chart data
        $ordersChart = [['Date', 'Commandes', 'Revenu (DT)']];
        foreach ($raw as $row) {
            $date = sprintf('%04d-%02d-%02d', $row['year'], $row['month'], $row['day']);
            $ordersChart[] = [
                $date,
                (int) $row['nbCommandes'],
                (float) $row['totalRevenue']
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'bestBook'      => $best,
            'topBooks'      => $topBooks,
            'ordersChart'   => json_encode($ordersChart),
            'topBooksChart' => json_encode($topBooksChart),
            'orderStats'    => $orderStats,
            'periodFrom'    => $from->format('Y-m-d'),
            'periodTo'      => $to->format('Y-m-d'),
            'activePeriod'  => $period,
        ]);
    }
}

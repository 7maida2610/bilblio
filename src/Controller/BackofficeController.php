<?php

namespace App\Controller;

use App\Repository\AuteurRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditeurRepository;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BackofficeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_backoffice_dashboard');
    }

    #[Route('/backoffice', name: 'app_backoffice_dashboard')]
    public function dashboard(
        LivreRepository $livreRepository,
        AuteurRepository $auteurRepository,
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository
    ): Response {
        $livres = $livreRepository->findAll();
        $auteurs = $auteurRepository->findAll();
        $categories = $categorieRepository->findAll();
        $editeurs = $editeurRepository->findAll();

        // Real chart data - books by category
        $chartData = [];
        foreach ($categories as $categorie) {
            $count = count($categorie->getLivres());
            if ($count > 0) { // Only include categories with books
                $chartData[] = [
                    'label' => $categorie->getDesignation(),
                    'count' => $count,
                ];
            }
        }

        // Real monthly book additions data
        $monthlyData = [];
        $currentYear = (int) date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTime("$currentYear-$month-01");
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');

            $count = $livreRepository->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.dateEdition BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->getQuery()
                ->getSingleScalarResult();

            $monthlyData[] = (int) $count;
        }

        // Additional statistics
        $totalBooksValue = array_reduce($livres, function($sum, $livre) {
            return $sum + ($livre->getPrix() * $livre->getNbExemplaires());
        }, 0);

        $averageBookPrice = count($livres) > 0 ? array_sum(array_map(fn($l) => $l->getPrix(), $livres)) / count($livres) : 0;

        $booksByAuthor = [];
        foreach ($auteurs as $auteur) {
            $booksByAuthor[] = [
                'author' => $auteur->getPrenom() . ' ' . $auteur->getNom(),
                'count' => count($auteur->getLivres()),
            ];
        }
        usort($booksByAuthor, fn($a, $b) => $b['count'] <=> $a['count']);

        return $this->render('backoffice/dashboard.html.twig', [
            'livres' => $livres,
            'auteurs' => $auteurs,
            'categories' => $categories,
            'editeurs' => $editeurs,
            'derniersLivres' => $livreRepository->findBy([], ['dateEdition' => 'DESC'], 5),
            'chartData' => $chartData,
            'monthlyData' => $monthlyData,
            'totalBooksValue' => $totalBooksValue,
            'averageBookPrice' => $averageBookPrice,
            'booksByAuthor' => array_slice($booksByAuthor, 0, 5), // Top 5 authors
        ]);
    }

    #[Route('/backoffice/liver', name: 'app_backoffice_liver')]
    public function liver(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
            'search' => '',
        ]);
    }

    #[Route('/liver', name: 'app_liver')]
    public function liverAlt(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/index.html.twig', [
            'livres' => $livreRepository->findAll(),
        ]);
    }
}
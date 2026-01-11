<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Repository\AuteurRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditeurRepository;
use App\Repository\LivreRepository;
use App\Repository\ReadingGoalRepository;
use App\Repository\ReadingProgressRepository;
use App\Repository\ReviewRepository;
use App\Service\ReadingStreakService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_home')]
    public function home(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin');
        }

        if ($this->isGranted('ROLE_MODERATOR')) {
            return $this->redirectToRoute('app_backoffice_dashboard');
        }

        return $this->redirectToRoute('app_backoffice_dashboard');
    }

    #[Route('/backoffice', name: 'app_backoffice_dashboard')]
    public function backofficeDashboard(
        LivreRepository $livreRepository,
        AuteurRepository $auteurRepository,
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository,
        ReadingProgressRepository $readingProgressRepository,
        ReviewRepository $reviewRepository,
        ReadingGoalRepository $readingGoalRepository,
        ReadingStreakService $readingStreakService,
        EntityManagerInterface $entityManager
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

        // User-specific data
        $user = $this->getUser();
        $userReadingProgress = $readingProgressRepository->findRecentlyRead($user, 5);
        $userCompletedBooks = $readingProgressRepository->findCompletedBooks($user);
        $pendingReviews = $reviewRepository->findPendingReviews($user);
        $userGoals = $readingGoalRepository->findActiveGoals($user);

        // Calculate overall progress
        $totalBooks = count($user->getPurchasedBooks());
        $completedBooksCount = count($userCompletedBooks);
        $overallProgress = $totalBooks > 0 ? ($completedBooksCount / $totalBooks) * 100 : 0;

        // Update and get reading goals data
        $booksThisYearGoal = $readingGoalRepository->findByType($user, 'books_year');
        $pagesThisMonthGoal = $readingGoalRepository->findByType($user, 'pages_month');

        // Update goal progress if goals exist
        if ($booksThisYearGoal) {
            $booksThisYearGoal->setCurrentValue($completedBooksCount);
            $entityManager->flush();
        }

        // For pages goal, we would need to track pages read per book
        // For now, we'll leave it as is since we don't have page tracking yet
        // This could be enhanced later with page tracking per book

        // Reading streak data
        $streakStats = $readingStreakService->getStreakStats($user);
        $achievements = $readingStreakService->getAchievements($user);
        $motivationalMessage = $readingStreakService->getMotivationalMessage($user);

        // Cart data
        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        $cartItemsCount = $cart ? $cart->getTotalItems() : 0;

        // Active loans count
        $activeLoansCount = count($user->getLoans()->filter(function($loan) {
            return in_array($loan->getStatus(), ['approved', 'active']);
        }));

        $dashboardData = [
            'user' => $user,
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
            // User-specific data
            'userReadingProgress' => $userReadingProgress,
            'userCompletedBooks' => $userCompletedBooks,
            'pendingReviews' => $pendingReviews,
            'userGoals' => $userGoals,
            'overallProgress' => $overallProgress,
            'myBooksCount' => $totalBooks,
            'favoriteAuthorsCount' => count($user->getFavoriteAuthors()),
            'pendingReviewsCount' => count($pendingReviews),
            'booksThisYearGoal' => $booksThisYearGoal,
            'pagesThisMonthGoal' => $pagesThisMonthGoal,
            // Reading streak data
            'streakStats' => $streakStats,
            'achievements' => $achievements,
            'motivationalMessage' => $motivationalMessage,
            // Cart data
            'cartItemsCount' => $cartItemsCount,
            // Active loans count
            'activeLoansCount' => $activeLoansCount,
        ];

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('dashboard/admin.html.twig', $dashboardData);
        }

        if ($this->isGranted('ROLE_MODERATOR')) {
            return $this->render('dashboard/moderator.html.twig', $dashboardData);
        }

        return $this->render('dashboard/user.html.twig', $dashboardData);
    }
}
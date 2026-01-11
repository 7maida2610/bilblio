<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Widget\StatsOverviewWidget;
use Symfony\Bundle\SecurityBundle\Security;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function index(): Response
    {
        // Basic entity counts
        $livreCount = $this->entityManager->getRepository(\App\Entity\Livre::class)->count([]);
        $auteurCount = $this->entityManager->getRepository(\App\Entity\Auteur::class)->count([]);
        $categorieCount = $this->entityManager->getRepository(\App\Entity\Categorie::class)->count([]);
        $editeurCount = $this->entityManager->getRepository(\App\Entity\Editeur::class)->count([]);
        $userCount = $this->entityManager->getRepository(\App\Entity\User::class)->count([]);
        $activityLogCount = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)->count([]);

        // Order statistics
        $orderCount = $this->entityManager->getRepository(\App\Entity\Order::class)->count([]);
        $pendingOrders = $this->entityManager->getRepository(\App\Entity\Order::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $shippedOrders = $this->entityManager->getRepository(\App\Entity\Order::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', 'shipped')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $totalRevenue = $this->entityManager->getRepository(\App\Entity\Order::class)
            ->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', ['paid', 'shipped', 'delivered'])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Loan statistics
        $loanCount = $this->entityManager->getRepository(\App\Entity\Loan::class)->count([]);
        $activeLoans = $this->entityManager->getRepository(\App\Entity\Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $requestedLoans = $this->entityManager->getRepository(\App\Entity\Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', 'requested')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $approvedLoans = $this->entityManager->getRepository(\App\Entity\Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $overdueLoans = $this->entityManager->getRepository(\App\Entity\Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status AND l.dueDate < :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $returnedLoans = $this->entityManager->getRepository(\App\Entity\Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.status = :status')
            ->setParameter('status', 'returned')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Reservation statistics
        $reservationCount = $this->entityManager->getRepository(\App\Entity\BookReservation::class)->count([]);
        $activeReservations = $this->entityManager->getRepository(\App\Entity\BookReservation::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isActive = true')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $notifiedReservations = $this->entityManager->getRepository(\App\Entity\BookReservation::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.notifiedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Review statistics
        $reviewCount = $this->entityManager->getRepository(\App\Entity\Review::class)->count([]);
        $avgRating = $this->entityManager->getRepository(\App\Entity\Review::class)
            ->createQueryBuilder('r')
            ->select('AVG(r.rating)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Financial calculations
        $totalValue = $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->createQueryBuilder('l')
            ->select('SUM(l.prix * l.nbExemplaires)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $avgBookPrice = $livreCount > 0 ? $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->createQueryBuilder('l')
            ->select('AVG(l.prix)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0 : 0;

        // Stock statistics
        $totalStock = $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->createQueryBuilder('l')
            ->select('SUM(l.nbExemplaires)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $outOfStockCount = $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.nbExemplaires = 0')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // User statistics
        $activeUsers = $this->entityManager->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = true')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $adminUsers = $this->entityManager->getRepository(\App\Entity\User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Recent data
        $recentLivres = $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        $recentUsers = $this->entityManager->getRepository(\App\Entity\User::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        $recentActivity = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
            ->findBy([], ['createdAt' => 'DESC'], 10);

        // Category distribution for chart
        $categoryStats = $this->entityManager->getRepository(\App\Entity\Categorie::class)
            ->createQueryBuilder('c')
            ->select('c.designation, COUNT(l.id) as livreCount')
            ->leftJoin('c.livres', 'l')
            ->groupBy('c.id')
            ->orderBy('livreCount', 'DESC')
            ->getQuery()
            ->getResult();

        // Ensure we have at least some test data for charts
        if (empty($categoryStats)) {
            $categoryStats = [
                ['designation' => 'Fiction', 'livreCount' => 5],
                ['designation' => 'Science', 'livreCount' => 3],
                ['designation' => 'History', 'livreCount' => 2],
                ['designation' => 'Biography', 'livreCount' => 1]
            ];
        }

        // Monthly activity for chart (last 12 months)
        $monthlyActivity = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $startOfMonth = $date->format('Y-m-01 00:00:00');
            $endOfMonth = $date->format('Y-m-t 23:59:59');

            $count = $this->entityManager->getRepository(\App\Entity\ActivityLog::class)
                ->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $startOfMonth)
                ->setParameter('end', $endOfMonth)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            $monthlyActivity[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }

        // Ensure we have at least some test data for activity chart
        if (array_sum(array_column($monthlyActivity, 'count')) === 0) {
            $monthlyActivity = array_map(function($item, $index) {
                return [
                    'month' => $item['month'],
                    'count' => rand(5, 25) // Random test data
                ];
            }, $monthlyActivity, array_keys($monthlyActivity));
        }

        return $this->render('admin/index.html.twig', [
            // Basic counts
            'livreCount' => $livreCount,
            'auteurCount' => $auteurCount,
            'categorieCount' => $categorieCount,
            'editeurCount' => $editeurCount,
            'userCount' => $userCount,
            'activityLogCount' => $activityLogCount,

            // Order data
            'orderCount' => $orderCount,
            'pendingOrders' => $pendingOrders,
            'shippedOrders' => $shippedOrders,
            'totalRevenue' => $totalRevenue,

            // Loan data
            'loanCount' => $loanCount,
            'requestedLoans' => $requestedLoans,
            'approvedLoans' => $approvedLoans,
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
            'returnedLoans' => $returnedLoans,

            // Reservation data
            'reservationCount' => $reservationCount,
            'activeReservations' => $activeReservations,
            'notifiedReservations' => $notifiedReservations,

            // Review data
            'reviewCount' => $reviewCount,
            'avgRating' => $avgRating,

            // Financial data
            'totalValue' => $totalValue,
            'avgBookPrice' => $avgBookPrice,

            // Stock data
            'totalStock' => $totalStock,
            'outOfStockCount' => $outOfStockCount,

            // User data
            'activeUsers' => $activeUsers,
            'adminUsers' => $adminUsers,

            // Recent data
            'recentLivres' => $recentLivres,
            'recentUsers' => $recentUsers,
            'recentActivity' => $recentActivity,

            // Chart data
            'categoryStats' => $categoryStats,
            'monthlyActivity' => $monthlyActivity,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Biblio - Administration')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->renderContentMaximized()
            ->generateRelativeUrls();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css')
            ->addCssFile('https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i')
            ->addJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js');
    }

    public function configureMenuItems(): iterable
    {
        // Get current user
        $user = $this->getUser();
        $hasAdminRole = $user && in_array('ROLE_ADMIN', $user->getRoles());
        
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Content Management (ADMIN only)
        if ($hasAdminRole) {
            yield MenuItem::subMenu('Gestion du Contenu', 'fas fa-book-open')->setSubItems([
                MenuItem::linkToCrud('Livres', 'fas fa-book', \App\Entity\Livre::class),
                MenuItem::linkToCrud('Auteurs', 'fas fa-user', \App\Entity\Auteur::class),
                MenuItem::linkToCrud('Catégories', 'fas fa-tag', \App\Entity\Categorie::class),
                MenuItem::linkToCrud('Éditeurs', 'fas fa-building', \App\Entity\Editeur::class),
            ]);
        }

        // E-commerce Management
        yield MenuItem::subMenu('E-commerce', 'fas fa-shopping-cart')->setSubItems([
            MenuItem::linkToCrud('Commandes', 'fas fa-shopping-bag', \App\Entity\Order::class),
            MenuItem::linkToCrud('Articles de Commande', 'fas fa-list', \App\Entity\OrderItem::class),
            MenuItem::linkToCrud('Paniers', 'fas fa-cart-plus', \App\Entity\Cart::class),
            MenuItem::linkToCrud('Articles du Panier', 'fas fa-cart-arrow-down', \App\Entity\CartItem::class),
        ]);

        // Library Services (ADMIN only - for loan and reservation management)
        if ($hasAdminRole) {
            yield MenuItem::subMenu('Services Bibliotheque', 'fas fa-university')->setSubItems([
                MenuItem::linkToCrud('Emprunts', 'fas fa-book-reader', \App\Entity\Loan::class),
                MenuItem::linkToCrud('Reservations', 'fas fa-calendar', \App\Entity\BookReservation::class),
                MenuItem::linkToCrud('Progressions de Lecture', 'fas fa-chart-line', \App\Entity\ReadingProgress::class),
                MenuItem::linkToCrud('Objectifs de Lecture', 'fas fa-bullseye', \App\Entity\ReadingGoal::class),
                MenuItem::linkToCrud('Avis', 'fas fa-star', \App\Entity\Review::class),
            ]);
        }

        // User Management (ADMIN only)
        if ($hasAdminRole) {
            yield MenuItem::subMenu('Gestion Utilisateurs', 'fas fa-users')->setSubItems([
                MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', \App\Entity\User::class),
                MenuItem::linkToCrud('Logs d\'Activité', 'fas fa-history', \App\Entity\ActivityLog::class),
            ]);
        }

        // Banner Management (ADMIN only)
        if ($hasAdminRole) {
            yield MenuItem::subMenu('Gestion du Site', 'fas fa-cog')->setSubItems([
                MenuItem::linkToCrud('Bannières', 'fas fa-flag', \App\Entity\Banner::class),
            ]);
        }
    }
}

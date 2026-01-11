<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Loan;
use App\Entity\Order;
use App\Entity\BookReservation;
use App\Repository\UserRepository;
use App\Repository\LoanRepository;
use App\Repository\OrderRepository;
use App\Repository\BookReservationRepository;
use App\Service\EmailServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\KernelInterface;

#[Route('/backoffice/emails')]
final class EmailController extends AbstractController
{
    public function __construct(
        private EmailServiceInterface $emailService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private OrderRepository $orderRepository,
        private BookReservationRepository $reservationRepository,
        private KernelInterface $kernel
    ) {}

    #[Route('', name: 'backoffice_emails_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get some stats for the dashboard
        $totalUsers = $this->userRepository->count([]);
        $verifiedUsers = $this->userRepository->count(['isVerified' => true]);
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');
        
        // Get loan, order, and reservation stats
        $totalLoans = $this->loanRepository->count([]);
        $totalOrders = $this->orderRepository->count([]);
        $totalReservations = $this->reservationRepository->count([]);

        // Get loans due soon (within 3 days) and overdue
        $today = new \DateTimeImmutable('today');
        $threeDaysFromNow = new \DateTimeImmutable('+3 days');
        
        $loansDueSoon = $this->loanRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.dueDate <= :dueDate')
            ->andWhere('l.dueDate >= :today')
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->setParameter('dueDate', $threeDaysFromNow)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $overdueLoans = $this->loanRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.dueDate < :today')
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        return $this->render('emails/index.html.twig', [
            'totalUsers' => $totalUsers,
            'verifiedUsers' => $verifiedUsers,
            'adminCount' => count($admins),
            'totalLoans' => $totalLoans,
            'totalOrders' => $totalOrders,
            'totalReservations' => $totalReservations,
            'loansDueSoon' => $loansDueSoon,
            'overdueLoans' => $overdueLoans,
        ]);
    }

    #[Route('/send-verification/{userId}', name: 'backoffice_send_verification')]
    public function sendVerification(int $userId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        if ($user->isVerified()) {
            $this->addFlash('warning', 'User is already verified.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        try {
            // Generate new verification token
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $this->entityManager->flush();

            $this->emailService->sendVerificationEmail($user);
            $this->addFlash('success', 'Verification email sent to ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send verification email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/send-weekly-summaries', name: 'backoffice_send_weekly_summaries')]
    public function sendWeeklySummaries(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->emailService->sendWeeklySummaries();
            $this->addFlash('success', 'Weekly summaries sent to all verified users.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send weekly summaries: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/send-recommendations', name: 'backoffice_send_recommendations')]
    public function sendRecommendations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->emailService->sendBookRecommendations();
            $this->addFlash('success', 'Book recommendations sent to all verified users.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send recommendations: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/send-low-stock-alert', name: 'backoffice_send_low_stock_alert')]
    public function sendLowStockAlert(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get actual low stock books from database
        $lowStockBooks = $this->entityManager->getRepository(\App\Entity\Livre::class)
            ->createQueryBuilder('l')
            ->where('l.nbExemplaires <= 5')
            ->andWhere('l.nbExemplaires > 0')
            ->orderBy('l.nbExemplaires', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($lowStockBooks)) {
            $this->addFlash('info', 'No books are currently low in stock.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        $lowStockData = array_map(function($book) {
            return [
                'title' => $book->getTitre(),
                'author' => $book->getAuteur() ? $book->getAuteur()->getNom() : 'Unknown',
                'stock' => $book->getNbExemplaires(),
            ];
        }, $lowStockBooks);

        try {
            $this->emailService->sendLowStockAlert($lowStockData);
            $this->addFlash('success', 'Low stock alert sent to all administrators for ' . count($lowStockBooks) . ' books.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send low stock alert: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/test-goal-achievement/{userId}', name: 'backoffice_test_goal_achievement')]
    public function testGoalAchievement(int $userId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        // Mock goal achievement data for testing
        $achievedGoals = [
            [
                'type' => 'books_per_year',
                'target' => 20,
                'achieved' => 25,
                'period' => 'year'
            ],
            [
                'type' => 'pages_per_month',
                'target' => 1000,
                'achieved' => 1200,
                'period' => 'month'
            ]
        ];

        try {
            $this->emailService->sendReadingGoalAchievedEmail($user, $achievedGoals);
            $this->addFlash('success', 'Goal achievement email sent to ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send goal achievement email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/test-weekly-summary/{userId}', name: 'backoffice_test_weekly_summary')]
    public function testWeeklySummary(int $userId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        // Get real weekly stats for the user
        $stats = $this->emailService->calculateWeeklyStats($user);

        try {
            $this->emailService->sendWeeklyReadingSummary($user, $stats);
            $this->addFlash('success', 'Weekly summary email sent to ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send weekly summary email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/test-recommendations/{userId}', name: 'backoffice_test_recommendations')]
    public function testRecommendations(int $userId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        // Get real recommendations for the user
        $recommendations = $this->emailService->generateRecommendations($user);

        if (empty($recommendations)) {
            $this->addFlash('warning', 'No recommendations available for this user.');
            return $this->redirectToRoute('backoffice_emails_index');
        }

        try {
            $this->emailService->sendBookRecommendation($user, $recommendations);
            $this->addFlash('success', 'Book recommendations sent to ' . $user->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send recommendations: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/users', name: 'backoffice_emails_users')]
    public function users(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $users = $this->userRepository->findBy([], ['id' => 'DESC'], $limit, $offset);
        $totalUsers = $this->userRepository->count([]);

        return $this->render('emails/users.html.twig', [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => ceil($totalUsers / $limit),
            'totalUsers' => $totalUsers,
        ]);
    }

    // ==================== LOAN EMAIL TESTING ====================

    #[Route('/loans', name: 'backoffice_emails_loans')]
    public function loans(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $loans = $this->loanRepository->findBy([], ['requestedAt' => 'DESC'], $limit, $offset);
        $totalLoans = $this->loanRepository->count([]);

        return $this->render('emails/loans.html.twig', [
            'loans' => $loans,
            'currentPage' => $page,
            'totalPages' => ceil($totalLoans / $limit),
            'totalLoans' => $totalLoans,
        ]);
    }

    #[Route('/test-loan-approved/{loanId}', name: 'backoffice_test_loan_approved')]
    public function testLoanApproved(int $loanId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $loan = $this->loanRepository->find($loanId);

        if (!$loan) {
            $this->addFlash('error', 'Loan not found.');
            return $this->redirectToRoute('backoffice_emails_loans');
        }

        try {
            $this->emailService->sendLoanApprovedEmail($loan);
            $this->addFlash('success', 'Loan approved email sent to ' . $loan->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send loan approved email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_loans');
    }

    #[Route('/test-loan-rejected/{loanId}', name: 'backoffice_test_loan_rejected')]
    public function testLoanRejected(int $loanId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $loan = $this->loanRepository->find($loanId);

        if (!$loan) {
            $this->addFlash('error', 'Loan not found.');
            return $this->redirectToRoute('backoffice_emails_loans');
        }

        try {
            $this->emailService->sendLoanRejectedEmail($loan, 'Book already checked out');
            $this->addFlash('success', 'Loan rejected email sent to ' . $loan->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send loan rejected email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_loans');
    }

    #[Route('/test-loan-overdue/{loanId}', name: 'backoffice_test_loan_overdue')]
    public function testLoanOverdue(int $loanId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $loan = $this->loanRepository->find($loanId);

        if (!$loan) {
            $this->addFlash('error', 'Loan not found.');
            return $this->redirectToRoute('backoffice_emails_loans');
        }

        try {
            $this->emailService->sendLoanOverdueEmail($loan);
            $this->addFlash('success', 'Loan overdue email sent to ' . $loan->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send loan overdue email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_loans');
    }

    // ==================== ORDER EMAIL TESTING ====================

    #[Route('/orders', name: 'backoffice_emails_orders')]
    public function orders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $orders = $this->orderRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $totalOrders = $this->orderRepository->count([]);

        return $this->render('emails/orders.html.twig', [
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => ceil($totalOrders / $limit),
            'totalOrders' => $totalOrders,
        ]);
    }

    #[Route('/test-order-shipped/{orderId}', name: 'backoffice_test_order_shipped')]
    public function testOrderShipped(int $orderId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('backoffice_emails_orders');
        }

        try {
            $this->emailService->sendOrderShippedEmail($order);
            $this->addFlash('success', 'Order shipped email sent to ' . $order->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send order shipped email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_orders');
    }

    #[Route('/test-order-delivered/{orderId}', name: 'backoffice_test_order_delivered')]
    public function testOrderDelivered(int $orderId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('backoffice_emails_orders');
        }

        try {
            $this->emailService->sendOrderDeliveredEmail($order);
            $this->addFlash('success', 'Order delivered email sent to ' . $order->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send order delivered email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_orders');
    }

    // ==================== RESERVATION EMAIL TESTING ====================

    #[Route('/reservations', name: 'backoffice_emails_reservations')]
    public function reservations(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $reservations = $this->reservationRepository->findBy([], ['requestedAt' => 'DESC'], $limit, $offset);
        $totalReservations = $this->reservationRepository->count([]);

        return $this->render('emails/reservations.html.twig', [
            'reservations' => $reservations,
            'currentPage' => $page,
            'totalPages' => ceil($totalReservations / $limit),
            'totalReservations' => $totalReservations,
        ]);
    }

    #[Route('/test-reservation-available/{reservationId}', name: 'backoffice_test_reservation_available')]
    public function testReservationAvailable(int $reservationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $this->reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('backoffice_emails_reservations');
        }

        try {
            $this->emailService->sendReservationAvailableEmail($reservation);
            $this->addFlash('success', 'Reservation available email sent to ' . $reservation->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send reservation available email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_reservations');
    }

    #[Route('/test-reservation-position/{reservationId}', name: 'backoffice_test_reservation_position')]
    public function testReservationPosition(int $reservationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $this->reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('backoffice_emails_reservations');
        }

        try {
            $this->emailService->sendReservationPositionUpdateEmail($reservation);
            $this->addFlash('success', 'Reservation position update email sent to ' . $reservation->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send reservation position email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_reservations');
    }

    #[Route('/test-reservation-cancelled/{reservationId}', name: 'backoffice_test_reservation_cancelled')]
    public function testReservationCancelled(int $reservationId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $this->reservationRepository->find($reservationId);

        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('backoffice_emails_reservations');
        }

        try {
            $this->emailService->sendReservationCancelledEmail($reservation);
            $this->addFlash('success', 'Reservation cancelled email sent to ' . $reservation->getUser()->getEmail());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send reservation cancelled email: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_reservations');
    }

    // ==================== COMMAND EXECUTION ====================

    #[Route('/run-loan-reminders', name: 'backoffice_run_loan_reminders', methods: ['POST'])]
    public function runLoanReminders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reminderDays = $request->request->getInt('reminder_days', 3);
        
        try {
            $projectDir = $this->kernel->getProjectDir();
            $phpBinary = PHP_BINARY;
            
            $process = new Process([
                $phpBinary,
                $projectDir . '/bin/console',
                'app:send-loan-reminders',
                '--reminder-days=' . $reminderDays
            ]);
            
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $this->addFlash('success', 'Commande exécutée avec succès! ' . $this->parseCommandOutput($output));
            } else {
                $this->addFlash('error', 'Erreur: ' . $process->getErrorOutput());
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'exécution: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/run-reading-goals', name: 'backoffice_run_reading_goals', methods: ['POST'])]
    public function runReadingGoals(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $projectDir = $this->kernel->getProjectDir();
            $phpBinary = PHP_BINARY;
            
            $process = new Process([
                $phpBinary,
                $projectDir . '/bin/console',
                'app:update-reading-goals'
            ]);
            
            $process->setTimeout(120);
            $process->run();

            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $this->addFlash('success', 'Objectifs de lecture mis à jour! ' . $this->parseCommandOutput($output));
            } else {
                $this->addFlash('error', 'Erreur: ' . $process->getErrorOutput());
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'exécution: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/send-overdue-alerts', name: 'backoffice_send_overdue_alerts', methods: ['POST'])]
    public function sendOverdueAlerts(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $today = new \DateTimeImmutable('today');
            
            $overdueLoans = $this->loanRepository->createQueryBuilder('l')
                ->where('l.status = :status')
                ->andWhere('l.dueDate < :today')
                ->setParameter('status', Loan::STATUS_ACTIVE)
                ->setParameter('today', $today)
                ->getQuery()
                ->getResult();

            if (empty($overdueLoans)) {
                $this->addFlash('info', 'Aucun prêt en retard trouvé.');
                return $this->redirectToRoute('backoffice_emails_index');
            }

            // Send overdue alert to admins
            $this->emailService->sendOverdueLoanAlertToAdmins($overdueLoans);
            
            // Also send individual overdue emails to users
            $count = 0;
            foreach ($overdueLoans as $loan) {
                try {
                    $this->emailService->sendLoanOverdueEmail($loan);
                    $count++;
                } catch (\Exception $e) {
                    // Continue with others
                }
            }

            $this->addFlash('success', sprintf(
                'Alertes envoyées: %d utilisateur(s) notifié(s), admins alertés pour %d prêt(s) en retard.',
                $count,
                count($overdueLoans)
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    #[Route('/send-return-reminders', name: 'backoffice_send_return_reminders', methods: ['POST'])]
    public function sendReturnReminders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reminderDays = $request->request->getInt('reminder_days', 3);

        try {
            $today = new \DateTimeImmutable('today');
            $dueDate = new \DateTimeImmutable('+' . $reminderDays . ' days');
            
            $loansDueSoon = $this->loanRepository->createQueryBuilder('l')
                ->where('l.status = :status')
                ->andWhere('l.dueDate <= :dueDate')
                ->andWhere('l.dueDate >= :today')
                ->setParameter('status', Loan::STATUS_ACTIVE)
                ->setParameter('dueDate', $dueDate)
                ->setParameter('today', $today)
                ->getQuery()
                ->getResult();

            if (empty($loansDueSoon)) {
                $this->addFlash('info', 'Aucun prêt à échéance proche trouvé.');
                return $this->redirectToRoute('backoffice_emails_index');
            }

            $count = 0;
            foreach ($loansDueSoon as $loan) {
                try {
                    $this->emailService->sendLoanReturnReminderEmail($loan);
                    $count++;
                } catch (\Exception $e) {
                    // Continue with others
                }
            }

            $this->addFlash('success', sprintf(
                'Rappels envoyés à %d utilisateur(s) pour les prêts à rendre dans les %d prochains jours.',
                $count,
                $reminderDays
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur: ' . $e->getMessage());
        }

        return $this->redirectToRoute('backoffice_emails_index');
    }

    private function parseCommandOutput(string $output): string
    {
        // Extract key info from command output
        if (preg_match('/(\d+)\s*reminder/', $output, $matches)) {
            return $matches[1] . ' rappel(s) envoyé(s).';
        }
        if (preg_match('/(\d+)\s*overdue/', $output, $matches)) {
            return $matches[1] . ' notification(s) de retard envoyée(s).';
        }
        return '';
    }
}
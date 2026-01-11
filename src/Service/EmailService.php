<?php

namespace App\Service;

use App\Entity\Livre;
use App\Entity\Order;
use App\Entity\ReadingProgress;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService implements EmailServiceInterface
{
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        string $fromEmail = null,
        string $fromName = null
    ) {
        $this->fromEmail = $fromEmail ?? $_ENV['EMAIL_FROM_ADDRESS'] ?? 'noreply@biblio.app';
        $this->fromName = $fromName ?? $_ENV['EMAIL_FROM_NAME'] ?? 'BiblioApp';
    }

    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Verify your email address - BiblioApp')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Welcome to BiblioApp!')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user, string $resetToken): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $resetToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Reset your password - BiblioApp')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendReadingGoalAchievedEmail(User $user, array $achievedGoals): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Congratulations! Reading goal achieved!')
            ->htmlTemplate('emails/goal_achieved.html.twig')
            ->context([
                'user' => $user,
                'achievedGoals' => $achievedGoals,
            ]);

        $this->mailer->send($email);
    }

    public function sendWeeklyReadingSummary(User $user, array $stats): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Your weekly reading summary')
            ->htmlTemplate('emails/weekly_summary.html.twig')
            ->context([
                'user' => $user,
                'stats' => $stats,
            ]);

        $this->mailer->send($email);
    }

    public function sendBookRecommendation(User $user, array $recommendations): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject('Book recommendations for you')
            ->htmlTemplate('emails/recommendations.html.twig')
            ->context([
                'user' => $user,
                'recommendations' => $recommendations,
            ]);

        $this->mailer->send($email);
    }

    public function sendLowStockAlert(array $lowStockBooks): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($admin->getEmail())
                ->subject('Low stock alert - Books need restocking')
                ->htmlTemplate('emails/low_stock_alert.html.twig')
                ->context([
                    'lowStockBooks' => $lowStockBooks,
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * Send role-based notifications
     */
    public function sendRoleBasedNotification(string $notificationType, array $data = []): void
    {
        $users = $this->getUsersForNotification($notificationType);

        foreach ($users as $user) {
            $this->sendNotificationToUser($user, $notificationType, $data);
        }
    }

    /**
     * Get users who should receive specific notifications based on roles
     */
    private function getUsersForNotification(string $notificationType): array
    {
        return match ($notificationType) {
            'low_stock_alert' => $this->userRepository->findByRole('ROLE_ADMIN'),
            'new_user_registration' => $this->userRepository->findByRole('ROLE_ADMIN'),
            'system_maintenance' => $this->userRepository->findByRole('ROLE_ADMIN'),
            'reading_goal_achieved' => $this->userRepository->findByRole('ROLE_USER'),
            'weekly_summary' => $this->userRepository->findByRole('ROLE_USER'),
            'book_recommendations' => $this->userRepository->findByRole('ROLE_USER'),
            'new_book_added' => $this->userRepository->findByRole(['ROLE_USER', 'ROLE_MODERATOR']),
            default => []
        };
    }

    /**
     * Send specific notification to a user
     */
    private function sendNotificationToUser(User $user, string $notificationType, array $data): void
    {
        try {
            match ($notificationType) {
                'reading_goal_achieved' => $this->sendReadingGoalAchievedEmail($user, $data['goals'] ?? []),
                'weekly_summary' => $this->sendWeeklyReadingSummary($user, $data['stats'] ?? []),
                'book_recommendations' => $this->sendBookRecommendation($user, $data['recommendations'] ?? []),
                'welcome' => $this->sendWelcomeEmail($user),
                'verification' => $this->sendVerificationEmail($user),
                default => null
            };
        } catch (\Exception $e) {
            // Log error but don't break the application
            error_log("Failed to send {$notificationType} notification to {$user->getEmail()}: " . $e->getMessage());
        }
    }

    /**
     * Send weekly reading summaries to all verified users
     */
    public function sendWeeklySummaries(): void
    {
        $verifiedUsers = $this->userRepository->findBy(['isVerified' => true]);

        foreach ($verifiedUsers as $user) {
            $stats = $this->calculateWeeklyStats($user);
            if (!empty($stats)) {
                $this->sendWeeklyReadingSummary($user, $stats);
            }
        }
    }

    /**
     * Send book recommendations to users
     */
    public function sendBookRecommendations(): void
    {
        $verifiedUsers = $this->userRepository->findBy(['isVerified' => true]);

        foreach ($verifiedUsers as $user) {
            $recommendations = $this->generateRecommendations($user);
            if (!empty($recommendations)) {
                $this->sendBookRecommendation($user, $recommendations);
            }
        }
    }

    /**
     * Calculate weekly reading statistics for a user
     */
    public function calculateWeeklyStats(User $user): array
    {
        $oneWeekAgo = new \DateTime('-7 days');
        $now = new \DateTime();

        // Get reading progress from the last 7 days
        $readingProgress = $this->entityManager->getRepository(ReadingProgress::class)
            ->createQueryBuilder('rp')
            ->where('rp.user = :user')
            ->andWhere('rp.lastReadAt >= :oneWeekAgo')
            ->andWhere('rp.lastReadAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('oneWeekAgo', $oneWeekAgo)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $stats = [
            'books_read' => 0,
            'pages_read' => 0,
            'time_spent' => '0 hours',
            'favorite_genre' => 'Not determined'
        ];

        if (!empty($readingProgress)) {
            $stats['books_read'] = count($readingProgress);

            // Calculate total pages read
            foreach ($readingProgress as $progress) {
                $currentPage = $progress->getCurrentPage();
                if ($currentPage !== null) {
                    $stats['pages_read'] += $currentPage;
                }
            }

            // Estimate time spent (assuming 1 page per minute average reading speed)
            $estimatedMinutes = $stats['pages_read'];
            $hours = floor($estimatedMinutes / 60);
            $minutes = $estimatedMinutes % 60;

            if ($hours > 0) {
                $stats['time_spent'] = $hours . 'h ' . $minutes . 'm';
            } else {
                $stats['time_spent'] = $minutes . ' minutes';
            }

            // Determine favorite genre from books read
            $genres = [];
            foreach ($readingProgress as $progress) {
                $book = $progress->getLivre();
                if ($book && $book->getCategorie()) {
                    $genre = $book->getCategorie()->getDesignation();
                    if (!isset($genres[$genre])) {
                        $genres[$genre] = 0;
                    }
                    $genres[$genre]++;
                }
            }

            if (!empty($genres)) {
                $stats['favorite_genre'] = array_keys($genres, max($genres))[0];
            }
        }

        return $stats;
    }

    /**
     * Generate book recommendations for a user
     */
    public function generateRecommendations(User $user): array
    {
        $recommendations = [];

        // Get user's reading history and favorites
        $userFavorites = $user->getFavoriteAuthors();
        $userOwnedBooks = $user->getPurchasedBooks();
        $userWishlist = $user->getWishlist();

        // Get user's reading progress to understand preferences
        $readingProgress = $this->entityManager->getRepository(ReadingProgress::class)
            ->createQueryBuilder('rp')
            ->where('rp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rp.lastReadAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        // Analyze favorite genres from reading history
        $preferredGenres = [];
        $preferredAuthors = [];

        foreach ($readingProgress as $progress) {
            $book = $progress->getLivre();
            if ($book) {
                // Track genres
                if ($book->getCategorie()) {
                    $genre = $book->getCategorie()->getDesignation();
                    $preferredGenres[$genre] = ($preferredGenres[$genre] ?? 0) + 1;
                }

                // Track authors
                if ($book->getAuteur()) {
                    $authorId = $book->getAuteur()->getId();
                    $preferredAuthors[$authorId] = ($preferredAuthors[$authorId] ?? 0) + 1;
                }
            }
        }

        // Add favorite authors to preferred list
        foreach ($userFavorites as $favoriteAuthor) {
            $authorId = $favoriteAuthor->getId();
            $preferredAuthors[$authorId] = ($preferredAuthors[$authorId] ?? 0) + 5; // Boost favorites
        }

        // Find top genres and authors
        arsort($preferredGenres);
        arsort($preferredAuthors);
        $topGenres = array_slice(array_keys($preferredGenres), 0, 3);
        $topAuthorIds = array_slice(array_keys($preferredAuthors), 0, 5);

        // Get books user hasn't read yet
        $readBookIds = array_map(function($progress) {
            return $progress->getLivre() ? $progress->getLivre()->getId() : null;
        }, $readingProgress);

        $ownedBookIds = array_map(function($book) {
            return $book->getId();
        }, $userOwnedBooks->toArray());

        $wishlistBookIds = array_map(function($book) {
            return $book->getId();
        }, $userWishlist->toArray());

        $excludeBookIds = array_unique(array_merge($readBookIds, $ownedBookIds, $wishlistBookIds));

        // Query for recommendations
        $qb = $this->entityManager->getRepository(Livre::class)->createQueryBuilder('l');

        // Recommend books from preferred genres
        if (!empty($topGenres)) {
            $qb->leftJoin('l.categorie', 'c')
               ->where('c.designation IN (:genres)')
               ->setParameter('genres', $topGenres);
        }

        // Also recommend books from preferred authors
        if (!empty($topAuthorIds)) {
            $qb->leftJoin('l.auteur', 'a');
            if (!empty($topGenres)) {
                $qb->orWhere('a.id IN (:authors)');
            } else {
                $qb->where('a.id IN (:authors)');
            }
            $qb->setParameter('authors', $topAuthorIds);
        }

        // Exclude books user already has or read
        if (!empty($excludeBookIds)) {
            $qb->andWhere('l.id NOT IN (:exclude)')
               ->setParameter('exclude', $excludeBookIds);
        }

        // Only recommend available books (in stock > 0)
        $qb->andWhere('l.nbExemplaires > 0');

        // Order by stock/popularity (books with more stock first)
        $qb->orderBy('l.nbExemplaires', 'DESC')
            ->addOrderBy('l.id', 'DESC'); // Newer books first

        $recommendedBooks = $qb->setMaxResults(5)->getQuery()->getResult();

        // Format recommendations
        foreach ($recommendedBooks as $book) {
            $recommendations[] = [
                'title' => $book->getTitre(),
                'author' => $book->getAuteur() ? $book->getAuteur()->getNom() : 'Unknown',
                'genre' => $book->getCategorie() ? $book->getCategorie()->getDesignation() : 'Unknown',
                'reason' => $this->getRecommendationReason($book, $preferredGenres, $preferredAuthors)
            ];
        }

        // If we don't have enough recommendations, add some popular books
        if (count($recommendations) < 3) {
            $popularBooks = $this->entityManager->getRepository(Livre::class)
                ->createQueryBuilder('l')
                ->where('l.nbExemplaires > 0')
                ->andWhere('l.id NOT IN (:exclude)')
                ->setParameter('exclude', $excludeBookIds)
                ->orderBy('l.nbExemplaires', 'DESC')
                ->setMaxResults(3 - count($recommendations))
                ->getQuery()
                ->getResult();

            foreach ($popularBooks as $book) {
                $recommendations[] = [
                    'title' => $book->getTitre(),
                    'author' => $book->getAuteur() ? $book->getAuteur()->getNom() : 'Unknown',
                    'genre' => $book->getCategorie() ? $book->getCategorie()->getDesignation() : 'Unknown',
                    'reason' => 'Popular choice'
                ];
            }
        }

        return array_slice($recommendations, 0, 5); // Return max 5 recommendations
    }

    /**
     * Get recommendation reason based on user preferences
     */
    private function getRecommendationReason(Livre $book, array $preferredGenres, array $preferredAuthors): string
    {
        // Check if it's from a preferred genre
        if ($book->getCategorie() && in_array($book->getCategorie()->getDesignation(), array_keys($preferredGenres))) {
            return 'Based on your reading preferences';
        }

        // Check if it's from a preferred author
        if ($book->getAuteur() && in_array($book->getAuteur()->getId(), array_keys($preferredAuthors))) {
            return 'From an author you enjoy';
        }

        return 'You might like this book';
    }

    /**
     * Send order confirmation email to customer
     */
    public function sendOrderConfirmationEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getUser()->getEmail())
            ->subject('Confirmation de votre commande - ' . $order->getOrderNumber())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send order status update email to customer
     */
    public function sendOrderStatusUpdateEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getUser()->getEmail())
            ->subject('Mise à jour de votre commande - ' . $order->getOrderNumber())
            ->htmlTemplate('emails/order_status_update.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send order shipped notification to customer
     */
    public function sendOrderShippedEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getUser()->getEmail())
            ->subject('Votre commande a été expédiée - ' . $order->getOrderNumber())
            ->htmlTemplate('emails/order_shipped.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send order delivered notification to customer
     */
    public function sendOrderDeliveredEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getUser()->getEmail())
            ->subject('Votre commande a été livrée - ' . $order->getOrderNumber())
            ->htmlTemplate('emails/order_delivered.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send order cancellation notification to customer
     */
    public function sendOrderCancelledEmail(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($order->getUser()->getEmail())
            ->subject('Votre commande a été annulée - ' . $order->getOrderNumber())
            ->htmlTemplate('emails/order_cancelled.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send new order notification to admins
     */
    public function sendNewOrderNotificationToAdmins(Order $order): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($admin->getEmail())
                ->subject('Nouvelle commande reçue - ' . $order->getOrderNumber())
                ->htmlTemplate('emails/admin_new_order.html.twig')
                ->context([
                    'order' => $order,
                    'customer' => $order->getUser(),
                ]);

            $this->mailer->send($email);
        }
    }

    // ==================== LOAN EMAIL METHODS ====================

    /**
     * Send loan request received notification to user
     */
    public function sendLoanRequestReceivedEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Votre demande d\'emprunt a été reçue - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_request_received.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send loan approval email to user
     */
    public function sendLoanApprovedEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Votre emprunt a été approuvé - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_approved.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'dueDate' => $loan->getDueDate(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send loan rejection email to user
     */
    public function sendLoanRejectedEmail(\App\Entity\Loan $loan, string $reason = ''): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Votre demande d\'emprunt a été rejetée - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_rejected.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'reason' => $reason,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send loan started notification to user
     */
    public function sendLoanStartedEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Votre emprunt a commencé - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_started.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'dueDate' => $loan->getDueDate(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send loan return reminder (before due date)
     */
    public function sendLoanReturnReminderEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Rappel: Retour d\'emprunt - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_return_reminder.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'daysLeft' => $this->calculateDaysLeft($loan->getDueDate()),
                'dueDate' => $loan->getDueDate(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send overdue notification to user
     */
    public function sendLoanOverdueEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Emprunt en retard - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_overdue.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'daysOverdue' => $this->calculateDaysOverdue($loan->getDueDate()),
                'dueDate' => $loan->getDueDate(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send loan return confirmation
     */
    public function sendLoanReturnedEmail(\App\Entity\Loan $loan): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($loan->getUser()->getEmail())
            ->subject('Emprunt retourné - ' . $loan->getLivre()->getTitre())
            ->htmlTemplate('emails/loan_returned.html.twig')
            ->context([
                'loan' => $loan,
                'user' => $loan->getUser(),
                'book' => $loan->getLivre(),
                'returnedAt' => $loan->getReturnedAt(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send new loan request notification to admins
     */
    public function sendNewLoanRequestNotificationToAdmins(\App\Entity\Loan $loan): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($admin->getEmail())
                ->subject('Nouvelle demande d\'emprunt - ' . $loan->getLivre()->getTitre())
                ->htmlTemplate('emails/admin_new_loan_request.html.twig')
                ->context([
                    'loan' => $loan,
                    'user' => $loan->getUser(),
                    'book' => $loan->getLivre(),
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * Send overdue loan alert to admins
     */
    public function sendOverdueLoanAlertToAdmins(array $overdueLoans): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($admin->getEmail())
                ->subject('Alertes de retards d\'emprunts - ' . count($overdueLoans) . ' emprunts')
                ->htmlTemplate('emails/admin_overdue_loans.html.twig')
                ->context([
                    'overdueLoans' => $overdueLoans,
                ]);

            $this->mailer->send($email);
        }
    }

    // ==================== RESERVATION EMAIL METHODS ====================

    /**
     * Send reservation confirmed notification to user
     */
    public function sendReservationConfirmedEmail(\App\Entity\BookReservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($reservation->getUser()->getEmail())
            ->subject('Votre réservation a été confirmée - ' . $reservation->getLivre()->getTitre())
            ->htmlTemplate('emails/reservation_confirmed.html.twig')
            ->context([
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'book' => $reservation->getLivre(),
                'position' => $reservation->getPosition(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification when book becomes available for reservation
     */
    public function sendReservationAvailableEmail(\App\Entity\BookReservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($reservation->getUser()->getEmail())
            ->subject('Le livre réservé est maintenant disponible - ' . $reservation->getLivre()->getTitre())
            ->htmlTemplate('emails/reservation_available.html.twig')
            ->context([
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'book' => $reservation->getLivre(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send reservation position update to user
     */
    public function sendReservationPositionUpdateEmail(\App\Entity\BookReservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($reservation->getUser()->getEmail())
            ->subject('Mise à jour de votre position de réservation - ' . $reservation->getLivre()->getTitre())
            ->htmlTemplate('emails/reservation_position_update.html.twig')
            ->context([
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'book' => $reservation->getLivre(),
                'position' => $reservation->getPosition(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send reservation cancellation confirmation
     */
    public function sendReservationCancelledEmail(\App\Entity\BookReservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($reservation->getUser()->getEmail())
            ->subject('Votre réservation a été annulée - ' . $reservation->getLivre()->getTitre())
            ->htmlTemplate('emails/reservation_cancelled.html.twig')
            ->context([
                'reservation' => $reservation,
                'user' => $reservation->getUser(),
                'book' => $reservation->getLivre(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send new reservation notification to admins
     */
    public function sendNewReservationNotificationToAdmins(\App\Entity\BookReservation $reservation): void
    {
        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($admin->getEmail())
                ->subject('Nouvelle réservation - ' . $reservation->getLivre()->getTitre())
                ->htmlTemplate('emails/admin_new_reservation.html.twig')
                ->context([
                    'reservation' => $reservation,
                    'user' => $reservation->getUser(),
                    'book' => $reservation->getLivre(),
                ]);

            $this->mailer->send($email);
        }
    }

    /**
     * Helper: Calculate days left until due date
     */
    public function calculateDaysLeft(\DateTimeImmutable|null $dueDate = null): int
    {
        if (!$dueDate) {
            return 0;
        }
        $now = new \DateTime();
        $interval = $dueDate->diff($now);
        return (int)$interval->days;
    }

    /**
     * Helper: Calculate days overdue
     */
    public function calculateDaysOverdue(\DateTimeImmutable|null $dueDate = null): int
    {
        if (!$dueDate) {
            return 0;
        }
        $now = new \DateTime();
        if ($dueDate < $now) {
            $interval = $now->diff($dueDate);
            return (int)$interval->days;
        }
        return 0;
    }

}
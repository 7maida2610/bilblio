<?php

namespace App\Service;

use App\Entity\BookReservation;
use App\Entity\Livre;
use App\Entity\Loan;
use App\Entity\Order;
use App\Entity\User;

/**
 * EmailServiceInterface
 * 
 * Defines the contract for email service implementations
 * Provides type-safe email sending methods for all BiblioApp features
 * 
 * @package App\Service
 */
interface EmailServiceInterface
{
    // ==================== USER AUTHENTICATION ====================
    
    /**
     * Send email verification link to user
     */
    public function sendVerificationEmail(User $user): void;

    /**
     * Send welcome email to newly registered user
     */
    public function sendWelcomeEmail(User $user): void;

    /**
     * Send password reset link to user
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): void;

    // ==================== LOAN NOTIFICATIONS ====================

    /**
     * Notify user that loan request was received
     */
    public function sendLoanRequestReceivedEmail(Loan $loan): void;

    /**
     * Notify user that loan was approved
     */
    public function sendLoanApprovedEmail(Loan $loan): void;

    /**
     * Notify user that loan request was rejected
     */
    public function sendLoanRejectedEmail(Loan $loan, string $reason = ''): void;

    /**
     * Notify user that loan is ready to pick up / has started
     */
    public function sendLoanStartedEmail(Loan $loan): void;

    /**
     * Send reminder to return book before due date
     */
    public function sendLoanReturnReminderEmail(Loan $loan): void;



    /**
     * Notify user that loan is overdue
     */
    public function sendLoanOverdueEmail(Loan $loan): void;

    /**
     * Confirm that user has returned the book
     */
    public function sendLoanReturnedEmail(Loan $loan): void;

    /**
     * Notify admins of new loan request
     */
    public function sendNewLoanRequestNotificationToAdmins(Loan $loan): void;

    /**
     * Alert admins of overdue loans
     * 
     * @param Loan[] $overdueLoans
     */
    public function sendOverdueLoanAlertToAdmins(array $overdueLoans): void;

    // ==================== BOOK RESERVATION NOTIFICATIONS ====================

    /**
     * Confirm reservation and provide queue position
     */
    public function sendReservationConfirmedEmail(BookReservation $reservation): void;

    /**
     * Notify user that reserved book is available for pickup
     */
    public function sendReservationAvailableEmail(BookReservation $reservation): void;

    /**
     * Update user about position change in reservation queue
     */
    public function sendReservationPositionUpdateEmail(BookReservation $reservation): void;

    /**
     * Confirm that reservation has been cancelled
     */
    public function sendReservationCancelledEmail(BookReservation $reservation): void;

    /**
     * Notify admins of new reservation
     */
    public function sendNewReservationNotificationToAdmins(BookReservation $reservation): void;

    // ==================== ORDER NOTIFICATIONS ====================

    /**
     * Send order confirmation to customer
     */
    public function sendOrderConfirmationEmail(Order $order): void;

    /**
     * Send order status update to customer
     */
    public function sendOrderStatusUpdateEmail(Order $order): void;

    /**
     * Notify customer that order has been shipped
     */
    public function sendOrderShippedEmail(Order $order): void;

    /**
     * Notify customer that order has been delivered
     */
    public function sendOrderDeliveredEmail(Order $order): void;

    /**
     * Notify customer that order has been cancelled
     */
    public function sendOrderCancelledEmail(Order $order): void;

    /**
     * Notify admins of new order
     */
    public function sendNewOrderNotificationToAdmins(Order $order): void;

    // ==================== READING ENGAGEMENT & GOALS ====================

    /**
     * Congratulate user on achieving reading goal
     * 
     * @param array $achievedGoals Array of achieved reading goals
     */
    public function sendReadingGoalAchievedEmail(User $user, array $achievedGoals): void;

    /**
     * Send weekly reading summary to user
     * 
     * @param array $stats Weekly reading statistics
     */
    public function sendWeeklyReadingSummary(User $user, array $stats): void;

    /**
     * Send personalized book recommendations to user
     * 
     * @param array $recommendations Array of book recommendations
     */
    public function sendBookRecommendation(User $user, array $recommendations): void;

    /**
     * Alert admins about books with low stock
     * 
     * @param Livre[] $lowStockBooks Array of books with low stock
     */
    public function sendLowStockAlert(array $lowStockBooks): void;

    // ==================== BULK OPERATIONS ====================

    /**
     * Send weekly reading summaries to all active users
     */
    public function sendWeeklySummaries(): void;

    /**
     * Send book recommendations to all active users
     */
    public function sendBookRecommendations(): void;

    /**
     * Send role-based notifications to users
     * 
     * @param string $notificationType Type of notification to send
     * @param array $data Additional data for the notification
     */
    public function sendRoleBasedNotification(string $notificationType, array $data = []): void;

    // ==================== HELPER METHODS ====================

    /**
     * Calculate days remaining until due date
     */
    public function calculateDaysLeft(\DateTimeImmutable|null $dueDate = null): int;

    /**
     * Calculate days overdue
     */
    public function calculateDaysOverdue(\DateTimeImmutable|null $dueDate = null): int;

    /**
     * Calculate weekly reading statistics for a user
     * 
     * @return array Statistics including books_read, pages_read, time_spent, favorite_genre
     */
    public function calculateWeeklyStats(User $user): array;

    /**
     * Generate personalized book recommendations for user
     * 
     * @return array Array of recommended books with details
     */
    public function generateRecommendations(User $user): array;
}

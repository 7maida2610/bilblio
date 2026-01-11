<?php

namespace App\EventListener;

use App\Entity\BookReservation;
use App\Entity\Loan;
use App\Entity\Order;
use App\Service\EmailServiceInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

/**
 * AdminEmailListener - Automatic email notifications for admin actions
 */
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
class AdminEmailListener
{
    public function __construct(
        private EmailServiceInterface $emailService,
        private LoggerInterface $logger
    ) {}

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();
        $changeSet = $em->getUnitOfWork()->getEntityChangeSet($entity);

        $this->logger->info('postUpdate called for entity: ' . get_class($entity), ['changeSet' => array_keys($changeSet)]);

        if ($entity instanceof Loan) {
            $this->handleLoanUpdate($entity, $changeSet);
        } elseif ($entity instanceof BookReservation) {
            $this->handleReservationUpdate($entity, $changeSet);
        } elseif ($entity instanceof Order) {
            $this->logger->info('Order update detected');
            $this->handleOrderUpdate($entity, $changeSet);
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        $this->logger->info('postPersist called for entity: ' . get_class($entity));

        // Check if mailer is configured and functional before attempting to send emails
        if (!$this->isMailerAvailable()) {
            $this->logger->debug('Mailer not available, skipping email notifications');
            return;
        }

        try {
            if ($entity instanceof Loan) {
                // Send confirmation email to the user
                $this->logger->info('Sending loan request received email to user');
                $this->emailService->sendLoanRequestReceivedEmail($entity);
                $this->logger->info('Sent loan request received email to user');
                
                // Send notification to admins
                $this->logger->info('Sending new loan request notification to admins');
                $this->emailService->sendNewLoanRequestNotificationToAdmins($entity);
                $this->logger->info('Sent new loan request notification to admins');
            } elseif ($entity instanceof BookReservation) {
                $this->logger->info('Sending reservation confirmation and admin notification');
                $this->emailService->sendReservationConfirmedEmail($entity);
                $this->emailService->sendNewReservationNotificationToAdmins($entity);
                $this->logger->info('Sent reservation confirmation and admin notification');
            } elseif ($entity instanceof Order) {
                $this->logger->info('Sending order confirmation for order: ' . $entity->getOrderNumber());
                $this->emailService->sendOrderConfirmationEmail($entity);
                $this->logger->info('Sent order confirmation email');
                $this->emailService->sendNewOrderNotificationToAdmins($entity);
                $this->logger->info('Sent admin notification for new order');
            }
        } catch (\Exception $e) {
            // Log as warning instead of error - email failures are non-critical
            $this->logger->warning('Email notification failed (non-critical): ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'entity_type' => get_class($entity)
            ]);
            // Don't re-throw - emails are not critical for application functionality
        }
    }

    private function handleLoanUpdate(Loan $entity, array $changeSet): void
    {
        if (!isset($changeSet['status'])) {
            return;
        }

        // Check if mailer is configured and functional before attempting to send emails
        if (!$this->isMailerAvailable()) {
            $this->logger->debug('Mailer not available, skipping email notifications');
            return;
        }

        $oldStatus = $changeSet['status'][0];
        $newStatus = $changeSet['status'][1];

        try {
            switch ($newStatus) {
                case Loan::STATUS_APPROVED:
                    $this->emailService->sendLoanApprovedEmail($entity);
                    $this->logger->info('Sent loan approval email');
                    break;

                case Loan::STATUS_CANCELLED:
                    if ($oldStatus === Loan::STATUS_REQUESTED) {
                        $reason = $entity->getNotes() ?? 'No reason provided';
                        $this->emailService->sendLoanRejectedEmail($entity, $reason);
                        $this->logger->info('Sent loan rejection email');
                    }
                    break;

                case Loan::STATUS_ACTIVE:
                    $this->emailService->sendLoanStartedEmail($entity);
                    $this->logger->info('Sent loan started email');
                    break;

                case Loan::STATUS_RETURNED:
                    $this->emailService->sendLoanReturnedEmail($entity);
                    $this->logger->info('Sent loan returned email');
                    break;

                case Loan::STATUS_OVERDUE:
                    $this->emailService->sendLoanOverdueEmail($entity);
                    $this->logger->info('Sent loan overdue email');
                    break;
            }
        } catch (\Exception $e) {
            // Log as warning instead of error - email failures are non-critical
            $this->logger->warning('Email notification failed (non-critical): ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'entity_type' => 'Loan'
            ]);
        }
    }

    private function handleReservationUpdate(BookReservation $entity, array $changeSet): void
    {
        // Check if mailer is configured and functional before attempting to send emails
        if (!$this->isMailerAvailable()) {
            $this->logger->debug('Mailer not available, skipping email notifications');
            return;
        }

        try {
            if (isset($changeSet['position'])) {
                $oldPosition = $changeSet['position'][0];
                $newPosition = $changeSet['position'][1];

                if ($newPosition !== null && $newPosition !== $oldPosition) {
                    $this->emailService->sendReservationPositionUpdateEmail($entity);
                    $this->logger->info('Sent reservation position update email');
                }
            }

            if (isset($changeSet['notifiedAt'])) {
                $oldNotified = $changeSet['notifiedAt'][0];
                $newNotified = $changeSet['notifiedAt'][1];

                if ($oldNotified === null && $newNotified !== null) {
                    $this->emailService->sendReservationAvailableEmail($entity);
                    $this->logger->info('Sent reservation available email');
                }
            }

            if (isset($changeSet['isActive'])) {
                $wasActive = $changeSet['isActive'][0];
                $isNowActive = $changeSet['isActive'][1];

                if ($wasActive === true && $isNowActive === false) {
                    $this->emailService->sendReservationCancelledEmail($entity);
                    $this->logger->info('Sent reservation cancelled email');
                }
            }
        } catch (\Exception $e) {
            // Log as warning instead of error - email failures are non-critical
            $this->logger->warning('Email notification failed (non-critical): ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'entity_type' => 'BookReservation'
            ]);
        }
    }

    private function handleOrderUpdate(Order $entity, array $changeSet): void
    {
        if (!isset($changeSet['status'])) {
            $this->logger->info('No status change detected for order');
            return;
        }

        // Check if mailer is configured and functional before attempting to send emails
        if (!$this->isMailerAvailable()) {
            $this->logger->debug('Mailer not available, skipping email notifications');
            return;
        }

        $newStatus = $changeSet['status'][1];
        $this->logger->info('Order status changed to: ' . $newStatus);

        try {
            switch ($newStatus) {
                case 'shipped':
                    $this->logger->info('Sending order shipped email');
                    $this->emailService->sendOrderShippedEmail($entity);
                    $this->logger->info('Sent order shipped email');
                    break;

                case 'delivered':
                    $this->logger->info('Sending order delivered email');
                    $this->emailService->sendOrderDeliveredEmail($entity);
                    $this->logger->info('Sent order delivered email');
                    break;

                case 'cancelled':
                    $this->logger->info('Sending order cancelled email');
                    $this->emailService->sendOrderCancelledEmail($entity);
                    $this->logger->info('Sent order cancelled email');
                    break;

                default:
                    $this->logger->info('Sending order status update email');
                    $this->emailService->sendOrderStatusUpdateEmail($entity);
                    $this->logger->info('Sent order status update email');
                    break;
            }
        } catch (\Exception $e) {
            // Log as warning instead of error - email failures are non-critical
            $this->logger->warning('Email notification failed (non-critical): ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'entity_type' => 'Order'
            ]);
            // Don't re-throw - emails are not critical for application functionality
        }
    }

    /**
     * Check if mailer is available and configured
     * Returns false if mailer is not configured or uses null transport
     * Also returns false during fixtures loading to avoid SMTP connection errors
     */
    private function isMailerAvailable(): bool
    {
        // During fixtures loading, always skip emails to avoid SMTP connection errors
        if (php_sapi_name() === 'cli' && isset($_SERVER['argv'])) {
            $argv = $_SERVER['argv'];
            if (in_array('app:load-fixtures', $argv) || 
                (isset($argv[1]) && $argv[1] === 'app:load-fixtures')) {
                return false;
            }
        }
        
        $mailerDsn = $_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN') ?: null;
        
        // If not set, mailer is not available
        if (empty($mailerDsn)) {
            return false;
        }
        
        // If using null transport, mailer is not available
        if ($mailerDsn === 'null://null' || str_starts_with($mailerDsn, 'null://')) {
            return false;
        }
        
        // For SMTP, we assume it's available unless we're in fixtures context
        // (which is already handled above)
        return true;
    }
}

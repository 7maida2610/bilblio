<?php

namespace App\Command;

use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Service\EmailServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-loan-reminders',
    description: 'Send reminder emails for loans due soon and mark overdue loans',
)]
class SendLoanRemindersCommand extends Command
{
    public function __construct(
        private LoanRepository $loanRepository,
        private EmailServiceInterface $emailService,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reminder-days', 'd', InputOption::VALUE_OPTIONAL, 'Send reminder for loans due in X days', 3)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate without sending emails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $reminderDays = (int) $input->getOption('reminder-days');

        $io->title('Loan Email Notifications');

        $io->section('Checking for loans due soon...');
        $reminderCount = $this->sendReturnReminders($io, $reminderDays, $dryRun);

        $io->section('Checking for overdue loans...');
        $overdueCount = $this->processOverdueLoans($io, $dryRun);

        $io->success(sprintf(
            '%s: %d reminder(s) sent, %d overdue notification(s) sent',
            $dryRun ? '[DRY RUN]' : 'Completed',
            $reminderCount,
            $overdueCount
        ));

        return Command::SUCCESS;
    }

    private function sendReturnReminders(SymfonyStyle $io, int $reminderDays, bool $dryRun): int
    {
        $count = 0;
        $dueDate = new \DateTimeImmutable('+' . $reminderDays . ' days');
        $today = new \DateTimeImmutable('today');

        $loans = $this->loanRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.dueDate <= :dueDate')
            ->andWhere('l.dueDate >= :today')
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->setParameter('dueDate', $dueDate)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        foreach ($loans as $loan) {
            $daysRemaining = $today->diff($loan->getDueDate())->days;
            $io->text(sprintf(
                '  - Loan #%d: "%s" by %s - due in %d day(s)',
                $loan->getId(),
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername(),
                $daysRemaining
            ));

            if (!$dryRun) {
                try {
                    $this->emailService->sendLoanReturnReminderEmail($loan);
                    $count++;
                } catch (\Exception $e) {
                    $io->error('Failed to send reminder: ' . $e->getMessage());
                }
            } else {
                $count++;
            }
        }

        if ($count === 0) {
            $io->text('  No loans due within the next ' . $reminderDays . ' days.');
        }

        return $count;
    }

    private function processOverdueLoans(SymfonyStyle $io, bool $dryRun): int
    {
        $count = 0;
        $today = new \DateTimeImmutable('today');

        $loans = $this->loanRepository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.dueDate < :today')
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        foreach ($loans as $loan) {
            $daysOverdue = $loan->getDueDate()->diff($today)->days;
            $io->text(sprintf(
                '  - Loan #%d: "%s" by %s - %d day(s) overdue',
                $loan->getId(),
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername(),
                $daysOverdue
            ));

            if (!$dryRun) {
                try {
                    $loan->setStatus(Loan::STATUS_OVERDUE);
                    $this->entityManager->flush();
                    $count++;
                } catch (\Exception $e) {
                    $io->error('Failed to process overdue loan: ' . $e->getMessage());
                }
            } else {
                $count++;
            }
        }

        if ($count === 0) {
            $io->text('  No overdue loans found.');
        }

        return $count;
    }
}

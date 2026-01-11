<?php

namespace App\Service;

use App\Entity\ReadingGoal;
use App\Entity\User;
use App\Repository\ReadingGoalRepository;
use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class GoalAchievementService
{
    public function __construct(
        private ReadingGoalRepository $goalRepository,
        private ReadingProgressRepository $progressRepository,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    /**
     * Check and update all reading goals for a user
     */
    public function checkAndUpdateGoals(User $user): array
    {
        $achievedGoals = [];
        $activeGoals = $this->goalRepository->findActiveGoals($user);

        foreach ($activeGoals as $goal) {
            $wasAchieved = $goal->getCurrentValue() >= $goal->getTargetValue();

            // Update goal progress based on current data
            $this->updateGoalProgress($goal);

            // Check if goal is now achieved
            $isNowAchieved = $goal->getCurrentValue() >= $goal->getTargetValue();

            if ($isNowAchieved && !$wasAchieved) {
                $achievedGoals[] = $goal;
                $this->markGoalAsAchieved($goal);
            }
        }

        // Send notification if goals were achieved
        if (!empty($achievedGoals)) {
            $this->emailService->sendReadingGoalAchievedEmail($user, $achievedGoals);
        }

        return $achievedGoals;
    }

    /**
     * Update the progress of a specific reading goal
     */
    private function updateGoalProgress(ReadingGoal $goal): void
    {
        $user = $goal->getUser();

        switch ($goal->getGoalType()) {
            case 'books_year':
                $this->updateBooksThisYearGoal($goal, $user);
                break;
            case 'pages_month':
                $this->updatePagesThisMonthGoal($goal, $user);
                break;
            case 'books_month':
                $this->updateBooksThisMonthGoal($goal, $user);
                break;
            case 'pages_year':
                $this->updatePagesThisYearGoal($goal, $user);
                break;
            case 'reading_streak':
                $this->updateReadingStreakGoal($goal, $user);
                break;
        }

        $this->entityManager->flush();
    }

    /**
     * Update books read this year goal
     */
    private function updateBooksThisYearGoal(ReadingGoal $goal, User $user): void
    {
        $currentYear = (int) date('Y');
        $startOfYear = new \DateTime("$currentYear-01-01 00:00:00");
        $endOfYear = new \DateTime("$currentYear-12-31 23:59:59");

        $completedBooks = $this->progressRepository->countCompletedBooksInPeriod($user, $startOfYear, $endOfYear);
        $goal->setCurrentValue($completedBooks);
    }

    /**
     * Update pages read this month goal
     */
    private function updatePagesThisMonthGoal(ReadingGoal $goal, User $user): void
    {
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');
        $startOfMonth = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
        $endOfMonth = new \DateTime("$currentYear-$currentMonth-" . date('t', strtotime("$currentYear-$currentMonth-01")) . " 23:59:59");

        $pagesRead = $this->progressRepository->countPagesReadInPeriod($user, $startOfMonth, $endOfMonth);
        $goal->setCurrentValue($pagesRead);
    }

    /**
     * Update books read this month goal
     */
    private function updateBooksThisMonthGoal(ReadingGoal $goal, User $user): void
    {
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');
        $startOfMonth = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
        $endOfMonth = new \DateTime("$currentYear-$currentMonth-" . date('t', strtotime("$currentYear-$currentMonth-01")) . " 23:59:59");

        $completedBooks = $this->progressRepository->countCompletedBooksInPeriod($user, $startOfMonth, $endOfMonth);
        $goal->setCurrentValue($completedBooks);
    }

    /**
     * Update pages read this year goal
     */
    private function updatePagesThisYearGoal(ReadingGoal $goal, User $user): void
    {
        $currentYear = (int) date('Y');
        $startOfYear = new \DateTime("$currentYear-01-01 00:00:00");
        $endOfYear = new \DateTime("$currentYear-12-31 23:59:59");

        $pagesRead = $this->progressRepository->countPagesReadInPeriod($user, $startOfYear, $endOfYear);
        $goal->setCurrentValue($pagesRead);
    }

    /**
     * Update reading streak goal
     */
    private function updateReadingStreakGoal(ReadingGoal $goal, User $user): void
    {
        $currentStreak = $this->calculateCurrentReadingStreak($user);
        $goal->setCurrentValue($currentStreak);
    }

    /**
     * Calculate the current reading streak for a user
     */
    private function calculateCurrentReadingStreak(User $user): int
    {
        $streak = 0;
        $checkDate = new \DateTime('today');

        while (true) {
            $startOfDay = new \DateTime($checkDate->format('Y-m-d') . ' 00:00:00');
            $endOfDay = new \DateTime($checkDate->format('Y-m-d') . ' 23:59:59');

            $progressOnDay = $this->progressRepository->findProgressInPeriod($user, $startOfDay, $endOfDay);

            if (empty($progressOnDay)) {
                break; // No reading on this day, streak ends
            }

            $streak++;
            $checkDate->modify('-1 day');

            // Limit streak calculation to reasonable bounds (max 365 days)
            if ($streak >= 365) {
                break;
            }
        }

        return $streak;
    }

    /**
     * Mark a goal as achieved and extend its deadline
     */
    private function markGoalAsAchieved(ReadingGoal $goal): void
    {
        // Extend the goal period based on type
        $newEndDate = clone $goal->getEndDate();

        switch ($goal->getGoalType()) {
            case 'books_year':
            case 'pages_year':
                $newEndDate->modify('+1 year');
                break;
            case 'books_month':
            case 'pages_month':
                $newEndDate->modify('+1 month');
                break;
            case 'reading_streak':
                $newEndDate->modify('+6 months'); // Streak goals have longer periods
                break;
        }

        $goal->setEndDate($newEndDate);

        // Optionally increase the target for recurring goals
        $this->increaseGoalTarget($goal);

        $this->entityManager->flush();
    }

    /**
     * Increase the target value for achieved goals to make them progressive
     */
    private function increaseGoalTarget(ReadingGoal $goal): void
    {
        $currentTarget = $goal->getTargetValue();

        switch ($goal->getGoalType()) {
            case 'books_year':
                // Increase by 2-5 books based on current level
                $increase = $currentTarget < 12 ? 2 : ($currentTarget < 24 ? 3 : 5);
                break;
            case 'pages_year':
                // Increase by 500-2000 pages based on current level
                $increase = $currentTarget < 6000 ? 500 : ($currentTarget < 12000 ? 1000 : 2000);
                break;
            case 'books_month':
                // Increase by 1-2 books
                $increase = $currentTarget < 4 ? 1 : 2;
                break;
            case 'pages_month':
                // Increase by 200-500 pages
                $increase = $currentTarget < 1000 ? 200 : 500;
                break;
            case 'reading_streak':
                // Increase by 7-14 days
                $increase = $currentTarget < 30 ? 7 : 14;
                break;
            default:
                $increase = 0;
        }

        if ($increase > 0) {
            $goal->setTargetValue($currentTarget + $increase);
            $goal->setCurrentValue(0); // Reset progress for new target
        }
    }

    /**
     * Get goal achievement statistics for a user
     */
    public function getGoalStatistics(User $user): array
    {
        $activeGoals = $this->goalRepository->findActiveGoals($user);
        $achievedGoals = $this->goalRepository->findAchievedGoals($user);

        $stats = [
            'active_goals_count' => count($activeGoals),
            'achieved_goals_count' => count($achievedGoals),
            'total_goals_set' => count($activeGoals) + count($achievedGoals),
            'achievement_rate' => 0,
            'current_streak' => $this->calculateCurrentReadingStreak($user),
            'longest_streak' => $this->getLongestStreak($user),
            'goals_by_type' => []
        ];

        if ($stats['total_goals_set'] > 0) {
            $stats['achievement_rate'] = round(($stats['achieved_goals_count'] / $stats['total_goals_set']) * 100, 1);
        }

        // Group goals by type
        foreach (array_merge($activeGoals, $achievedGoals) as $goal) {
            $type = $goal->getGoalType();
            if (!isset($stats['goals_by_type'][$type])) {
                $stats['goals_by_type'][$type] = [
                    'active' => 0,
                    'achieved' => 0,
                    'total' => 0
                ];
            }

            $stats['goals_by_type'][$type]['total']++;
            if ($goal->getCurrentValue() >= $goal->getTargetValue()) {
                $stats['goals_by_type'][$type]['achieved']++;
            } else {
                $stats['goals_by_type'][$type]['active']++;
            }
        }

        return $stats;
    }

    /**
     * Get the longest reading streak for a user
     */
    private function getLongestStreak(User $user): int
    {
        // This would require storing historical streak data
        // For now, return current streak as approximation
        return $this->calculateCurrentReadingStreak($user);
    }

    /**
     * Check if any goals need periodic updates (daily/weekly)
     */
    public function performPeriodicGoalUpdates(): void
    {
        $users = $this->entityManager->getRepository(User::class)->findBy(['isVerified' => true]);

        foreach ($users as $user) {
            $this->checkAndUpdateGoals($user);
        }
    }
}
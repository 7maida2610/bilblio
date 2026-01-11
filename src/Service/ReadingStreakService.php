<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ActivityLogRepository;
use DateTime;
use DateTimeImmutable;

class ReadingStreakService
{
    public function __construct(
        private ActivityLogRepository $activityLogRepository
    ) {}

    /**
     * Calculate the current reading streak for a user
     */
    public function getCurrentStreak(User $user): int
    {
        $activities = $this->activityLogRepository->findByUser($user);
        $streak = 0;
        $currentDate = new DateTime();
        $currentDate->setTime(0, 0, 0);

        // Check if user has any reading activity today
        $hasActivityToday = false;
        foreach ($activities as $activity) {
            $activityDate = $activity->getCreatedAt()->setTime(0, 0, 0);
            if ($activityDate == $currentDate) {
                $hasActivityToday = true;
                break;
            }
        }

        if (!$hasActivityToday) {
            return 0; // No activity today, streak is 0
        }

        // Count consecutive days with activity
        $checkDate = clone $currentDate;
        while (true) {
            $hasActivity = false;
            foreach ($activities as $activity) {
                $activityDate = $activity->getCreatedAt()->setTime(0, 0, 0);
                if ($activityDate == $checkDate) {
                    $hasActivity = true;
                    break;
                }
            }

            if ($hasActivity) {
                $streak++;
                $checkDate->modify('-1 day');
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get reading streak statistics
     */
    public function getStreakStats(User $user): array
    {
        $activities = $this->activityLogRepository->findByUser($user);
        $currentStreak = $this->getCurrentStreak($user);

        // Calculate longest streak
        $longestStreak = 0;
        $currentCount = 0;
        $previousDate = null;

        // Sort activities by date
        usort($activities, function($a, $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });

        foreach ($activities as $activity) {
            $activityDate = $activity->getCreatedAt()->format('Y-m-d');

            if ($previousDate === null) {
                $currentCount = 1;
            } elseif ($previousDate === $activityDate) {
                // Same day, continue
                continue;
            } elseif (date('Y-m-d', strtotime($previousDate . ' +1 day')) === $activityDate) {
                // Consecutive day
                $currentCount++;
            } else {
                // Break in streak
                $longestStreak = max($longestStreak, $currentCount);
                $currentCount = 1;
            }

            $previousDate = $activityDate;
        }

        $longestStreak = max($longestStreak, $currentCount);

        // Calculate weekly activity
        $weeklyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new DateTime("-{$i} days");
            $dateStr = $date->format('Y-m-d');
            $count = 0;

            foreach ($activities as $activity) {
                if ($activity->getCreatedAt()->format('Y-m-d') === $dateStr) {
                    $count++;
                }
            }

            $weeklyActivity[] = [
                'date' => $date->format('M d'),
                'count' => $count,
                'day' => $date->format('D')
            ];
        }

        // Calculate monthly stats
        $thisMonth = new DateTime('first day of this month');
        $monthlyActivities = 0;

        foreach ($activities as $activity) {
            if ($activity->getCreatedAt() >= $thisMonth) {
                $monthlyActivities++;
            }
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'weekly_activity' => $weeklyActivity,
            'monthly_activities' => $monthlyActivities,
            'total_activities' => count($activities),
            'streak_status' => $this->getStreakStatus($currentStreak)
        ];
    }

    /**
     * Get streak status message
     */
    private function getStreakStatus(int $streak): string
    {
        if ($streak === 0) {
            return 'Commencez votre série de lecture !';
        } elseif ($streak < 7) {
            return 'Bonne série ! Continuez comme ça.';
        } elseif ($streak < 30) {
            return 'Excellente série ! Vous êtes motivé !';
        } elseif ($streak < 100) {
            return 'Incroyable ! Vous êtes un lecteur assidu !';
        } else {
            return 'Légende ! Votre dévouement est remarquable !';
        }
    }

    /**
     * Get reading achievements
     */
    public function getAchievements(User $user): array
    {
        $stats = $this->getStreakStats($user);
        $achievements = [];

        // Streak achievements
        if ($stats['current_streak'] >= 7) {
            $achievements[] = [
                'icon' => 'fas fa-fire',
                'title' => 'Semaine de Lecture',
                'description' => '7 jours consécutifs de lecture',
                'color' => 'warning',
                'unlocked' => true
            ];
        }

        if ($stats['current_streak'] >= 30) {
            $achievements[] = [
                'icon' => 'fas fa-calendar-alt',
                'title' => 'Mois de Lecture',
                'description' => '30 jours consécutifs de lecture',
                'color' => 'success',
                'unlocked' => true
            ];
        }

        if ($stats['longest_streak'] >= 100) {
            $achievements[] = [
                'icon' => 'fas fa-trophy',
                'title' => 'Lecteur Légendaire',
                'description' => '100 jours consécutifs de lecture',
                'color' => 'primary',
                'unlocked' => true
            ];
        }

        // Activity achievements
        if ($stats['monthly_activities'] >= 50) {
            $achievements[] = [
                'icon' => 'fas fa-book-open',
                'title' => 'Lecteur Actif',
                'description' => '50 activités de lecture ce mois',
                'color' => 'info',
                'unlocked' => true
            ];
        }

        if ($stats['total_activities'] >= 500) {
            $achievements[] = [
                'icon' => 'fas fa-star',
                'title' => 'Bibliophile',
                'description' => '500 activités de lecture au total',
                'color' => 'secondary',
                'unlocked' => true
            ];
        }

        // Add locked achievements for motivation
        $lockedAchievements = [
            [
                'icon' => 'fas fa-fire',
                'title' => 'Semaine de Lecture',
                'description' => '7 jours consécutifs de lecture',
                'color' => 'warning',
                'unlocked' => false
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'title' => 'Mois de Lecture',
                'description' => '30 jours consécutifs de lecture',
                'color' => 'success',
                'unlocked' => false
            ],
            [
                'icon' => 'fas fa-trophy',
                'title' => 'Lecteur Légendaire',
                'description' => '100 jours consécutifs de lecture',
                'color' => 'primary',
                'unlocked' => false
            ]
        ];

        // Filter out already unlocked achievements
        $unlockedTitles = array_column($achievements, 'title');
        foreach ($lockedAchievements as $achievement) {
            if (!in_array($achievement['title'], $unlockedTitles)) {
                $achievements[] = $achievement;
            }
        }

        return $achievements;
    }

    /**
     * Get motivational messages based on streak
     */
    public function getMotivationalMessage(User $user): string
    {
        $streak = $this->getCurrentStreak($user);
        $messages = [
            0 => "Chaque page tournée est une victoire. Commencez votre aventure littéraire aujourd'hui !",
            1 => "Premier jour de votre série ! Chaque petit pas compte. Continuez !",
            2 => "Deux jours d'affilée ! Vous êtes sur la bonne voie.",
            3 => "Trois jours ! L'habitude commence à se former. Bravo !",
            4 => "Quatre jours consécutifs ! Votre détermination est admirable.",
            5 => "Cinq jours ! Vous êtes un lecteur engagé. Continuez ainsi !",
            6 => "Presque une semaine ! Plus qu'un jour pour atteindre votre première semaine !",
            7 => "Une semaine complète ! Vous êtes incroyable !",
            10 => "10 jours consécutifs ! Votre discipline est remarquable !",
            14 => "Deux semaines ! Vous êtes un lecteur assidu !",
            21 => "Trois semaines ! Votre engagement est exceptionnel !",
            30 => "Un mois complet ! Vous êtes une légende !",
            50 => "50 jours consécutifs ! Votre dévouement est inspirant !",
            100 => "100 jours ! Vous êtes un maître de la lecture !"
        ];

        // Find the appropriate message
        $messageKey = 0;
        foreach ($messages as $days => $message) {
            if ($streak >= $days) {
                $messageKey = $days;
            } else {
                break;
            }
        }

        return $messages[$messageKey] ?? "Continuez votre belle série de lecture !";
    }
}
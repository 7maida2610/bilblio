<?php

namespace App\Controller\Api;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/inspection')]
#[IsGranted('ROLE_ADMIN')]
class InspectionController extends AbstractController
{
    #[Route('/activity-logs', name: 'api_inspection_activity_logs', methods: ['GET'])]
    public function getActivityLogs(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $logs = $activityLogRepository->findBy([], ['createdAt' => 'DESC'], 50);

        $data = array_map(function ($log) {
            return [
                'id' => $log->getId(),
                'user' => $log->getUser()->getUsername(),
                'action' => $log->getAction(),
                'description' => $log->getDescription(),
                'ipAddress' => $log->getIpAddress(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        return $this->json($data);
    }

    #[Route('/user-activity/{userId}', name: 'api_inspection_user_activity', methods: ['GET'])]
    public function getUserActivity(int $userId, ActivityLogRepository $activityLogRepository, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $logs = $activityLogRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 20);

        $data = array_map(function ($log) {
            return [
                'action' => $log->getAction(),
                'description' => $log->getDescription(),
                'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        return $this->json([
            'user' => $user->getUsername(),
            'activity' => $data
        ]);
    }

    #[Route('/system-stats', name: 'api_inspection_system_stats', methods: ['GET'])]
    public function getSystemStats(UserRepository $userRepository, ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isActive' => true]);
        $totalActivities = $activityLogRepository->count([]);

        // Get activities in last 24 hours
        $yesterday = new \DateTime('-1 day');
        $recentActivities = $activityLogRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt > :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalActivities' => $totalActivities,
            'recentActivities' => $recentActivities,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }
}
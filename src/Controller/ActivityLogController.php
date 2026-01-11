<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_logs_index', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        $activityLogs = $activityLogRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('activity_log/index.html.twig', [
            'activity_logs' => $activityLogs,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_logs_show', methods: ['GET'])]
    public function show(ActivityLogRepository $activityLogRepository, int $id): Response
    {
        $activityLog = $activityLogRepository->find($id);

        if (!$activityLog) {
            throw $this->createNotFoundException('Activity log not found');
        }

        return $this->render('activity_log/show.html.twig', [
            'activity_log' => $activityLog,
        ]);
    }
}
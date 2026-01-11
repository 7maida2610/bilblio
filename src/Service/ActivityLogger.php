<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function log(User $user, string $action, ?string $description = null, ?array $metadata = null): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $activityLog = new ActivityLog();
        $activityLog->setUser($user);
        $activityLog->setAction($action);
        $activityLog->setDescription($description);
        $activityLog->setMetadata($metadata);
        $activityLog->setIpAddress($request ? $request->getClientIp() : null);
        $activityLog->setUserAgent($request ? $request->headers->get('User-Agent') : null);
        $activityLog->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }

    public function logLogin(User $user): void
    {
        $this->log($user, 'LOGIN', 'User logged in');
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();
    }

    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT', 'User logged out');
    }

    public function logBookView(User $user, string $bookTitle): void
    {
        $this->log($user, 'BOOK_VIEW', "Viewed book: $bookTitle");
    }

    public function logBookCreate(User $user, string $bookTitle): void
    {
        $this->log($user, 'BOOK_CREATE', "Created book: $bookTitle");
    }

    public function logAdminAction(User $user, string $action, string $entity, ?int $entityId = null): void
    {
        $description = "Admin action: $action on $entity" . ($entityId ? " (ID: $entityId)" : "");
        $this->log($user, 'ADMIN_ACTION', $description, ['entity' => $entity, 'entity_id' => $entityId]);
    }
}
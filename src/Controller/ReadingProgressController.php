<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\ReadingProgress;
use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reading-progress')]
#[IsGranted('ROLE_USER')]
class ReadingProgressController extends AbstractController
{
    #[Route('/book/{id}', name: 'app_reading_progress_show', methods: ['GET'])]
    public function show(Livre $livre, ReadingProgressRepository $progressRepository): Response
    {
        // Check if user has access to this book's progress
        if (!$this->canAccessBookProgress($livre)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à la progression de lecture de ce livre.');
        }

        $progress = $progressRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre
        ]);

        if (!$progress) {
            $progress = new ReadingProgress();
            $progress->setUser($this->getUser());
            $progress->setLivre($livre);
        }

        return $this->render('reading_progress/show.html.twig', [
            'livre' => $livre,
            'progress' => $progress,
        ]);
    }

    #[Route('/book/{id}/update', name: 'app_reading_progress_update', methods: ['POST'])]
    public function update(Request $request, Livre $livre, EntityManagerInterface $entityManager, ReadingProgressRepository $progressRepository): JsonResponse
    {
        // Check if user has access to this book's progress
        if (!$this->canAccessBookProgress($livre)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $progress = $progressRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre
        ]);

        if (!$progress) {
            $progress = new ReadingProgress();
            $progress->setUser($this->getUser());
            $progress->setLivre($livre);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['currentPage'])) {
            $progress->setCurrentPage((int) $data['currentPage']);
        }

        if (isset($data['progressPercentage'])) {
            $progress->setProgressPercentage((int) $data['progressPercentage']);
        }

        if (isset($data['notes'])) {
            $progress->setNotes($data['notes']);
        }

        $progress->setLastReadAt(new \DateTime());

        $entityManager->persist($progress);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'progress' => [
                'percentage' => $progress->getProgressPercentage(),
                'currentPage' => $progress->getCurrentPage(),
                'isCompleted' => $progress->isCompleted(),
                'lastReadAt' => $progress->getLastReadAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/book/{id}/bookmark', name: 'app_reading_progress_bookmark', methods: ['POST'])]
    public function addBookmark(Request $request, Livre $livre, EntityManagerInterface $entityManager, ReadingProgressRepository $progressRepository): JsonResponse
    {
        // Check if user has access to this book's progress
        if (!$this->canAccessBookProgress($livre)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $progress = $progressRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre
        ]);

        if (!$progress) {
            $progress = new ReadingProgress();
            $progress->setUser($this->getUser());
            $progress->setLivre($livre);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['page']) && isset($data['title'])) {
            $progress->addBookmark((int) $data['page'], $data['title'], $data['note'] ?? null);
            $entityManager->persist($progress);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'bookmarks' => $progress->getBookmarks()
            ]);
        }

        return new JsonResponse(['error' => 'Invalid bookmark data'], 400);
    }

    #[Route('/book/{id}/bookmark/{index}/remove', name: 'app_reading_progress_remove_bookmark', methods: ['DELETE'])]
    public function removeBookmark(int $index, Livre $livre, EntityManagerInterface $entityManager, ReadingProgressRepository $progressRepository): JsonResponse
    {
        // Check if user has access to this book's progress
        if (!$this->canAccessBookProgress($livre)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $progress = $progressRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre
        ]);

        if ($progress) {
            $progress->removeBookmark($index);
            $entityManager->persist($progress);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'bookmarks' => $progress->getBookmarks()
            ]);
        }

        return new JsonResponse(['error' => 'Progress not found'], 404);
    }

    private function canAccessBookProgress(Livre $livre): bool
    {
        $user = $this->getUser();

        // Check if user owns the book
        if ($livre->getCreatedBy() === $user) {
            return true;
        }

        // Check if user has purchased the book
        if (method_exists($user, 'getOwnedBooks') && $user->getOwnedBooks()->contains($livre)) {
            return true;
        }

        // Check if user has an active or overdue loan for this book
        foreach ($livre->getLoans() as $loan) {
            if ($loan->getUser() === $user &&
                in_array($loan->getStatus(), ['active', 'overdue'])) {
                return true;
            }
        }

        return false;
    }
}
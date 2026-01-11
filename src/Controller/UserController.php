<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Auteur;
use App\Entity\ReadingProgress;
use App\Entity\ReadingGoal;
use App\Entity\Review;
use App\Form\ReadingGoalType;
use App\Form\ReviewType;
use App\Repository\LivreRepository;
use App\Repository\AuteurRepository;
use App\Repository\ReadingProgressRepository;
use App\Repository\ReadingGoalRepository;
use App\Repository\ReviewRepository;
use App\Service\GoalAchievementService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/wishlist/add/{id}', name: 'app_user_wishlist_add', methods: ['POST'])]
    public function addToWishlist(Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getWishlist()->contains($livre)) {
            $user->addToWishlist($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Book added to wishlist!');
        } else {
            $this->addFlash('warning', 'Book is already in your wishlist.');
        }

        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    #[Route('/wishlist/remove/{id}', name: 'app_user_wishlist_remove', methods: ['POST'])]
    public function removeFromWishlist(Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($user->getWishlist()->contains($livre)) {
            $user->removeFromWishlist($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Book removed from wishlist!');
        }

        return $this->redirectToRoute('app_user_wishlist');
    }

    #[Route('/wishlist', name: 'app_user_wishlist')]
    public function wishlist(): Response
    {
        return $this->render('user/wishlist.html.twig', [
            'wishlist' => $this->getUser()->getWishlist(),
        ]);
    }

    #[Route('/favorites/add/{id}', name: 'app_user_favorites_add', methods: ['POST'])]
    public function addToFavorites(Auteur $auteur, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getFavoriteAuthors()->contains($auteur)) {
            $user->addFavoriteAuthor($auteur);
            $entityManager->flush();
            $this->addFlash('success', 'Author added to favorites!');
        } else {
            $this->addFlash('warning', 'Author is already in your favorites.');
        }

        return $this->redirectToRoute('app_auteur_show', ['id' => $auteur->getId()]);
    }

    #[Route('/favorites/remove/{id}', name: 'app_user_favorites_remove', methods: ['POST'])]
    public function removeFromFavorites(Auteur $auteur, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($user->getFavoriteAuthors()->contains($auteur)) {
            $user->removeFavoriteAuthor($auteur);
            $entityManager->flush();
            $this->addFlash('success', 'Author removed from favorites!');
        }

        return $this->redirectToRoute('app_user_favorites');
    }

    #[Route('/favorites', name: 'app_user_favorites')]
    public function favorites(): Response
    {
        return $this->render('user/favorites.html.twig', [
            'favorites' => $this->getUser()->getFavoriteAuthors(),
        ]);
    }

    #[Route('/reading-progress/update/{id}', name: 'app_user_reading_progress_update', methods: ['POST'])]
    public function updateReadingProgress(Request $request, Livre $livre, EntityManagerInterface $entityManager, ReadingProgressRepository $progressRepository, ReadingGoalRepository $goalRepository): Response
    {
        $user = $this->getUser();
        $progressPercentage = (int) $request->request->get('progress_percentage', 0);

        // Validate progress percentage
        $progressPercentage = max(0, min(100, $progressPercentage));

        $progress = $progressRepository->findOneBy(['user' => $user, 'livre' => $livre]);

        // Check if book is already completed
        if ($progress && $progress->isCompleted()) {
            $this->addFlash('warning', 'This book is already completed. You cannot update the progress further.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        if (!$progress) {
            $progress = new ReadingProgress();
            $progress->setUser($user);
            $progress->setLivre($livre);
            $entityManager->persist($progress);
        }

        $wasCompleted = $progress->isCompleted();
        $progress->setProgressPercentage($progressPercentage);
        $progress->setLastReadAt(new \DateTime());

        // Check if book was just completed
        if ($progressPercentage >= 100 && !$wasCompleted) {
            $progress->setIsCompleted(true);

            // Update reading goals and check for achievements
            $this->goalAchievementService->checkAndUpdateGoals($user);
        }

        $entityManager->flush();

        $message = $progress->isCompleted() ? 'Book marked as completed!' : 'Reading progress updated!';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }


    #[Route('/reading-history', name: 'app_user_reading_history')]
    public function readingHistory(ReadingProgressRepository $progressRepository): Response
    {
        $user = $this->getUser();

        return $this->render('user/reading_history.html.twig', [
            'readingHistory' => $progressRepository->findByUser($user),
        ]);
    }

    #[Route('/owned-books/add/{id}', name: 'app_user_owned_books_add', methods: ['POST'])]
    public function addOwnedBook(Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getOwnedBooks()->contains($livre)) {
            $user->addOwnedBook($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Book added to your library!');
        } else {
            $this->addFlash('warning', 'Book is already in your library.');
        }

        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    #[Route('/owned-books/remove/{id}', name: 'app_user_owned_books_remove', methods: ['POST'])]
    public function removeOwnedBook(Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($user->getOwnedBooks()->contains($livre)) {
            $user->removeOwnedBook($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Book removed from your library!');
        }

        return $this->redirectToRoute('app_user_owned_books');
    }

    #[Route('/review/{id}', name: 'app_user_review_create', methods: ['GET', 'POST'])]
    public function createReview(Request $request, Livre $livre, EntityManagerInterface $entityManager, ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();

        // Check if user has completed this book
        $progress = $entityManager->getRepository(ReadingProgress::class)->findOneBy([
            'user' => $user,
            'livre' => $livre,
            'isCompleted' => true
        ]);

        if (!$progress) {
            $this->addFlash('warning', 'You can only review books you have completed reading.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        // Check if review already exists
        $existingReview = $reviewRepository->findOneBy([
            'user' => $user,
            'livre' => $livre
        ]);

        if ($existingReview) {
            $this->addFlash('info', 'You have already reviewed this book.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $review = new Review();
        $review->setUser($user);
        $review->setLivre($livre);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Your review has been submitted!');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('user/review_form.html.twig', [
            'reviewForm' => $form->createView(),
            'livre' => $livre,
        ]);
    }

    #[Route('/goals', name: 'app_user_goals', methods: ['GET', 'POST'])]
    public function manageGoals(Request $request, EntityManagerInterface $entityManager, ReadingGoalRepository $goalRepository): Response
    {
        $user = $this->getUser();

        $goal = new ReadingGoal();
        $goal->setUser($user);

        $form = $this->createForm(ReadingGoalType::class, $goal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if goal of this type already exists
            $existingGoal = $goalRepository->findByType($user, $goal->getGoalType());
            if ($existingGoal) {
                $existingGoal->setTargetValue($goal->getTargetValue());
                $existingGoal->setCurrentValue(0); // Reset progress
                $entityManager->flush();
                $this->addFlash('success', 'Goal updated successfully!');
            } else {
                $entityManager->persist($goal);
                $entityManager->flush();
                $this->addFlash('success', 'Goal created successfully!');
            }

            return $this->redirectToRoute('app_user_goals');
        }

        return $this->render('user/goals.html.twig', [
            'goalForm' => $form->createView(),
            'activeGoals' => $goalRepository->findActiveGoals($user),
            'achievedGoals' => $goalRepository->findAchievedGoals($user),
        ]);
    }

    #[Route('/goals/{id}/remove', name: 'app_user_goals_remove', methods: ['POST'])]
    public function removeGoal(ReadingGoal $goal, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Check if the goal belongs to the current user
        if ($goal->getUser() !== $user) {
            throw $this->createAccessDeniedException('You cannot remove this goal.');
        }

        $entityManager->remove($goal);
        $entityManager->flush();

        $this->addFlash('success', 'Goal removed successfully!');

        return $this->redirectToRoute('app_user_goals');
    }

    #[Route('/progress/update/{id}', name: 'app_user_update_progress', methods: ['POST'])]
    public function updateProgress(Request $request, Livre $livre, EntityManagerInterface $entityManager, ReadingProgressRepository $progressRepository, ReadingGoalRepository $goalRepository): Response
    {
        $user = $this->getUser();
        $currentPage = (int) $request->request->get('current_page', 0);

        // Validate current page
        if ($currentPage < 0) {
            $currentPage = 0;
        }

        $progress = $progressRepository->findOneBy(['user' => $user, 'livre' => $livre]);

        if (!$progress) {
            $progress = new ReadingProgress();
            $progress->setUser($user);
            $progress->setLivre($livre);
            $entityManager->persist($progress);
        }

        // Check if book is already completed
        if ($progress->isCompleted()) {
            $this->addFlash('warning', 'This book is already completed. You cannot update the progress further.');
            return $this->redirectToRoute('app_backoffice_dashboard');
        }

        $wasCompleted = $progress->isCompleted();
        $progress->setCurrentPage($currentPage);
        $progress->setLastReadAt(new \DateTime());

        // Update reading goals and check for achievements
        $this->goalAchievementService->checkAndUpdateGoals($user);

        // Check if book was just completed
        if ($progress->isCompleted() && !$wasCompleted) {
            // Additional logic for book completion if needed
        }

        $entityManager->flush();

        $message = $progress->isCompleted() ? 'Book marked as completed!' : 'Reading progress updated!';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_backoffice_dashboard');
    }

    #[Route('/owned-books', name: 'app_user_owned_books')]
    public function ownedBooks(): Response
    {
        return $this->render('user/owned_books.html.twig', [
            'ownedBooks' => $this->getUser()->getPurchasedBooks(),
        ]);
    }
}
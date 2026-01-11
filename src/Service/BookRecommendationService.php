<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\LivreRepository;
use App\Repository\ReviewRepository;
use App\Repository\ActivityLogRepository;

class BookRecommendationService
{
    public function __construct(
        private LivreRepository $livreRepository,
        private ReviewRepository $reviewRepository,
        private ActivityLogRepository $activityLogRepository
    ) {}

    /**
     * Get personalized book recommendations for a user
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 6): array
    {
        $recommendations = [];

        // Get user's favorite categories based on reading history and reviews
        $favoriteCategories = $this->getUserFavoriteCategories($user);

        // Get user's favorite authors
        $favoriteAuthors = $user->getFavoriteAuthors()->toArray();

        // Get books in user's preferred categories
        if (!empty($favoriteCategories)) {
            foreach ($favoriteCategories as $category) {
                $categoryBooks = $this->livreRepository->findBy(['categorie' => $category], [], $limit);
                $recommendations = array_merge($recommendations, $categoryBooks);
            }
        }

        // Get books by favorite authors
        if (!empty($favoriteAuthors)) {
            foreach ($favoriteAuthors as $author) {
                $authorBooks = $this->livreRepository->findBy(['auteur' => $author], [], $limit);
                $recommendations = array_merge($recommendations, $authorBooks);
            }
        }

        // Get trending books (most reviewed recently)
        $trendingBooks = $this->getTrendingBooks($limit);
        $recommendations = array_merge($recommendations, $trendingBooks);

        // Remove duplicates and books user already owns
        $ownedBookIds = array_map(fn($book) => $book->getId(), $user->getPurchasedBooks()->toArray());
        $recommendations = array_filter($recommendations, function($book) use ($ownedBookIds) {
            return !in_array($book->getId(), $ownedBookIds);
        });

        // Remove duplicates by ID
        $uniqueRecommendations = [];
        $seenIds = [];
        foreach ($recommendations as $book) {
            if (!in_array($book->getId(), $seenIds)) {
                $uniqueRecommendations[] = $book;
                $seenIds[] = $book->getId();
            }
        }

        return array_slice($uniqueRecommendations, 0, $limit);
    }

    /**
     * Get user's favorite categories based on reading history and reviews
     */
    private function getUserFavoriteCategories(User $user): array
    {
        $categories = [];

        // Get categories from owned books
        foreach ($user->getPurchasedBooks() as $book) {
            if ($book->getCategorie()) {
                $categoryId = $book->getCategorie()->getId();
                if (!isset($categories[$categoryId])) {
                    $categories[$categoryId] = [
                        'category' => $book->getCategorie(),
                        'score' => 0
                    ];
                }
                $categories[$categoryId]['score'] += 2; // Weight owned books higher
            }
        }

        // Get categories from highly rated reviews
        $userReviews = $this->reviewRepository->findBy(['user' => $user]);
        foreach ($userReviews as $review) {
            if ($review->getRating() >= 4 && $review->getLivre()->getCategorie()) {
                $categoryId = $review->getLivre()->getCategorie()->getId();
                if (!isset($categories[$categoryId])) {
                    $categories[$categoryId] = [
                        'category' => $review->getLivre()->getCategorie(),
                        'score' => 0
                    ];
                }
                $categories[$categoryId]['score'] += 1;
            }
        }

        // Sort by score and return top categories
        usort($categories, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn($item) => $item['category'], array_slice($categories, 0, 3));
    }

    /**
     * Get trending books based on recent activity
     */
    private function getTrendingBooks(int $limit): array
    {
        // Get books with most activity in the last 30 days
        $thirtyDaysAgo = new \DateTime('-30 days');

        $qb = $this->activityLogRepository->createQueryBuilder('a');
        $qb->select('l.id, COUNT(a.id) as activity_count')
           ->join('a.livre', 'l')
           ->where('a.createdAt >= :thirty_days_ago')
           ->setParameter('thirty_days_ago', $thirtyDaysAgo)
           ->groupBy('l.id')
           ->orderBy('activity_count', 'DESC')
           ->setMaxResults($limit * 2); // Get more to filter

        $results = $qb->getQuery()->getResult();

        $bookIds = array_map(fn($result) => $result['id'], $results);

        if (empty($bookIds)) {
            return [];
        }

        return $this->livreRepository->findBy(['id' => $bookIds]);
    }

    /**
     * Get books similar to a given book
     */
    public function getSimilarBooks(\App\Entity\Livre $book, int $limit = 4): array
    {
        $similarBooks = [];

        // Same category
        if ($book->getCategorie()) {
            $categoryBooks = $this->livreRepository->findBy([
                'categorie' => $book->getCategorie()
            ], [], $limit + 1); // +1 to account for the current book

            foreach ($categoryBooks as $categoryBook) {
                if ($categoryBook->getId() !== $book->getId()) {
                    $similarBooks[] = $categoryBook;
                }
            }
        }

        // Same author
        if ($book->getAuteur()) {
            $authorBooks = $this->livreRepository->findBy([
                'auteur' => $book->getAuteur()
            ], [], $limit + 1);

            foreach ($authorBooks as $authorBook) {
                if ($authorBook->getId() !== $book->getId() && !in_array($authorBook, $similarBooks, true)) {
                    $similarBooks[] = $authorBook;
                }
            }
        }

        // Same publisher
        if ($book->getEditeur()) {
            $publisherBooks = $this->livreRepository->findBy([
                'editeur' => $book->getEditeur()
            ], [], $limit + 1);

            foreach ($publisherBooks as $publisherBook) {
                if ($publisherBook->getId() !== $book->getId() && !in_array($publisherBook, $similarBooks, true)) {
                    $similarBooks[] = $publisherBook;
                }
            }
        }

        return array_slice($similarBooks, 0, $limit);
    }

    /**
     * Get seasonal recommendations
     */
    public function getSeasonalRecommendations(int $limit = 6): array
    {
        $currentMonth = (int) date('n');
        $seasonalBooks = [];

        // Define seasonal themes
        $seasonalThemes = [
            'spring' => [[3, 4, 5], ['spring', 'renewal', 'growth', 'adventure']],
            'summer' => [[6, 7, 8], ['summer', 'beach', 'vacation', 'light']],
            'autumn' => [[9, 10, 11], ['autumn', 'harvest', 'mystery', 'cozy']],
            'winter' => [[12, 1, 2], ['winter', 'holiday', 'family', 'warmth']]
        ];

        $currentSeason = null;
        foreach ($seasonalThemes as $season => $data) {
            [$months, $themes] = $data;
            if (in_array($currentMonth, $months)) {
                $currentSeason = $themes;
                break;
            }
        }

        if ($currentSeason) {
            // This would require a more sophisticated search based on book titles, descriptions, or tags
            // For now, return some random books as seasonal recommendations
            $allBooks = $this->livreRepository->findBy([], ['dateEdition' => 'DESC'], $limit * 2);
            shuffle($allBooks);
            $seasonalBooks = array_slice($allBooks, 0, $limit);
        }

        return $seasonalBooks;
    }

    /**
     * Get "because you read" recommendations
     */
    public function getBecauseYouRead(User $user, int $limit = 3): array
    {
        $recommendations = [];

        // Get user's recently read books
        $recentlyRead = $this->activityLogRepository->findByUser($user, 5);

        if (empty($recentlyRead)) {
            return [];
        }

        // Get similar books based on recently read books
        foreach ($recentlyRead as $activity) {
            $similarBooks = $this->getSimilarBooks($activity->getLivre(), 2);
            $recommendations = array_merge($recommendations, $similarBooks);
        }

        // Remove duplicates and owned books
        $ownedBookIds = array_map(fn($book) => $book->getId(), $user->getPurchasedBooks()->toArray());
        $recommendations = array_filter($recommendations, function($book) use ($ownedBookIds) {
            return !in_array($book->getId(), $ownedBookIds);
        });

        // Remove duplicates
        $uniqueRecommendations = [];
        $seenIds = [];
        foreach ($recommendations as $book) {
            if (!in_array($book->getId(), $seenIds)) {
                $uniqueRecommendations[] = $book;
                $seenIds[] = $book->getId();
            }
        }

        return array_slice($uniqueRecommendations, 0, $limit);
    }
}
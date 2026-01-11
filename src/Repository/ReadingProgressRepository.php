<?php

namespace App\Repository;

use App\Entity\ReadingProgress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingProgress>
 */
class ReadingProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingProgress::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rp.lastReadAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyRead(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('rp.lastReadAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findCompletedBooks(User $user): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.isCompleted = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', true)
            ->orderBy('rp.lastReadAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalProgress(User $user): int
    {
        return $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->andWhere('rp.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count completed books in a specific time period
     */
    public function countCompletedBooksInPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.isCompleted = :completed')
            ->andWhere('rp.lastReadAt >= :startDate')
            ->andWhere('rp.lastReadAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('completed', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count pages read in a specific time period
     */
    public function countPagesReadInPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        $result = $this->createQueryBuilder('rp')
            ->select('SUM(rp.currentPage)')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.lastReadAt >= :startDate')
            ->andWhere('rp.lastReadAt <= :endDate')
            ->andWhere('rp.currentPage IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Find reading progress in a specific time period
     */
    public function findProgressInPeriod(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.lastReadAt >= :startDate')
            ->andWhere('rp.lastReadAt <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get reading statistics for a user
     */
    public function getReadingStatistics(User $user): array
    {
        $stats = [];

        // Total books completed
        $stats['total_completed_books'] = $this->countCompletedBooks($user);

        // Current month statistics
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');
        $startOfMonth = new \DateTime("$currentYear-$currentMonth-01 00:00:00");
        $endOfMonth = new \DateTime("$currentYear-$currentMonth-" . date('t', strtotime("$currentYear-$currentMonth-01")) . " 23:59:59");

        $stats['books_this_month'] = $this->countCompletedBooksInPeriod($user, $startOfMonth, $endOfMonth);
        $stats['pages_this_month'] = $this->countPagesReadInPeriod($user, $startOfMonth, $endOfMonth);

        // Current year statistics
        $startOfYear = new \DateTime("$currentYear-01-01 00:00:00");
        $endOfYear = new \DateTime("$currentYear-12-31 23:59:59");

        $stats['books_this_year'] = $this->countCompletedBooksInPeriod($user, $startOfYear, $endOfYear);
        $stats['pages_this_year'] = $this->countPagesReadInPeriod($user, $startOfYear, $endOfYear);

        // Currently reading
        $stats['currently_reading'] = $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.isCompleted = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', false)
            ->getQuery()
            ->getSingleScalarResult();

        return $stats;
    }

    /**
     * Count total completed books for a user
     */
    private function countCompletedBooks(User $user): int
    {
        return $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->andWhere('rp.user = :user')
            ->andWhere('rp.isCompleted = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
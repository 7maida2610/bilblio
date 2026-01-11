<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingReviews(User $user): array
    {
        // Reviews that user hasn't written yet for completed books
        return $this->createQueryBuilder('r')
            ->leftJoin('r.livre', 'l')
            ->leftJoin('App\Entity\ReadingProgress', 'rp', 'WITH', 'rp.livre = l AND rp.user = :user AND rp.isCompleted = true')
            ->andWhere('r.user = :user')
            ->andWhere('rp.id IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getAverageRatingForBook(int $livreId): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avg_rating')
            ->andWhere('r.livre = :livreId')
            ->setParameter('livreId', $livreId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}
<?php

namespace App\Repository;

use App\Entity\ReadingGoal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadingGoal>
 */
class ReadingGoalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadingGoal::class);
    }

    public function findActiveGoals(User $user): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('rg')
            ->andWhere('rg.user = :user')
            ->andWhere('rg.startDate <= :now')
            ->andWhere('rg.endDate >= :now')
            ->andWhere('rg.currentValue < rg.targetValue') // Exclude achieved goals
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('rg.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(User $user, string $goalType): ?ReadingGoal
    {
        return $this->createQueryBuilder('rg')
            ->andWhere('rg.user = :user')
            ->andWhere('rg.goalType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $goalType)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAchievedGoals(User $user): array
    {
        return $this->createQueryBuilder('rg')
            ->andWhere('rg.user = :user')
            ->andWhere('rg.currentValue >= rg.targetValue')
            ->setParameter('user', $user)
            ->orderBy('rg.endDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
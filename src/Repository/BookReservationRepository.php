<?php

namespace App\Repository;

use App\Entity\BookReservation;
use App\Entity\Livre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookReservation>
 */
class BookReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookReservation::class);
    }

    /**
     * Find active reservations for a book ordered by position
     */
    public function findActiveReservationsForBook(Livre $livre): array
    {
        return $this->createQueryBuilder('br')
            ->andWhere('br.livre = :livre')
            ->andWhere('br.isActive = :active')
            ->setParameter('livre', $livre)
            ->setParameter('active', true)
            ->orderBy('br.position', 'ASC')
            ->addOrderBy('br.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user's active reservation for a book
     */
    public function findUserActiveReservationForBook(User $user, Livre $livre): ?BookReservation
    {
        return $this->createQueryBuilder('br')
            ->andWhere('br.user = :user')
            ->andWhere('br.livre = :livre')
            ->andWhere('br.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('livre', $livre)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count active reservations for a book
     */
    public function countActiveReservationsForBook(Livre $livre): int
    {
        return $this->createQueryBuilder('br')
            ->select('COUNT(br.id)')
            ->andWhere('br.livre = :livre')
            ->andWhere('br.isActive = :active')
            ->setParameter('livre', $livre)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find reservations that can be notified (book became available)
     */
    public function findReservationsToNotify(): array
    {
        return $this->createQueryBuilder('br')
            ->join('br.livre', 'l')
            ->andWhere('br.isActive = :active')
            ->andWhere('br.notifiedAt IS NULL')
            ->andWhere('l.isBorrowable = :borrowable')
            ->setParameter('active', true)
            ->setParameter('borrowable', true)
            ->orderBy('br.position', 'ASC')
            ->addOrderBy('br.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Update positions for active reservations of a book
     */
    public function updatePositionsForBook(Livre $livre): void
    {
        $reservations = $this->findActiveReservationsForBook($livre);

        $position = 1;
        foreach ($reservations as $reservation) {
            $reservation->setPosition($position);
            $position++;
        }

        $this->_em->flush();
    }
}
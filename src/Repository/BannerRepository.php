<?php

namespace App\Repository;

use App\Entity\Banner;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Banner>
 */
class BannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Banner::class);
    }

    /**
     * Find active banners for a specific position
     */
    public function findActiveBanners(string $position, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.position = :position')
            ->setParameter('status', Banner::STATUS_ACTIVE)
            ->setParameter('position', $position)
            ->orderBy('b.priority', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC');

        // Add date constraints
        $now = new \DateTime();
        $qb->andWhere('(b.startDate IS NULL OR b.startDate <= :now)')
           ->andWhere('(b.endDate IS NULL OR b.endDate >= :now)')
           ->setParameter('now', $now);

        $banners = $qb->getQuery()->getResult();

        // Filter by target audience and hidden preferences if user is provided
        if ($user) {
            $banners = array_filter($banners, function (Banner $banner) use ($user) {
                // Check if banner is visible for user's roles
                if (!$banner->isVisibleForUser($user)) {
                    return false;
                }
                
                // Check if user has hidden this banner
                $em = $this->getEntityManager();
                $preferenceRepo = $em->getRepository(\App\Entity\UserBannerPreference::class);
                $preference = $preferenceRepo->findOneBy(['user' => $user, 'banner' => $banner]);
                
                // Hide if preference exists and is marked as hidden
                if ($preference && $preference->isHidden()) {
                    return false;
                }
                
                return true;
            });
        }

        return $banners;
    }

    /**
     * Find all active banners for display
     */
    public function findAllActiveBanners(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', Banner::STATUS_ACTIVE)
            ->orderBy('b.priority', 'DESC')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('b.createdAt', 'DESC');

        // Add date constraints
        $now = new \DateTime();
        $qb->andWhere('(b.startDate IS NULL OR b.startDate <= :now)')
           ->andWhere('(b.endDate IS NULL OR b.endDate >= :now)')
           ->setParameter('now', $now);

        $banners = $qb->getQuery()->getResult();

        // Filter by target audience if user is provided
        if ($user) {
            $banners = array_filter($banners, function (Banner $banner) use ($user) {
                return $banner->isVisibleForUser($user);
            });
        }

        return $banners;
    }

    /**
     * Find banners by type
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.type = :type')
            ->setParameter('type', $type)
            ->orderBy('b.priority', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find banners by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->orderBy('b.priority', 'DESC')
            ->addOrderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired banners that need to be updated
     */
    public function findExpiredBanners(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.endDate IS NOT NULL')
            ->andWhere('b.endDate < :now')
            ->setParameter('status', Banner::STATUS_ACTIVE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find scheduled banners that should become active
     */
    public function findScheduledBannersToActivate(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.startDate IS NOT NULL')
            ->andWhere('b.startDate <= :now')
            ->setParameter('status', Banner::STATUS_SCHEDULED)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Update expired banners status
     */
    public function updateExpiredBanners(): int
    {
        $expiredBanners = $this->findExpiredBanners();

        foreach ($expiredBanners as $banner) {
            $banner->setStatus(Banner::STATUS_EXPIRED);
        }

        $this->_em->flush();

        return count($expiredBanners);
    }

    /**
     * Activate scheduled banners
     */
    public function activateScheduledBanners(): int
    {
        $scheduledBanners = $this->findScheduledBannersToActivate();

        foreach ($scheduledBanners as $banner) {
            $banner->setStatus(Banner::STATUS_ACTIVE);
        }

        $this->_em->flush();

        return count($scheduledBanners);
    }

    /**
     * Get banner statistics
     */
    public function getBannerStatistics(): array
    {
        $stats = [
            'total' => $this->count([]),
            'active' => $this->count(['status' => Banner::STATUS_ACTIVE]),
            'inactive' => $this->count(['status' => Banner::STATUS_INACTIVE]),
            'scheduled' => $this->count(['status' => Banner::STATUS_SCHEDULED]),
            'expired' => $this->count(['status' => Banner::STATUS_EXPIRED]),
            'promotion' => $this->count(['type' => Banner::TYPE_PROMOTION]),
            'warning' => $this->count(['type' => Banner::TYPE_WARNING]),
            'announcement' => $this->count(['type' => Banner::TYPE_ANNOUNCEMENT]),
            'info' => $this->count(['type' => Banner::TYPE_INFO]),
            'top' => $this->count(['position' => Banner::POSITION_TOP]),
            'bottom' => $this->count(['position' => Banner::POSITION_BOTTOM]),
            'sidebar' => $this->count(['position' => Banner::POSITION_SIDEBAR]),
            'popup' => $this->count(['position' => Banner::POSITION_POPUP]),
        ];

        return $stats;
    }

    /**
     * Find banners created by a specific user
     */
    public function findByCreator(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search banners by title or content
     */
    public function searchBanners(string $query): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.title LIKE :query OR b.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save a banner
     */
    public function save(Banner $banner, bool $flush = false): void
    {
        $this->getEntityManager()->persist($banner);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a banner
     */
    public function remove(Banner $banner, bool $flush = false): void
    {
        $this->getEntityManager()->remove($banner);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
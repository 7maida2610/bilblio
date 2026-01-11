<?php

namespace App\Repository;

use App\Entity\Banner;
use App\Entity\User;
use App\Entity\UserBannerPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBannerPreference>
 */
class UserBannerPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBannerPreference::class);
    }

    /**
     * Find or create a preference for a user and banner
     */
    public function findOrCreate(User $user, Banner $banner): UserBannerPreference
    {
        $preference = $this->findOneBy(['user' => $user, 'banner' => $banner]);

        if (!$preference) {
            $preference = new UserBannerPreference();
            $preference->setUser($user);
            $preference->setBanner($banner);
            $preference->setHidden(false);
        }

        return $preference;
    }

    /**
     * Check if a banner is hidden for a user
     */
    public function isBannerHidden(User $user, Banner $banner): bool
    {
        $preference = $this->findOneBy(['user' => $user, 'banner' => $banner]);

        return $preference && $preference->isHidden();
    }

    /**
     * Get hidden banners for a user
     */
    public function getHiddenBanners(User $user): array
    {
        return $this->createQueryBuilder('ubp')
            ->andWhere('ubp.user = :user')
            ->andWhere('ubp.hidden = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Save a preference
     */
    public function save(UserBannerPreference $preference, bool $flush = false): void
    {
        $this->getEntityManager()->persist($preference);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

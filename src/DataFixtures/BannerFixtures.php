<?php

namespace App\DataFixtures;

use App\Entity\Banner;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

class BannerFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get admin user
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (!$admin) {
            return; // Skip if admin user doesn't exist
        }

        // Welcome banner for new users
        $welcomeBanner = new Banner();
        $welcomeBanner->setTitle('Bienvenue sur BiblioApp !');
        $welcomeBanner->setContent('<p>Découvrez notre collection exceptionnelle de livres. Empruntez, achetez et plongez dans l\'univers de la lecture !</p>');
        $welcomeBanner->setType(Banner::TYPE_INFO);
        $welcomeBanner->setPosition(Banner::POSITION_TOP);
        $welcomeBanner->setStatus(Banner::STATUS_ACTIVE);
        $welcomeBanner->setPriority(10);
        $welcomeBanner->setTargetAudience(['ROLE_USER']);
        $welcomeBanner->setCreatedBy($admin);
        $manager->persist($welcomeBanner);

        // Promotion banner
        $promoBanner = new Banner();
        $promoBanner->setTitle('Promotion Spéciale !');
        $promoBanner->setContent('<p><strong>-20% sur tous les livres de science-fiction</strong> cette semaine seulement ! Profitez-en pour enrichir votre bibliothèque.</p>');
        $promoBanner->setType(Banner::TYPE_PROMOTION);
        $promoBanner->setPosition(Banner::POSITION_TOP);
        $promoBanner->setStatus(Banner::STATUS_ACTIVE);
        $promoBanner->setPriority(15);
        $promoBanner->setLink('/livre?category=science-fiction');
        $promoBanner->setLinkText('Voir les offres');
        $promoBanner->setTargetAudience(['ROLE_USER']);
        $promoBanner->setCreatedBy($admin);
        $manager->persist($promoBanner);

        // Loan reminder banner
        $loanBanner = new Banner();
        $loanBanner->setTitle('Pensez à vos emprunts');
        $loanBanner->setContent('<p>N\'oubliez pas de retourner vos livres empruntés avant la date d\'échéance pour éviter les pénalités.</p>');
        $loanBanner->setType(Banner::TYPE_WARNING);
        $loanBanner->setPosition(Banner::POSITION_SIDEBAR);
        $loanBanner->setStatus(Banner::STATUS_ACTIVE);
        $loanBanner->setPriority(8);
        $loanBanner->setLink('/loan');
        $loanBanner->setLinkText('Voir mes emprunts');
        $loanBanner->setTargetAudience(['ROLE_USER']);
        $loanBanner->setCreatedBy($admin);
        $manager->persist($loanBanner);

        // New arrivals announcement
        $newArrivalsBanner = new Banner();
        $newArrivalsBanner->setTitle('Nouveautés Disponibles');
        $newArrivalsBanner->setContent('<p>Découvrez nos dernières acquisitions ! De nouveaux livres passionnants vous attendent.</p>');
        $newArrivalsBanner->setType(Banner::TYPE_ANNOUNCEMENT);
        $newArrivalsBanner->setPosition(Banner::POSITION_BOTTOM);
        $newArrivalsBanner->setStatus(Banner::STATUS_ACTIVE);
        $newArrivalsBanner->setPriority(5);
        $newArrivalsBanner->setLink('/livre?sort=newest');
        $newArrivalsBanner->setLinkText('Explorer les nouveautés');
        $newArrivalsBanner->setTargetAudience(['ROLE_USER']);
        $newArrivalsBanner->setCreatedBy($admin);
        $manager->persist($newArrivalsBanner);

        // Admin announcement
        $adminBanner = new Banner();
        $adminBanner->setTitle('Maintenance Programmée');
        $adminBanner->setContent('<p>Une maintenance technique est prévue ce dimanche de 2h à 4h. Le service sera temporairement indisponible.</p>');
        $adminBanner->setType(Banner::TYPE_WARNING);
        $adminBanner->setPosition(Banner::POSITION_TOP);
        $adminBanner->setStatus(Banner::STATUS_ACTIVE);
        $adminBanner->setPriority(20);
        $adminBanner->setTargetAudience(['ROLE_USER', 'ROLE_MODERATOR', 'ROLE_ADMIN']);
        $adminBanner->setCreatedBy($admin);
        $manager->persist($adminBanner);

        // Reading goal encouragement
        $goalBanner = new Banner();
        $goalBanner->setTitle('Objectifs de Lecture');
        $goalBanner->setContent('<p>Définissez vos objectifs de lecture mensuels et suivez vos progrès ! Une façon motivante de cultiver vos habitudes de lecture.</p>');
        $goalBanner->setType(Banner::TYPE_INFO);
        $goalBanner->setPosition(Banner::POSITION_SIDEBAR);
        $goalBanner->setStatus(Banner::STATUS_ACTIVE);
        $goalBanner->setPriority(6);
        $goalBanner->setLink('/profile#goals');
        $goalBanner->setLinkText('Gérer mes objectifs');
        $goalBanner->setTargetAudience(['ROLE_USER']);
        $goalBanner->setCreatedBy($admin);
        $manager->persist($goalBanner);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
        ];
    }
}
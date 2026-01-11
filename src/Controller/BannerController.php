<?php

namespace App\Controller;

use App\Entity\Banner;
use App\Entity\User;
use App\Entity\UserBannerPreference;
use App\Form\BannerType;
use App\Repository\BannerRepository;
use App\Repository\UserBannerPreferenceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/banners')]
#[IsGranted('ROLE_ADMIN')]
class BannerController extends AbstractController
{
    public function __construct(
        private BannerRepository $bannerRepository,
        private UserBannerPreferenceRepository $preferenceRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', name: 'app_admin_banner_custom', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status');
        $type = $request->query->get('type');
        $search = $request->query->get('search');

        // Build query
        $qb = $this->bannerRepository->createQueryBuilder('b');

        if ($status) {
            $qb->andWhere('b.status = :status')->setParameter('status', $status);
        }

        if ($type) {
            $qb->andWhere('b.type = :type')->setParameter('type', $type);
        }

        if ($search) {
            $qb->andWhere('(b.title LIKE :search OR b.content LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('b.createdAt', 'DESC');

        $banners = $qb->setFirstResult(($page - 1) * 10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $total = $this->bannerRepository->count([]);
        $stats = $this->bannerRepository->getBannerStatistics();

        return $this->render('admin/banner/index.html.twig', [
            'banners' => $banners,
            'stats' => $stats,
            'page' => $page,
            'total' => $total,
            'pageSize' => 10,
            'status' => $status,
            'type' => $type,
            'search' => $search,
            'statuses' => Banner::STATUSES,
            'types' => Banner::TYPES,
        ]);
    }

    #[Route('/create', name: 'app_admin_banner_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $banner = new Banner();
        $banner->setCreatedBy($this->getUser());
        $banner->setType(Banner::TYPE_INFO);
        $banner->setPosition(Banner::POSITION_TOP);
        $banner->setStatus(Banner::STATUS_INACTIVE);

        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bannerRepository->save($banner, true);
            $this->addFlash('success', 'Bannière créée avec succès.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        return $this->render('admin/banner/form.html.twig', [
            'form' => $form->createView(),
            'banner' => $banner,
            'isEdit' => false,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_admin_banner_edit', methods: ['GET', 'POST'])]
    public function edit(Banner $banner, Request $request): Response
    {
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->bannerRepository->save($banner, true);
            $this->addFlash('success', 'Bannière modifiée avec succès.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        return $this->render('admin/banner/form.html.twig', [
            'form' => $form->createView(),
            'banner' => $banner,
            'isEdit' => true,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_admin_banner_delete', methods: ['POST'])]
    public function delete(Banner $banner, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $banner->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        $this->bannerRepository->remove($banner, true);
        $this->addFlash('success', 'Bannière supprimée avec succès.');
        return $this->redirectToRoute('app_admin_banner_custom');
    }

    #[Route('/activate/{id}', name: 'app_admin_banner_activate', methods: ['POST'])]
    public function activate(Banner $banner, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('activate' . $banner->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        $banner->setStatus(Banner::STATUS_ACTIVE);
        $this->bannerRepository->save($banner, true);

        $this->addFlash('success', 'Bannière activée avec succès.');
        return $this->redirectToRoute('app_admin_banner_custom');
    }

    #[Route('/deactivate/{id}', name: 'app_admin_banner_deactivate', methods: ['POST'])]
    public function deactivate(Banner $banner, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('deactivate' . $banner->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        $banner->setStatus(Banner::STATUS_INACTIVE);
        $this->bannerRepository->save($banner, true);

        $this->addFlash('success', 'Bannière désactivée avec succès.');
        return $this->redirectToRoute('app_admin_banner_custom');
    }

    #[Route('/preview/{id}', name: 'app_admin_banner_preview', methods: ['GET'])]
    public function preview(Banner $banner): Response
    {
        return $this->render('admin/banner/preview.html.twig', [
            'banner' => $banner,
        ]);
    }

    #[Route('/details/{id}', name: 'app_admin_banner_details', methods: ['GET'])]
    public function details(Banner $banner, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        // Get users who dismissed this banner
        $dismissedCount = $this->preferenceRepository->count([
            'banner' => $banner,
            'hidden' => true
        ]);

        $qb = $this->preferenceRepository->createQueryBuilder('p')
            ->where('p.banner = :banner')
            ->andWhere('p.hidden = true')
            ->setParameter('banner', $banner)
            ->orderBy('p.hiddenAt', 'DESC');

        $preferences = $qb->setFirstResult(($page - 1) * 20)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $totalPages = (int) ceil($dismissedCount / 20);

        return $this->render('admin/banner/details.html.twig', [
            'banner' => $banner,
            'preferences' => $preferences,
            'dismissedCount' => $dismissedCount,
            'page' => $page,
            'pageSize' => 20,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/preferences/{id}', name: 'app_admin_banner_preferences', methods: ['GET'])]
    public function preferences(Banner $banner, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $dismissedOnly = $request->query->getBoolean('dismissed_only', false);

        $qb = $this->preferenceRepository->createQueryBuilder('p')
            ->where('p.banner = :banner')
            ->setParameter('banner', $banner)
            ->leftJoin('p.user', 'u')
            ->addSelect('u');

        if ($dismissedOnly) {
            $qb->andWhere('p.hidden = true');
        }

        $qb->orderBy('p.hiddenAt', 'DESC');

        $preferences = $qb->setFirstResult(($page - 1) * 20)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $total = $this->preferenceRepository->count(['banner' => $banner]);
        $dismissedTotal = $this->preferenceRepository->count([
            'banner' => $banner,
            'hidden' => true
        ]);

        $paginationTotal = $dismissedOnly ? $dismissedTotal : $total;
        $totalPages = (int) ceil($paginationTotal / 20);

        return $this->render('admin/banner/preferences.html.twig', [
            'banner' => $banner,
            'preferences' => $preferences,
            'total' => $total,
            'dismissedTotal' => $dismissedTotal,
            'page' => $page,
            'pageSize' => 20,
            'dismissedOnly' => $dismissedOnly,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/reset-preferences/{id}', name: 'app_admin_banner_reset_preferences', methods: ['POST'])]
    public function resetPreferences(Banner $banner, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset' . $banner->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_banner_details', ['id' => $banner->getId()]);
        }

        // Reset all user preferences for this banner
        $preferences = $this->preferenceRepository->findBy(['banner' => $banner]);

        foreach ($preferences as $preference) {
            $preference->setHidden(false);
            $preference->setHiddenAt(null);
        }

        $this->em->flush();

        $this->addFlash('success', sprintf('Les préférences de %d utilisateurs ont été réinitialisées.', count($preferences)));
        return $this->redirectToRoute('app_admin_banner_details', ['id' => $banner->getId()]);
    }

    #[Route('/reset-user-preference/{bannerId}/{userId}', name: 'app_admin_banner_reset_user', methods: ['POST'])]
    public function resetUserPreference(int $bannerId, int $userId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset_user', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        $banner = $this->bannerRepository->find($bannerId);
        $user = $this->userRepository->find($userId);

        if (!$banner || !$user) {
            $this->addFlash('error', 'Bannière ou utilisateur non trouvé.');
            return $this->redirectToRoute('app_admin_banner_custom');
        }

        $preference = $this->preferenceRepository->findOneBy([
            'banner' => $banner,
            'user' => $user
        ]);

        if ($preference) {
            $preference->setHidden(false);
            $preference->setHiddenAt(null);
            $this->em->flush();
            $this->addFlash('success', 'Préférence réinitialisée pour cet utilisateur.');
        }

        return $this->redirectToRoute('app_admin_banner_details', ['id' => $banner->getId()]);
    }

    #[Route('/stats', name: 'app_admin_banner_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $stats = $this->bannerRepository->getBannerStatistics();
        $banners = $this->bannerRepository->findAll();

        $dismissalStats = [];
        foreach ($banners as $banner) {
            $total = $this->preferenceRepository->count(['banner' => $banner]);
            $dismissed = $this->preferenceRepository->count([
                'banner' => $banner,
                'hidden' => true
            ]);

            $dismissalStats[$banner->getId()] = [
                'banner' => $banner,
                'total' => $total,
                'dismissed' => $dismissed,
                'rate' => $total > 0 ? round(($dismissed / $total) * 100, 2) : 0,
            ];
        }

        return $this->render('admin/banner/stats.html.twig', [
            'stats' => $stats,
            'dismissalStats' => $dismissalStats,
        ]);
    }
}

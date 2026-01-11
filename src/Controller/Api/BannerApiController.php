<?php

namespace App\Controller\Api;

use App\Entity\Banner;
use App\Repository\BannerRepository;
use App\Repository\UserBannerPreferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/banner')]
#[IsGranted('ROLE_USER')]
class BannerApiController extends AbstractController
{
    public function __construct(
        private BannerRepository $bannerRepository,
        private UserBannerPreferenceRepository $preferenceRepository
    ) {}

    #[Route('/hide/{id}', name: 'api_banner_hide', methods: ['POST'])]
    public function hideBanner(Banner $banner, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $preference = $this->preferenceRepository->findOrCreate($user, $banner);
        $preference->setHidden(true);
        $this->preferenceRepository->save($preference, true);

        return new JsonResponse(['success' => true, 'message' => 'Banner hidden']);
    }

    #[Route('/show/{id}', name: 'api_banner_show', methods: ['POST'])]
    public function showBanner(Banner $banner, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $preference = $this->preferenceRepository->findOrCreate($user, $banner);
        $preference->setHidden(false);
        $this->preferenceRepository->save($preference, true);

        return new JsonResponse(['success' => true, 'message' => 'Banner shown']);
    }

    #[Route('/preferences', name: 'api_banner_preferences', methods: ['GET'])]
    public function getPreferences(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([], Response::HTTP_OK);
        }

        $hiddenBanners = $this->preferenceRepository->getHiddenBanners($user);
        $bannerIds = array_map(fn($pref) => $pref->getBanner()->getId(), $hiddenBanners);

        return new JsonResponse(['hiddenBannerIds' => $bannerIds]);
    }
}

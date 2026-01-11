<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/review')]
#[IsGranted('ROLE_USER')]
class ReviewController extends AbstractController
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private CloudinaryService $cloudinaryService
    ) {}

    #[Route('/livre/{id}', name: 'app_review_create', methods: ['GET', 'POST'])]
    public function create(Request $request, Livre $livre): Response
    {
        // Check if user already reviewed this book
        $existingReview = $this->reviewRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre
        ]);

        if ($existingReview) {
            $this->addFlash('warning', 'Vous avez déjà laissé un avis pour ce livre.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $review = new Review();
        $review->setUser($this->getUser());
        $review->setLivre($livre);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image uploads
            $imageFiles = $form->get('images')->getData();
            $uploadedImages = [];

            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile) {
                        // Cloudinary only - no local fallback
                        $cloudinaryUrl = $this->cloudinaryService->uploadImage($imageFile, 'biblio/review_images');
                        if ($cloudinaryUrl) {
                            $uploadedImages[] = $cloudinaryUrl;
                        } else {
                            $this->addFlash('error', 'Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                        }
                    }
                }
            }

            if (!empty($uploadedImages)) {
                $review->setImages($uploadedImages);
            }

            $this->entityManager->persist($review);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre avis a été publié avec succès.');

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('review/create.html.twig', [
            'livre' => $livre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review): Response
    {
        // Check if review belongs to current user
        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle new image uploads
            $imageFiles = $form->get('images')->getData();
            $uploadedImages = $review->getImages() ?? [];

            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile) {
                        // Cloudinary only - no local fallback
                        $cloudinaryUrl = $this->cloudinaryService->uploadImage($imageFile, 'biblio/review_images');
                        if ($cloudinaryUrl) {
                            $uploadedImages[] = $cloudinaryUrl;
                        } else {
                            $this->addFlash('error', 'Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                        }
                    }
                }
            }

            $review->setImages($uploadedImages);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre avis a été modifié avec succès.');

            return $this->redirectToRoute('app_livre_show', ['id' => $review->getLivre()->getId()]);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review): Response
    {
        // Check if review belongs to current user
        if ($review->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $review->getId(), $request->request->get('_token'))) {
            // Delete associated images
            $images = $review->getImages();
            if ($images) {
                foreach ($images as $image) {
                    $imagePath = $this->getParameter('review_images_directory') . '/' . $image;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            $livreId = $review->getLivre()->getId();
            $this->entityManager->remove($review);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre avis a été supprimé.');
        }

        return $this->redirectToRoute('app_livre_show', ['id' => $livreId]);
    }

    #[Route('/{id}/helpful', name: 'app_review_helpful', methods: ['POST'])]
    public function markHelpful(Request $request, Review $review): Response
    {
        if ($this->isCsrfTokenValid('helpful' . $review->getId(), $request->request->get('_token'))) {
            $review->setHelpful($review->getHelpful() + 1);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_show', ['id' => $review->getLivre()->getId()]);
    }
}
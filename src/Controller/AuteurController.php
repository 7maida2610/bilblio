<?php

namespace App\Controller;

use App\Entity\Auteur;
use App\Form\AuteurType;
use App\Repository\AuteurRepository;
use App\Service\CloudinaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/auteur')]
final class AuteurController extends AbstractController
{
    #[Route(name: 'app_auteur_index', methods: ['GET'])]
    public function index(Request $request, AuteurRepository $auteurRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $auteurRepository->createQueryBuilder('a');

        if (!empty($search)) {
            $queryBuilder->where('a.prenom LIKE :search')
                ->orWhere('a.nom LIKE :search')
                ->orWhere('a.biographie LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10 // items per page
        );

        return $this->render('auteur/index.html.twig', [
            'auteurs' => $pagination,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_auteur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): Response
    {
        $auteur = new Auteur();
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Cloudinary only - no local fallback
                $cloudinaryUrl = $cloudinaryService->uploadImage($imageFile, 'biblio/auteur_images');
                if ($cloudinaryUrl) {
                    $auteur->setImage($cloudinaryUrl);
                } else {
                    throw new \RuntimeException('Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                }
            }

            $entityManager->persist($auteur);
            $entityManager->flush();

            return $this->redirectToRoute('app_auteur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('auteur/new.html.twig', [
            'auteur' => $auteur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_auteur_show', methods: ['GET'])]
    public function show(Auteur $auteur): Response
    {
        return $this->render('auteur/show.html.twig', [
            'auteur' => $auteur,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_auteur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Auteur $auteur, EntityManagerInterface $entityManager, CloudinaryService $cloudinaryService): Response
    {
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Cloudinary only - no local fallback
                $cloudinaryUrl = $cloudinaryService->uploadImage($imageFile, 'biblio/auteur_images');
                if ($cloudinaryUrl) {
                    $auteur->setImage($cloudinaryUrl);
                } else {
                    throw new \RuntimeException('Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_auteur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('auteur/edit.html.twig', [
            'auteur' => $auteur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_auteur_delete', methods: ['POST'])]
    public function delete(Request $request, Auteur $auteur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$auteur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($auteur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_auteur_index', [], Response::HTTP_SEE_OTHER);
    }
}

<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Loan;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use App\Repository\CategorieRepository;
use App\Repository\AuteurRepository;
use App\Repository\EditeurRepository;
use App\Repository\BookReservationRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    #[Route(name: 'app_livre_index', methods: ['GET'])]
    public function index(Request $request, LivreRepository $livreRepository, CategorieRepository $categorieRepository, AuteurRepository $auteurRepository, EditeurRepository $editeurRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie', '');
        $auteurId = $request->query->get('auteur', '');
        $editeurId = $request->query->get('editeur', '');
        $ratingFilter = $request->query->get('rating_filter', '');
        $prixMin = $request->query->get('prix_min', '');
        $prixMax = $request->query->get('prix_max', '');
        $sort = $request->query->get('sort', 'l.createdAt DESC');

        $queryBuilder = $livreRepository->createQueryBuilder('l')
            ->leftJoin('l.auteur', 'a')
            ->leftJoin('l.categorie', 'c')
            ->leftJoin('l.editeur', 'e')
            ->leftJoin('l.reviews', 'r')
            ->addSelect('a', 'c', 'e')
            ->addSelect('AVG(r.rating) as HIDDEN avg_rating')
            ->groupBy('l.id, a.id, c.id, e.id');

        // Search filter
        if (!empty($search)) {
            $queryBuilder->andWhere('l.titre LIKE :search OR l.isbn LIKE :search OR CONCAT(a.prenom, \' \', a.nom) LIKE :search OR c.designation LIKE :search OR e.nomEditeur LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Category filter
        if (!empty($categorieId)) {
            $queryBuilder->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        // Author filter
        if (!empty($auteurId)) {
            $queryBuilder->andWhere('a.id = :auteurId')
                ->setParameter('auteurId', $auteurId);
        }

        // Publisher filter
        if (!empty($editeurId)) {
            $queryBuilder->andWhere('e.id = :editeurId')
                ->setParameter('editeurId', $editeurId);
        }

        // Rating filter
        if (!empty($ratingFilter)) {
            $queryBuilder->andHaving('AVG(r.rating) >= :ratingFilter')
                ->setParameter('ratingFilter', $ratingFilter);
        }

        // Price filters
        if (!empty($prixMin)) {
            $queryBuilder->andWhere('l.prix >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        if (!empty($prixMax)) {
            $queryBuilder->andWhere('l.prix <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        // Sorting
        switch ($sort) {
            case 'l.titre':
                $queryBuilder->orderBy('l.titre', 'ASC');
                break;
            case 'l.titre DESC':
                $queryBuilder->orderBy('l.titre', 'DESC');
                break;
            case 'l.prix':
                $queryBuilder->orderBy('l.prix', 'ASC');
                break;
            case 'l.prix DESC':
                $queryBuilder->orderBy('l.prix', 'DESC');
                break;
            case 'l.createdAt DESC':
                $queryBuilder->orderBy('l.createdAt', 'DESC');
                break;
            case 'l.createdAt':
                $queryBuilder->orderBy('l.createdAt', 'ASC');
                break;
            case 'l.nbExemplaires DESC':
                $queryBuilder->orderBy('l.nbExemplaires', 'DESC');
                break;
            case 'l.nbExemplaires':
                $queryBuilder->orderBy('l.nbExemplaires', 'ASC');
                break;
            default:
                $queryBuilder->orderBy('l.createdAt', 'DESC');
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12 // items per page
        );

        return $this->render('livre/index.html.twig', [
            'livres' => $pagination,
            'search' => $search,
            'categories' => $categorieRepository->findAll(),
            'auteurs' => $auteurRepository->findAll(),
            'editeurs' => $editeurRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $pdfFile = $form->get('pdf')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $livre->setImage($newFilename);
            }

            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('pdf_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $livre->setPdf($newFilename);
            }

            $entityManager->persist($livre);
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_show', methods: ['GET'])]
    public function show(Livre $livre, BookReservationRepository $reservationRepository, LoanRepository $loanRepository): Response
    {
        $user = $this->getUser();
        $hasActiveLoan = false;
        $hasActiveReservation = false;
        $hasPurchasedBook = false;
        $userReservation = null;
        
        // Get accurate loan count from repository
        $activeLoansCount = $loanRepository->count([
            'livre' => $livre,
            'status' => [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]
        ]);
        
        // Use stockEmprunt for loan availability
        $availableLoanCopies = max(0, $livre->getStockEmprunt() - $activeLoansCount);
        $isAvailable = $availableLoanCopies > 0 && $livre->isBorrowable();
        
        // Stock for sale
        $availableSaleCopies = $livre->getStockVente();

        if ($user) {
            // Check if user has active loan for this book
            $userLoan = $loanRepository->findOneBy([
                'user' => $user,
                'livre' => $livre,
                'status' => [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]
            ]);
            $hasActiveLoan = $userLoan !== null;

            // Check if user has active reservation for this book
            $userReservation = $reservationRepository->findUserActiveReservationForBook($user, $livre);
            $hasActiveReservation = $userReservation !== null;

            // Check if user purchased this book
            /** @var \App\Entity\User $user */
            $hasPurchasedBook = $user->getPurchasedBooks()->contains($livre);
        }

        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
            'hasActiveLoan' => $hasActiveLoan,
            'hasActiveReservation' => $hasActiveReservation,
            'hasPurchasedBook' => $hasPurchasedBook,
            'userReservation' => $userReservation,
            'availableCopies' => $availableLoanCopies,
            'availableSaleCopies' => $availableSaleCopies,
            'isAvailable' => $isAvailable,
            'activeLoansCount' => $activeLoansCount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            $pdfFile = $form->get('pdf')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $livre->setImage($newFilename);
            }

            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('pdf_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $livre->setPdf($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}

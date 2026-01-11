<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Livre;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/loan')]
#[IsGranted('ROLE_USER')]
class LoanController extends AbstractController
{
    public function __construct(
        private LoanRepository $loanRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_loan_index', methods: ['GET'])]
    public function index(): Response
    {
        $activeLoans = $this->loanRepository->findActiveLoansForUser($this->getUser());
        $loanHistory = $this->loanRepository->findBy(['user' => $this->getUser()], ['requestedAt' => 'DESC']);

        return $this->render('loan/index.html.twig', [
            'activeLoans' => $activeLoans,
            'loanHistory' => $loanHistory,
        ]);
    }

    #[Route('/request/{id}', name: 'app_loan_request', methods: ['POST'])]
    public function requestLoan(Livre $livre, Request $request): Response
    {
        // Check if book is available for borrowing (use stockEmprunt)
        if ($livre->getStockEmprunt() <= 0) {
            $this->addFlash('error', 'Ce livre n\'est pas disponible pour l\'emprunt.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_livre_index')));
        }

        // Check if user already has an active loan for this book
        $existingLoan = $this->loanRepository->findOneBy([
            'user' => $this->getUser(),
            'livre' => $livre,
            'status' => [Loan::STATUS_REQUESTED, Loan::STATUS_APPROVED, Loan::STATUS_ACTIVE]
        ]);

        if ($existingLoan) {
            $this->addFlash('warning', 'Vous avez déjà une demande d\'emprunt en cours pour ce livre.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_livre_index')));
        }

        // Create loan request
        $loan = new Loan();
        $loan->setUser($this->getUser());
        $loan->setLivre($livre);
        $loan->setStatus(Loan::STATUS_REQUESTED);

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre demande d\'emprunt a été soumise. Vous serez notifié de sa validation.');

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_livre_index')));
    }

    #[Route('/return/{id}', name: 'app_loan_return', methods: ['POST'])]
    public function returnLoan(Loan $loan, Request $request): Response
    {
        // Check if loan belongs to current user
        if ($loan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Check if loan can be returned
        if (!$loan->canBeReturned()) {
            $this->addFlash('error', 'Ce prêt ne peut pas être rendu actuellement.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_loan_index')));
        }

        // Update loan status
        $loan->setStatus(Loan::STATUS_RETURNED);
        $loan->setReturnedAt(new \DateTimeImmutable());

        // Update book loan stock (stockEmprunt, not stockVente)
        $livre = $loan->getLivre();
        $livre->setStockEmprunt($livre->getStockEmprunt() + 1);
        // Update total for backwards compatibility
        $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());

        $this->entityManager->flush();

        $this->addFlash('success', 'Le livre a été rendu avec succès.');

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_loan_index')));
    }

    #[Route('/cancel/{id}', name: 'app_loan_cancel', methods: ['POST'])]
    public function cancelLoan(Loan $loan, Request $request): Response
    {
        // Check if loan belongs to current user
        if ($loan->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Check if loan can be cancelled
        if (!$loan->canBeCancelled()) {
            $this->addFlash('error', 'Cette demande d\'emprunt ne peut pas être annulée.');
            return $this->redirect($request->headers->get('referer', $this->generateUrl('app_loan_index')));
        }

        // Update loan status
        $loan->setStatus(Loan::STATUS_CANCELLED);
        $loan->setCancelledAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', 'La demande d\'emprunt a été annulée.');

        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_loan_index')));
    }
}
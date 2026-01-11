<?php

namespace App\Controller\Admin;

use App\Entity\BookReservation;
use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservation', name: 'app_admin_reservation_')]
#[IsGranted('ROLE_ADMIN')]
class ReservationAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/{id}/promote', name: 'promote', methods: ['GET', 'POST'])]
    public function promote(BookReservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            $this->addFlash('error', 'Cette reservation n\'est pas active.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        if ($reservation->getPosition() <= 0) {
            $this->addFlash('warning', 'Cette reservation est deja en premiere position.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        $reservation->setPosition($reservation->getPosition() - 1);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation promue avec succes. Position: ' . $reservation->getPosition());
        return $this->redirectToRoute('admin_bookreservation_index');
    }

    #[Route('/{id}/create-loan', name: 'create_loan', methods: ['GET', 'POST'])]
    public function createLoan(BookReservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            $this->addFlash('error', 'Cette reservation n\'est pas active.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        if ($reservation->getPosition() !== 0) {
            $this->addFlash('error', 'Seule la premiere reservation peut etre convertie en emprunt.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        $livre = $reservation->getLivre();
        if ($livre->getStockEmprunt() <= 0) {
            $this->addFlash('error', 'Le livre n\'a plus d\'exemplaires disponibles pour l\'emprunt.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        $loan = new Loan();
        $loan->setUser($reservation->getUser());
        $loan->setLivre($livre);
        $loan->setStatus(Loan::STATUS_APPROVED);
        $loan->setApprovedAt(new \DateTimeImmutable());
        $loan->setApprovedBy($this->getUser()?->getFirstName() . ' ' . $this->getUser()?->getLastName());
        $loan->setNotes('Cree a partir de la reservation #' . $reservation->getId());

        // Decrement stockEmprunt for loans
        $livre->setStockEmprunt($livre->getStockEmprunt() - 1);
        // Update total for backwards compatibility
        $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());

        $reservation->setIsActive(false);
        $reservation->setNotifiedAt(new \DateTimeImmutable());

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        $this->addFlash('success', 'Emprunt cree avec succes. Livre: ' . $livre->getTitre());
        return $this->redirectToRoute('admin_loan_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['GET', 'POST'])]
    public function cancel(BookReservation $reservation): RedirectResponse
    {
        if (!$reservation->isActive()) {
            $this->addFlash('error', 'Cette reservation est deja annulee.');
            return $this->redirectToRoute('admin_bookreservation_index');
        }

        $reservation->setIsActive(false);
        $reservation->setNotifiedAt(new \DateTimeImmutable());

        $nextReservations = $this->entityManager->getRepository(BookReservation::class)
            ->findBy(['livre' => $reservation->getLivre(), 'isActive' => true], ['position' => 'ASC']);

        foreach ($nextReservations as $nextRes) {
            $nextRes->setPosition($nextRes->getPosition() - 1);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation annulee avec succes.');
        return $this->redirectToRoute('admin_bookreservation_index');
    }
}

<?php

namespace App\Controller;

use App\Entity\BookReservation;
use App\Entity\Loan;
use App\Repository\BookReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reservations', name: 'app_admin_reservation_')]
#[IsGranted('ROLE_ADMIN')]
class ReservationManagementController extends AbstractController
{
    public function __construct(
        private BookReservationRepository $reservationRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', name: 'management', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status');
        $pageSize = 20;

        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.livre', 'b')
            ->addSelect('u', 'b')
            ->orderBy('r.requestedAt', 'DESC');

        if ($status === 'active') {
            $qb->andWhere('r.isActive = :active')->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $qb->andWhere('r.isActive = :active')->setParameter('active', false);
        }

        $total = (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $reservations = $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        // Count for stats
        $activeCount = $this->reservationRepository->count(['isActive' => true]);
        $inactiveCount = $this->reservationRepository->count(['isActive' => false]);

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'status' => $status,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
        ]);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(BookReservation $reservation): Response
    {
        return $this->render('admin/reservation/detail.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/notify', name: 'notify', methods: ['POST'])]
    public function notify(BookReservation $reservation, Request $request): Response
    {
        if ($this->isCsrfTokenValid('notify' . $reservation->getId(), $request->request->get('_token'))) {
            if (!$reservation->isActive()) {
                $this->addFlash('error', 'Cette réservation n\'est pas active.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            $reservation->setNotifiedAt(new \DateTimeImmutable());
            $this->em->flush();
            $this->addFlash('success', sprintf(
                'L\'utilisateur %s a été notifié pour le livre "%s".',
                $reservation->getUser()->getUsername(),
                $reservation->getLivre()->getTitre()
            ));
        }

        return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/promote', name: 'promote', methods: ['POST'])]
    public function promote(BookReservation $reservation, Request $request): Response
    {
        if ($this->isCsrfTokenValid('promote' . $reservation->getId(), $request->request->get('_token'))) {
            if (!$reservation->isActive()) {
                $this->addFlash('error', 'Cette réservation n\'est pas active.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            if ($reservation->getPosition() <= 0) {
                $this->addFlash('warning', 'Cette réservation est déjà en première position.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            $reservation->setPosition($reservation->getPosition() - 1);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Réservation promue avec succès. Nouvelle position: %d',
                $reservation->getPosition()
            ));
        }

        return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/create-loan', name: 'create_loan', methods: ['POST'])]
    public function createLoan(BookReservation $reservation, Request $request): Response
    {
        if ($this->isCsrfTokenValid('create_loan' . $reservation->getId(), $request->request->get('_token'))) {
            if (!$reservation->isActive()) {
                $this->addFlash('error', 'Cette réservation n\'est pas active.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            if ($reservation->getPosition() !== 0) {
                $this->addFlash('error', 'Seule la première réservation peut être convertie en emprunt.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            $livre = $reservation->getLivre();
            if ($livre->getStockEmprunt() <= 0) {
                $this->addFlash('error', 'Le livre n\'a plus d\'exemplaires disponibles pour l\'emprunt.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            // Create new loan
            $loan = new Loan();
            $loan->setUser($reservation->getUser());
            $loan->setLivre($livre);
            $loan->setStatus(Loan::STATUS_APPROVED);
            $loan->setApprovedAt(new \DateTimeImmutable());
            $loan->setApprovedBy($this->getUser());
            $loan->setNotes('Créé à partir de la réservation #' . $reservation->getId());

            // Decrement book loan stock (stockEmprunt, not stockVente)
            $livre->setStockEmprunt($livre->getStockEmprunt() - 1);
            // Update total for backwards compatibility
            $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());

            // Deactivate reservation
            $reservation->setIsActive(false);
            $reservation->setNotifiedAt(new \DateTimeImmutable());

            $this->em->persist($loan);
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Emprunt créé avec succès pour le livre "%s".',
                $livre->getTitre()
            ));

            return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
        }

        return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(BookReservation $reservation, Request $request): Response
    {
        if ($this->isCsrfTokenValid('cancel' . $reservation->getId(), $request->request->get('_token'))) {
            if (!$reservation->isActive()) {
                $this->addFlash('error', 'Cette réservation est déjà annulée.');
                return $this->redirectToRoute('app_admin_reservation_detail', ['id' => $reservation->getId()]);
            }

            $reservation->setIsActive(false);
            $reservation->setNotifiedAt(new \DateTimeImmutable());

            // Reposition other reservations in the queue
            $nextReservations = $this->reservationRepository->findBy(
                ['livre' => $reservation->getLivre(), 'isActive' => true],
                ['position' => 'ASC']
            );

            foreach ($nextReservations as $nextRes) {
                if ($nextRes->getPosition() > $reservation->getPosition()) {
                    $nextRes->setPosition($nextRes->getPosition() - 1);
                }
            }

            $this->em->flush();

            $this->addFlash('success', sprintf(
                'La réservation de "%s" par %s a été annulée.',
                $reservation->getLivre()->getTitre(),
                $reservation->getUser()->getUsername()
            ));
        }

        return $this->redirectToRoute('app_admin_reservation_management');
    }
}

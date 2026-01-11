<?php

namespace App\Controller;

use App\Entity\BookReservation;
use App\Repository\BookReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reservation')]
#[IsGranted('ROLE_USER')]
final class ReservationController extends AbstractController
{
    #[Route('/my-reservations', name: 'app_reservation_index', methods: ['GET'])]
    public function index(BookReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $reservations = $reservationRepository->findBy(['user' => $user], ['requestedAt' => 'DESC']);

        // Separate active and inactive reservations
        $activeReservations = array_filter($reservations, fn(BookReservation $r) => $r->isActive());
        $inactiveReservations = array_filter($reservations, fn(BookReservation $r) => !$r->isActive());

        return $this->render('reservation/index.html.twig', [
            'activeReservations' => $activeReservations,
            'inactiveReservations' => $inactiveReservations,
            'totalReservations' => count($reservations),
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(
        BookReservation $reservation,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $this->getUser();

        // Check if user owns this reservation
        if ($reservation->getUser() !== $user) {
            $this->addFlash('error', 'Vous n\'avez pas la permission d\'annuler cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Check token
        if (!$this->isCsrfTokenValid('cancel' . $reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_reservation_index');
        }

        // Mark as inactive instead of deleting
        $reservation->setIsActive(false);
        $entityManager->persist($reservation);
        $entityManager->flush();

        // Recalculate positions for remaining reservations
        $activeReservations = $entityManager->getRepository(BookReservation::class)->findActiveReservationsForBook($reservation->getLivre());
        foreach ($activeReservations as $index => $res) {
            $res->setPosition($index + 1);
        }
        $entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/view', name: 'app_reservation_view', methods: ['GET'])]
    public function view(BookReservation $reservation): Response
    {
        $user = $this->getUser();

        // Check if user owns this reservation
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reservation/view.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/my-calendar', name: 'app_reservation_my_calendar', methods: ['GET'])]
    public function myCalendar(BookReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        $activeReservations = $reservationRepository->findBy(['user' => $user, 'isActive' => true], ['requestedAt' => 'DESC']);
        $allReservations = $reservationRepository->findBy(['user' => $user], ['requestedAt' => 'DESC']);

        // Count stats
        $activeCount = count($activeReservations);
        $notifiedCount = 0;
        $totalCount = count($allReservations);

        $events = [];
        foreach ($activeReservations as $reservation) {
            $events[] = [
                'title' => 'Réservation - ' . $reservation->getLivre()->getTitre(),
                'start' => $reservation->getRequestedAt()->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => '#FF6F00',
                'borderColor' => '#FF6F00',
                'url' => $this->generateUrl('app_reservation_view', ['id' => $reservation->getId()]),
            ];

            // If notified, add notification event
            if ($reservation->getNotifiedAt()) {
                $notifiedCount++;
                $events[] = [
                    'title' => $reservation->getLivre()->getTitre() . ' - Disponible !',
                    'start' => $reservation->getNotifiedAt()->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => '#28a745',
                    'borderColor' => '#28a745',
                    'url' => $this->generateUrl('app_reservation_view', ['id' => $reservation->getId()]),
                ];
            }
        }

        return $this->render('reservation/my_calendar.html.twig', [
            'events' => json_encode($events ?: []),
            'activeReservationsCount' => $activeCount,
            'notifiedReservationsCount' => $notifiedCount,
            'totalReservationsCount' => $totalCount,
        ]);
    }

    #[Route('/{id}/calendar', name: 'app_reservation_calendar', methods: ['GET'])]
    public function calendar(BookReservation $reservation): Response
    {
        $user = $this->getUser();

        // Check if user owns this reservation
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $livre = $reservation->getLivre();
        $allReservations = $livre->getReservations();

        $events = [];
        foreach ($allReservations as $res) {
            if ($res->isActive()) {
                $color = $res->getId() === $reservation->getId() ? '#FF6F00' : '#FFC107';
                $title = $res->getId() === $reservation->getId() ? 'Votre réservation (Position #' . $res->getPosition() . ')' : 'Réservation #' . $res->getPosition();

                $events[] = [
                    'title' => $title,
                    'start' => $res->getRequestedAt()->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                ];
            }
        }

        return $this->render('reservation/calendar.html.twig', [
            'livre' => $livre,
            'events' => json_encode($events ?: []),
        ]);
    }
}

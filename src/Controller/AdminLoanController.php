<?php

namespace App\Controller;

use App\Entity\Loan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backoffice/gestion-emprunts')]
#[IsGranted('ROLE_MODERATOR')]
class AdminLoanController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Main loan management dashboard with calendar and statistics
     */
    #[Route('', name: 'app_backoffice_loans_dashboard')]
    public function dashboard(): Response
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);

        $requestedLoans = $loanRepo->findBy(['status' => Loan::STATUS_REQUESTED], ['requestedAt' => 'DESC']);
        $approvedLoans = $loanRepo->findBy(['status' => Loan::STATUS_APPROVED], ['approvedAt' => 'DESC']);
        $activeLoans = $loanRepo->findBy(['status' => Loan::STATUS_ACTIVE], ['loanStartDate' => 'DESC']);
        $overdueLoans = $loanRepo->findBy(['status' => Loan::STATUS_OVERDUE], ['dueDate' => 'ASC']);
        $returnedLoans = $loanRepo->findBy(['status' => Loan::STATUS_RETURNED], ['returnedAt' => 'DESC']);
        $cancelledLoans = $loanRepo->findBy(['status' => Loan::STATUS_CANCELLED], ['cancelledAt' => 'DESC']);

        $calendarEvents = [];
        
        foreach (array_merge($activeLoans, $overdueLoans) as $loan) {
            if ($loan->getDueDate()) {
                $event = [
                    'id' => $loan->getId(),
                    'title' => $loan->getLivre()->getTitre() . ' (' . ($loan->getUser()->getUsername() ?: $loan->getUser()->getEmail()) . ')',
                    'start' => $loan->getLoanStartDate() ? $loan->getLoanStartDate()->format('Y-m-d') : null,
                    'end' => $loan->getDueDate()->format('Y-m-d'),
                    'status' => $loan->getStatus(),
                    'backgroundColor' => $loan->getStatus() === Loan::STATUS_OVERDUE ? '#dc3545' : '#28a745',
                    'borderColor' => $loan->getStatus() === Loan::STATUS_OVERDUE ? '#c82333' : '#1e7e34',
                ];
                $calendarEvents[] = $event;
            }
        }

        foreach (array_merge($requestedLoans, $approvedLoans) as $loan) {
            $event = [
                'id' => $loan->getId(),
                'title' => $loan->getLivre()->getTitre() . ' (' . ($loan->getUser()->getUsername() ?: $loan->getUser()->getEmail()) . ')',
                'start' => $loan->getRequestedAt()->format('Y-m-d'),
                'status' => $loan->getStatus(),
                'backgroundColor' => '#ffc107',
                'borderColor' => '#e0a800',
            ];
            $calendarEvents[] = $event;
        }

        return $this->render('backoffice/loans/dashboard.html.twig', [
            'requestedLoans' => $requestedLoans,
            'approvedLoans' => $approvedLoans,
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
            'returnedLoans' => $returnedLoans,
            'cancelledLoans' => $cancelledLoans,
            'calendarEvents' => json_encode($calendarEvents),
            'totalLoans' => count($requestedLoans) + count($approvedLoans) + count($activeLoans) + count($overdueLoans) + count($returnedLoans),
        ]);
    }

    /**
     * Show pending loan requests
     */
    #[Route('/demandes', name: 'app_backoffice_loans_requests')]
    public function requests(): Response
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        $loans = $loanRepo->findBy(['status' => Loan::STATUS_REQUESTED], ['requestedAt' => 'DESC']);

        return $this->render('backoffice/loans/requests.html.twig', [
            'loans' => $loans,
        ]);
    }

    /**
     * Show active and overdue loans
     */
    #[Route('/actifs', name: 'app_backoffice_loans_active')]
    public function active(): Response
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        $activeLoans = $loanRepo->findBy(['status' => Loan::STATUS_ACTIVE], ['loanStartDate' => 'DESC']);
        $overdueLoans = $loanRepo->findBy(['status' => Loan::STATUS_OVERDUE], ['dueDate' => 'ASC']);

        return $this->render('backoffice/loans/active.html.twig', [
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
        ]);
    }

    /**
     * Show returned and cancelled loans history
     */
    #[Route('/historique', name: 'app_backoffice_loans_history')]
    public function history(): Response
    {
        $loanRepo = $this->entityManager->getRepository(Loan::class);
        $returnedLoans = $loanRepo->findBy(['status' => Loan::STATUS_RETURNED], ['returnedAt' => 'DESC']);
        $cancelledLoans = $loanRepo->findBy(['status' => Loan::STATUS_CANCELLED], ['cancelledAt' => 'DESC']);

        return $this->render('backoffice/loans/history.html.twig', [
            'returnedLoans' => $returnedLoans,
            'cancelledLoans' => $cancelledLoans,
        ]);
    }
}

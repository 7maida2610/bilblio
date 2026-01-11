<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/loans', name: 'app_admin_loan_')]
#[IsGranted('ROLE_ADMIN')]
class LoanManagementController extends AbstractController
{
    public function __construct(
        private LoanRepository $loanRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', name: 'management', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status');
        $pageSize = 20;

        // Build query
        $qb = $this->loanRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->leftJoin('l.livre', 'b')
            ->leftJoin('l.approvedBy', 'ab')
            ->addSelect('u', 'b', 'ab')
            ->orderBy('l.requestedAt', 'DESC');

        if ($status) {
            $qb->andWhere('l.status = :status')->setParameter('status', $status);
        }

        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $loans = $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        // Count by status for dashboard
        $statusCounts = [];
        foreach (Loan::STATUSES as $key => $label) {
            $statusCounts[$key] = $this->loanRepository->count(['status' => $key]);
        }

        return $this->render('admin/loan/index.html.twig', [
            'loans' => $loans,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'status' => $status,
            'statuses' => Loan::STATUSES,
            'statusCounts' => $statusCounts,
        ]);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function detail(Loan $loan): Response
    {
        return $this->render('admin/loan/detail.html.twig', [
            'loan' => $loan,
            'statuses' => Loan::STATUSES,
        ]);
    }

    #[Route('/{id}/approve', name: 'approve_action', methods: ['POST'])]
    public function approve(Loan $loan, Request $request): Response
    {
        if ($this->isCsrfTokenValid('approve' . $loan->getId(), $request->request->get('_token'))) {
            if (!$loan->canBeApproved()) {
                $this->addFlash('error', 'Ce prêt ne peut pas être approuvé dans son état actuel.');
                return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
            }

            $loan->setStatus(Loan::STATUS_APPROVED);
            $loan->setApprovedAt(new \DateTimeImmutable());
            $loan->setApprovedBy($this->getUser());
            
            // Decrement book loan stock (stockEmprunt, not stockVente)
            $livre = $loan->getLivre();
            if ($livre && $livre->getStockEmprunt() > 0) {
                $livre->setStockEmprunt($livre->getStockEmprunt() - 1);
                // Update total for backwards compatibility
                $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());
            }
            
            $this->em->flush();
            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" par %s a été approuvé.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        }

        return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
    }

    #[Route('/{id}/reject', name: 'reject_action', methods: ['POST'])]
    public function reject(Loan $loan, Request $request): Response
    {
        if ($this->isCsrfTokenValid('reject' . $loan->getId(), $request->request->get('_token'))) {
            if (!$loan->canBeCancelled()) {
                $this->addFlash('error', 'Ce prêt ne peut pas être rejeté dans son état actuel.');
                return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
            }

            $loan->setStatus(Loan::STATUS_CANCELLED);
            $loan->setCancelledAt(new \DateTimeImmutable());
            
            $this->em->flush();
            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" par %s a été rejeté.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        }

        return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
    }

    #[Route('/{id}/activate', name: 'activate_action', methods: ['POST'])]
    public function activate(Loan $loan, Request $request): Response
    {
        if ($this->isCsrfTokenValid('activate' . $loan->getId(), $request->request->get('_token'))) {
            if ($loan->getStatus() !== Loan::STATUS_APPROVED) {
                $this->addFlash('error', 'Seuls les prêts approuvés peuvent être activés.');
                return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
            }

            $loan->setStatus(Loan::STATUS_ACTIVE);
            $loan->setLoanStartDate(new \DateTimeImmutable());

            // Set due date to 14 days from now if not already set
            if (!$loan->getDueDate()) {
                $dueDate = (new \DateTimeImmutable())->modify('+14 days');
                $loan->setDueDate($dueDate);
            }

            $this->em->flush();
            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" a été activé. Date limite de retour: %s',
                $loan->getLivre()->getTitre(),
                $loan->getDueDate()->format('d/m/Y')
            ));
        }

        return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
    }

    #[Route('/{id}/return', name: 'return_action', methods: ['POST'])]
    public function markReturned(Loan $loan, Request $request): Response
    {
        if ($this->isCsrfTokenValid('return' . $loan->getId(), $request->request->get('_token'))) {
            if (!in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                $this->addFlash('error', 'Ce prêt ne peut pas être marqué comme retourné.');
                return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
            }

            $loan->setStatus(Loan::STATUS_RETURNED);
            $loan->setReturnedAt(new \DateTimeImmutable());
            
            // Increment book loan stock (stockEmprunt)
            if ($loan->getLivre()) {
                $livre = $loan->getLivre();
                $livre->setStockEmprunt($livre->getStockEmprunt() + 1);
                // Update total for backwards compatibility
                $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());
            }
            
            $this->em->flush();
            $this->addFlash('success', sprintf(
                'Le livre "%s" emprunté par %s a été marqué comme retourné.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        }

        return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
    }

    #[Route('/{id}/extend', name: 'extend_action', methods: ['POST'])]
    public function extend(Loan $loan, Request $request): Response
    {
        if ($this->isCsrfTokenValid('extend' . $loan->getId(), $request->request->get('_token'))) {
            if (!in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                $this->addFlash('error', 'Seuls les prêts actifs ou en retard peuvent être prolongés.');
                return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
            }

            // Extend by 14 days
            $currentDueDate = $loan->getDueDate();
            $newDueDate = $currentDueDate->modify('+14 days');
            $loan->setDueDate($newDueDate);

            // If was overdue, mark as active again
            if ($loan->getStatus() === Loan::STATUS_OVERDUE) {
                $loan->setStatus(Loan::STATUS_ACTIVE);
            }

            $this->em->flush();
            $this->addFlash('success', sprintf(
                'L\'emprunt a été prolongé jusqu\'au %s.',
                $newDueDate->format('d/m/Y')
            ));
        }

        return $this->redirectToRoute('app_admin_loan_detail', ['id' => $loan->getId()]);
    }
}

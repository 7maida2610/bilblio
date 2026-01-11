<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/loan')]
#[IsGranted('ROLE_ADMIN')]
class LoanAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Approve a loan request
     */
    #[Route('/{id}/approve', name: 'app_admin_loan_approve', methods: ['POST', 'GET'])]
    public function approveLoan(Loan $loan, Request $request): Response
    {
        // Check if loan can be approved
        if (!$loan->canBeApproved()) {
            $this->addFlash('error', 'Ce prêt ne peut pas être approuvé dans son état actuel.');
            return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
        }

        try {
            // Set approval information
            $loan->setStatus(Loan::STATUS_APPROVED);
            $loan->setApprovedAt(new \DateTimeImmutable());

            // Update book loan stock (stockEmprunt, not stockVente)
            $livre = $loan->getLivre();
            if ($livre->getStockEmprunt() > 0) {
                $livre->setStockEmprunt($livre->getStockEmprunt() - 1);
                // Update total for backwards compatibility
                $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());
            } else {
                throw new \Exception('Aucun exemplaire disponible pour l\'emprunt de ce livre.');
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" par %s a été approuvé.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
    }

    /**
     * Reject/Decline a loan request
     */
    #[Route('/{id}/reject', name: 'app_admin_loan_reject', methods: ['POST', 'GET'])]
    public function rejectLoan(Loan $loan, Request $request): Response
    {
        // Check if loan can be cancelled/rejected
        if (!$loan->canBeCancelled()) {
            $this->addFlash('error', 'Ce prêt ne peut pas être rejeté dans son état actuel.');
            return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
        }

        try {
            // Set rejection information
            $loan->setStatus(Loan::STATUS_CANCELLED);
            $loan->setCancelledAt(new \DateTimeImmutable());

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" par %s a été rejeté.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du rejet: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
    }

    /**
     * Mark a loan as returned by admin
     */
    #[Route('/{id}/return', name: 'app_admin_loan_return', methods: ['POST', 'GET'])]
    public function returnLoan(Loan $loan, Request $request): Response
    {
        // Check if loan can be returned
        if ($loan->getStatus() !== Loan::STATUS_ACTIVE && $loan->getStatus() !== Loan::STATUS_OVERDUE) {
            $this->addFlash('error', 'Ce prêt ne peut pas être marqué comme retourné.');
            return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
        }

        try {
            // Mark as returned
            $loan->setStatus(Loan::STATUS_RETURNED);
            $loan->setReturnedAt(new \DateTimeImmutable());

            // Update book loan stock (stockEmprunt, not stockVente)
            $livre = $loan->getLivre();
            $livre->setStockEmprunt($livre->getStockEmprunt() + 1);
            // Update total for backwards compatibility
            $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'Le livre "%s" emprunté par %s a été marqué comme retourné.',
                $loan->getLivre()->getTitre(),
                $loan->getUser()->getUsername()
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du marquage comme retourné: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
    }

    /**
     * Convert approved loan to active (start of borrowing)
     */
    #[Route('/{id}/activate', name: 'app_admin_loan_activate', methods: ['POST', 'GET'])]
    public function activateLoan(Loan $loan, Request $request): Response
    {
        // Check if loan is approved
        if ($loan->getStatus() !== Loan::STATUS_APPROVED) {
            $this->addFlash('error', 'Seuls les prêts approuvés peuvent être activés.');
            return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
        }

        try {
            // Set loan as active
            $loan->setStatus(Loan::STATUS_ACTIVE);
            $loan->setLoanStartDate(new \DateTimeImmutable());

            // Set due date to 14 days from now if not already set
            if (!$loan->getDueDate()) {
                $dueDate = (new \DateTimeImmutable())->modify('+14 days');
                $loan->setDueDate($dueDate);
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'L\'emprunt de "%s" a été activé. Date limite de retour: %s',
                $loan->getLivre()->getTitre(),
                $loan->getDueDate()->format('d/m/Y')
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'activation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
    }

    /**
     * Extend a loan's due date
     */
    #[Route('/{id}/extend', name: 'app_admin_loan_extend', methods: ['POST'])]
    public function extendLoan(Loan $loan, Request $request): Response
    {
        // Check if loan is active or overdue
        if (!in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
            $this->addFlash('error', 'Seuls les prêts actifs ou en retard peuvent être prolongés.');
            return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
        }

        try {
            // Extend by 14 days
            $currentDueDate = $loan->getDueDate();
            $newDueDate = $currentDueDate->modify('+14 days');
            $loan->setDueDate($newDueDate);

            // If was overdue, mark as active again
            if ($loan->getStatus() === Loan::STATUS_OVERDUE) {
                $loan->setStatus(Loan::STATUS_ACTIVE);
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf(
                'L\'emprunt a été prolongé jusqu\'au %s.',
                $newDueDate->format('d/m/Y')
            ));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la prolongation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => 'App\\Controller\\Admin\\LoanCrudController', 'entityId' => $loan->getId()]);
    }
}

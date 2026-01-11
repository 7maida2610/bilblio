<?php

namespace App\Controller\Admin;

use App\Entity\Loan;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class LoanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Loan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Emprunt')
            ->setEntityLabelInPlural('Emprunts')
            ->setPageTitle('index', 'Gestion des Emprunts')
            ->setPageTitle('detail', 'Détails de l\'emprunt #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier l\'emprunt #%entity_short_id%')
            ->setDefaultSort(['requestedAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('user', 'Utilisateur'),
            AssociationField::new('livre', 'Livre'),
            ChoiceField::new('status', 'Statut')
                ->setChoices(Loan::STATUSES),
            TextField::new('requestedAtFormatted', 'Demandé le')
                ->onlyOnIndex()
                ->hideOnForm(),
            TextField::new('requestedAtFormatted', 'Demandé le')
                ->onlyOnDetail()
                ->hideOnForm(),
            TextField::new('approvedAtFormatted', 'Approuvé le')
                ->onlyOnDetail()
                ->hideOnForm(),
            TextField::new('loanStartDateFormatted', 'Début d\'emprunt')
                ->onlyOnDetail()
                ->hideOnForm(),
            TextField::new('dueDateFormatted', 'Date de retour')
                ->hideOnForm(),
            TextField::new('returnedAtFormatted', 'Retourné le')
                ->onlyOnDetail()
                ->hideOnForm(),
            TextareaField::new('notes', 'Notes')
                ->onlyOnForms(),
            AssociationField::new('approvedBy', 'Approuvé par')
                ->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveLoan = Action::new('approveLoan', 'Approuver')
            ->linkToRoute('app_admin_loan_approve', function (Loan $loan): array {
                return ['id' => $loan->getId()];
            })
            ->setIcon('fa fa-check')
            ->setCssClass('btn btn-success')
            ->displayIf(function (Loan $loan) {
                return $loan->getStatus() === Loan::STATUS_REQUESTED;
            });

        $rejectLoan = Action::new('rejectLoan', 'Rejeter')
            ->linkToRoute('app_admin_loan_reject', function (Loan $loan): array {
                return ['id' => $loan->getId()];
            })
            ->setIcon('fa fa-times')
            ->setCssClass('btn btn-danger')
            ->displayIf(function (Loan $loan) {
                return $loan->getStatus() === Loan::STATUS_REQUESTED;
            });

        $markAsReturned = Action::new('markAsReturned', 'Marquer retourné')
            ->linkToRoute('app_admin_loan_return', function (Loan $loan): array {
                return ['id' => $loan->getId()];
            })
            ->setIcon('fa fa-undo')
            ->setCssClass('btn btn-info')
            ->displayIf(function (Loan $loan) {
                return $loan->getStatus() === Loan::STATUS_ACTIVE;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $approveLoan)
            ->add(Crud::PAGE_INDEX, $rejectLoan)
            ->add(Crud::PAGE_INDEX, $markAsReturned)
            ->add(Crud::PAGE_DETAIL, Action::new('backToList', 'Retour à la liste')
                ->linkToCrudAction(Action::INDEX)
                ->setIcon('fa fa-arrow-left'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit')->setLabel('Modifier');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel('Supprimer');
            });
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('user')
            ->add('livre')
            ->add('requestedAt')
            ->add('dueDate');
    }
}

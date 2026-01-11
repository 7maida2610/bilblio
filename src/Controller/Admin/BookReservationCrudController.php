<?php

namespace App\Controller\Admin;

use App\Entity\BookReservation;
use App\Entity\Loan;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_ADMIN')]
class BookReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BookReservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reservation')
            ->setEntityLabelInPlural('Reservations')
            ->setPageTitle('index', 'Gestion des Reservations de Livres')
            ->setPageTitle('detail', 'Details de la reservation #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier la reservation #%entity_short_id%')
            ->setDefaultSort(['position' => 'ASC', 'requestedAt' => 'ASC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('user', 'Utilisateur')
                ->setTemplatePath('admin/field/user_link.html.twig'),
            AssociationField::new('livre', 'Livre')
                ->setTemplatePath('admin/field/livre_link.html.twig'),
            IntegerField::new('position', 'Position dans la file')
                ->setHelp('Position dans la queue de reservation'),
            BooleanField::new('isActive', 'Actif'),
            TextField::new('requestedAtFormatted', 'Demandé le')
                ->onlyOnIndex()
                ->hideOnForm(),
            TextField::new('requestedAtFormatted', 'Date de demande')
                ->onlyOnDetail()
                ->hideOnForm(),
            TextField::new('notifiedAtFormatted', 'Notifie le')
                ->onlyOnDetail()
                ->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $promoteReservation = Action::new('promote', 'Promouvoir')
            ->linkToRoute('app_admin_reservation_promote', function (BookReservation $reservation): array {
                return ['id' => $reservation->getId()];
            })
            ->setIcon('fa fa-arrow-up')
            ->setCssClass('btn btn-primary')
            ->displayIf(function (BookReservation $reservation) {
                return $reservation->isActive() && $reservation->getPosition() > 0;
            });

        $createLoan = Action::new('createLoan', 'Creer Emprunt')
            ->linkToRoute('app_admin_reservation_create_loan', function (BookReservation $reservation): array {
                return ['id' => $reservation->getId()];
            })
            ->setIcon('fa fa-exchange')
            ->setCssClass('btn btn-success')
            ->displayIf(function (BookReservation $reservation) {
                return $reservation->isActive() && $reservation->getPosition() === 0;
            });

        $cancelReservation = Action::new('cancelReservation', 'Annuler')
            ->linkToRoute('app_admin_reservation_cancel', function (BookReservation $reservation): array {
                return ['id' => $reservation->getId()];
            })
            ->setIcon('fa fa-times')
            ->setCssClass('btn btn-danger')
            ->displayIf(function (BookReservation $reservation) {
                return $reservation->isActive();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $promoteReservation)
            ->add(Crud::PAGE_INDEX, $createLoan)
            ->add(Crud::PAGE_INDEX, $cancelReservation)
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
            ->add('isActive')
            ->add('user')
            ->add('livre')
            ->add('requestedAt')
            ->add('position');
    }
}

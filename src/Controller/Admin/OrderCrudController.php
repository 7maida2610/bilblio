<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderCrudController extends AbstractCrudController
{
    #[IsGranted('ROLE_ADMIN')]
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setPageTitle('index', 'Gestion des Commandes')
            ->setPageTitle('detail', 'Détails de la commande #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier la commande #%entity_short_id%')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            TextField::new('orderNumber', 'Numéro de commande')
                ->setTemplatePath('admin/field/order_number.html.twig')
                ->onlyOnIndex(),
            AssociationField::new('user', 'Client')
                ->setTemplatePath('admin/field/user_link.html.twig'),
            ChoiceField::new('status', 'Statut')
                ->setChoices(Order::STATUSES)
                ->setTemplatePath('admin/field/order_status.html.twig'),
            ChoiceField::new('paymentMethod', 'Methode de paiement')
                ->setChoices(Order::PAYMENT_METHODS),
            MoneyField::new('totalAmount', 'Montant total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setTemplatePath('admin/field/order_amount.html.twig'),
            TextField::new('currency', 'Devise')->onlyOnDetail(),
            TextField::new('stripePaymentIntentId', 'ID Stripe')->onlyOnDetail(),
            TextField::new('createdAt', 'Créée le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
            TextField::new('paidAt', 'Payée le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
            TextField::new('shippedAt', 'Expédiée le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
            TextField::new('deliveredAt', 'Livrée le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
            TextField::new('notes', 'Notes')->onlyOnForms(),
            AssociationField::new('orderItems', 'Articles')
                ->setTemplatePath('admin/field/order_items.html.twig')
                ->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOrder = Action::new('viewOrder', 'Voir la commande')
            ->linkToRoute('app_admin_order_show', function (Order $order): array {
                return ['id' => $order->getId()];
            })
            ->setIcon('fa fa-eye')
            ->setCssClass('btn btn-info');

        $markAsPaid = Action::new('markAsPaid', 'Marquer payée')
            ->linkToRoute('app_admin_order_mark_paid', function (Order $order): array {
                return ['id' => $order->getId()];
            })
            ->setIcon('fa fa-credit-card')
            ->setCssClass('btn btn-success')
            ->displayIf(function (Order $order) {
                return $order->getStatus() === Order::STATUS_PENDING;
            });

        $markAsShipped = Action::new('markAsShipped', 'Marquer expédiée')
            ->linkToRoute('app_admin_order_mark_shipped', function (Order $order): array {
                return ['id' => $order->getId()];
            })
            ->setIcon('fa fa-truck')
            ->setCssClass('btn btn-warning')
            ->displayIf(function (Order $order) {
                return in_array($order->getStatus(), [Order::STATUS_PAID, Order::STATUS_PROCESSING]);
            });

        $markAsDelivered = Action::new('markAsDelivered', 'Marquer livrée')
            ->linkToRoute('app_admin_order_mark_delivered', function (Order $order): array {
                return ['id' => $order->getId()];
            })
            ->setIcon('fa fa-check-circle')
            ->setCssClass('btn btn-success')
            ->displayIf(function (Order $order) {
                return $order->getStatus() === Order::STATUS_SHIPPED;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $viewOrder)
            ->add(Crud::PAGE_INDEX, $markAsPaid)
            ->add(Crud::PAGE_INDEX, $markAsShipped)
            ->add(Crud::PAGE_INDEX, $markAsDelivered)
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
            ->add('createdAt')
            ->add('totalAmount');
    }
}
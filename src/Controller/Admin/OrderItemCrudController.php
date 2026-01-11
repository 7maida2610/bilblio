<?php

namespace App\Controller\Admin;

use App\Entity\OrderItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class OrderItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article de Commande')
            ->setEntityLabelInPlural('Articles de Commande')
            ->setPageTitle('index', 'Gestion des Articles de Commande')
            ->setPageTitle('detail', 'Détails de l\'article #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier l\'article #%entity_short_id%')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('order', 'Commande'),
            AssociationField::new('livre', 'Livre'),
            NumberField::new('quantity', 'Quantité'),
            MoneyField::new('unitPrice', 'Prix unitaire')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            MoneyField::new('subtotal', 'Sous-total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->onlyOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('order')
            ->add('livre')
            ->add('quantity')
            ->add('unitPrice');
    }
}
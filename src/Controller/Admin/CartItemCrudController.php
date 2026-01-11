<?php

namespace App\Controller\Admin;

use App\Entity\CartItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CartItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CartItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article du Panier')
            ->setEntityLabelInPlural('Articles du Panier')
            ->setPageTitle('index', 'Gestion des Articles du Panier')
            ->setPageTitle('detail', 'Details de l\'article #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier l\'article #%entity_short_id%')
            ->setDefaultSort(['addedAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('cart', 'Panier'),
            AssociationField::new('livre', 'Livre'),
            NumberField::new('quantity', 'Quantité'),
            TextField::new('subtotalFormatted', 'Sous-total')
                ->onlyOnIndex()
                ->hideOnForm(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('cart')
            ->add('livre')
            ->add('quantity');
    }
}
<?php

namespace App\Controller\Admin;

use App\Entity\Cart;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CartCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cart::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Panier')
            ->setEntityLabelInPlural('Paniers')
            ->setPageTitle('index', 'Gestion des Paniers')
            ->setPageTitle('detail', 'Détails du panier #%entity_short_id%')
            ->setPageTitle('edit', 'Modifier le panier #%entity_short_id%')
            ->setDefaultSort(['updatedAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->onlyOnIndex(),
            AssociationField::new('user', 'Utilisateur'),
            AssociationField::new('cartItems', 'Articles')
                ->onlyOnDetail(),
            TextField::new('totalPriceFormatted', 'Total')
                ->onlyOnIndex()
                ->hideOnForm(),
            TextField::new('createdAt', 'Créé le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
            TextField::new('updatedAt', 'Modifié le')
                ->onlyOnDetail()
                ->hideOnForm()
                ->formatValue(function ($value) { return $value instanceof \DateTime ? $value->format('d/m/Y H:i') : $value; }),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('createdAt')
            ->add('updatedAt');
    }
}
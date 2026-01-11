<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CategorieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Categorie::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('designation', 'Désignation'),
            TextEditorField::new('description', 'Description')
                ->hideOnIndex()
                ->setFormTypeOptions([
                    'attr' => ['rows' => 3]
                ]),

            // Audit fields - only visible to admins
            TextField::new('createdAtFormatted', 'Créé le')
                ->hideOnForm()
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
            TextField::new('updatedAtFormatted', 'Modifié le')
                ->hideOnForm()
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('createdBy', 'Créé par')
                ->hideOnForm()
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
            AssociationField::new('updatedBy', 'Modifié par')
                ->hideOnForm()
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setSearchFields(['designation', 'description'])
            ->setDefaultSort(['designation' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('designation');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
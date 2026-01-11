<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class ActivityLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActivityLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Log d\'Activité')
            ->setEntityLabelInPlural('Logs d\'Activité')
            ->setPageTitle('index', 'Logs d\'Activité')
            ->setPageTitle('detail', 'Détail du Log')
            ->setPageTitle('edit', 'Modifier le Log')
            ->setPageTitle('new', 'Nouveau Log')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm()
                ->hideOnIndex(),

            AssociationField::new('user')
                ->setLabel('Utilisateur')
                ->setRequired(true)
                ->setColumns(6),

            TextField::new('action')
                ->setLabel('Action')
                ->setRequired(true)
                ->setColumns(6),

            TextareaField::new('description')
                ->setLabel('Description')
                ->setRequired(false)
                ->setColumns(12)
                ->hideOnIndex(),

            CodeEditorField::new('metadata')
                ->setLabel('Métadonnées')
                ->setRequired(false)
                ->setColumns(12)
                ->hideOnIndex(),

            TextField::new('ipAddress')
                ->setLabel('Adresse IP')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),

            TextField::new('userAgent')
                ->setLabel('User Agent')
                ->setRequired(false)
                ->setColumns(6)
                ->hideOnIndex(),

            DateTimeField::new('createdAt')
                ->setLabel('Date de création')
                ->setRequired(true)
                ->setColumns(6)
                ->onlyOnDetail()
                ->hideOnForm()
                ->setFormat('dd/MM/yyyy HH:mm'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setCssClass('btn btn-outline-danger');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('action')
            ->add('createdAt')
        ;
    }
}
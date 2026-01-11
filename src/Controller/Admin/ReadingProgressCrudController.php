<?php

namespace App\Controller\Admin;

use App\Entity\ReadingProgress;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReadingProgressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReadingProgress::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user')
                ->setLabel('User')
                ->setRequired(true),
            AssociationField::new('livre')
                ->setLabel('Book')
                ->setRequired(true),
            NumberField::new('progressPercentage')
                ->setLabel('Progress (%)')
                ->setRequired(true)
                ->setHelp('Reading progress as percentage (0-100)'),
            TextField::new('lastReadAt')
                ->setLabel('Last Read')
                ->hideOnIndex()
                ->hideOnForm(),
            BooleanField::new('isCompleted')
                ->setLabel('Completed')
                ->setHelp('Whether the user has completed reading this book'),
        ];
    }
}
<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
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
            NumberField::new('rating')
                ->setLabel('Rating')
                ->setRequired(true)
                ->setHelp('Rating from 1 to 5 stars'),
            TextareaField::new('comment')
                ->setLabel('Comment')
                ->setRequired(false)
                ->setHelp('User\'s review comment (optional)'),
            TextField::new('createdAt')
                ->setLabel('Created At')
                ->hideOnIndex()
                ->hideOnForm()
                ->setDisabled(true),
        ];
    }
}
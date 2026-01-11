<?php

namespace App\Controller\Admin;

use App\Entity\ReadingGoal;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReadingGoalCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReadingGoal::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user')
                ->setLabel('User')
                ->setRequired(true),
            ChoiceField::new('goalType')
                ->setLabel('Goal Type')
                ->setChoices([
                    'Books This Year' => 'books_year',
                    'Pages This Month' => 'pages_month',
                ])
                ->setRequired(true),
            NumberField::new('targetValue')
                ->setLabel('Target Value')
                ->setRequired(true)
                ->setHelp('Target number of books or pages'),
            NumberField::new('currentValue')
                ->setLabel('Current Value')
                ->setRequired(true)
                ->setHelp('Current progress towards the goal'),
            TextField::new('startDate')
                ->setLabel('Start Date')
                ->setRequired(true)
                ->hideOnIndex()
                ->hideOnForm(),
            TextField::new('endDate')
                ->setLabel('End Date')
                ->setRequired(true)
                ->hideOnIndex()
                ->hideOnForm(),
        ];
    }
}
<?php

namespace App\Form;

use App\Entity\ReadingGoal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReadingGoalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('goalType', ChoiceType::class, [
                'choices' => [
                    'Books This Year' => 'books_year',
                    'Pages This Month' => 'pages_month',
                ],
                'label' => 'Goal Type',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('targetValue', IntegerType::class, [
                'label' => 'Target Value',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Enter your target...'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReadingGoal::class,
        ]);
    }
}
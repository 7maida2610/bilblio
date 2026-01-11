<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class BillingAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Khlif'],
                'constraints' => [new NotBlank(['message' => 'Le nom est obligatoire'])],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Ahmed'],
                'constraints' => [new NotBlank(['message' => 'Le prénom est obligatoire'])],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 123 Rue de la Paix'],
                'constraints' => [new NotBlank(['message' => 'L\'adresse est obligatoire'])],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: 1000'],
                'constraints' => [new NotBlank(['message' => 'Le code postal est obligatoire'])],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Tunis'],
                'constraints' => [new NotBlank(['message' => 'La ville est obligatoire'])],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Tunisie'],
                'constraints' => [new NotBlank(['message' => 'Le pays est obligatoire'])],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}

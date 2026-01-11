<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Ahmed'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'ex: Khlif'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('profilePicture', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'attr' => ['class' => 'form-control-file'],
                'help' => 'Formats acceptés: JPEG, PNG, GIF, WebP. Taille max: 5MB',
            ])
            // Billing Address Fields (mapped to billingAddress JSON)
            ->add('billingPrenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Jean'],
            ])
            ->add('billingNom', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dupont'],
            ])
            ->add('billingAdresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '123 Rue de la Paix'],
            ])
            ->add('billingCodePostal', TextType::class, [
                'label' => 'Code Postal',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '75001'],
            ])
            ->add('billingVille', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Paris'],
            ])
            ->add('billingPays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'France'],
            ])
            // Shipping Address Fields (mapped to shippingAddress JSON)
            ->add('shippingPrenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Jean'],
            ])
            ->add('shippingNom', TextType::class, [
                'label' => 'Nom de famille',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dupont'],
            ])
            ->add('shippingAdresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '123 Rue de la Paix'],
            ])
            ->add('shippingCodePostal', TextType::class, [
                'label' => 'Code Postal',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '75001'],
            ])
            ->add('shippingVille', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Paris'],
            ])
            ->add('shippingPays', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'France'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
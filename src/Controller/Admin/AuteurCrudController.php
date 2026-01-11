<?php

namespace App\Controller\Admin;

use App\Entity\Auteur;
use App\Service\CloudinaryService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

class AuteurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Auteur::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('prenom', 'Prénom'),
            TextField::new('nom', 'Nom'),
            ImageField::new('image', 'Photo')
                ->setBasePath('uploads/auteur_images/')
                ->setUploadDir('public/uploads/auteur_images/')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false)
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return null;
                    }
                    // If it's a Cloudinary URL, return it directly
                    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                        return $value;
                    }
                    // Otherwise, it's a local file
                    return 'uploads/auteur_images/' . $value;
                }),
            TextEditorField::new('biographie', 'Biographie')
                ->hideOnIndex()
                ->setFormTypeOptions([
                    'attr' => ['rows' => 5]
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
            ->setEntityLabelInSingular('Auteur')
            ->setEntityLabelInPlural('Auteurs')
            ->setSearchFields(['prenom', 'nom', 'biographie'])
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('nom')
            ->add('prenom');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createEditFormBuilder(\EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto, \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $cloudinaryService = $this->container->get(CloudinaryService::class);
        $originalImage = $entityDto->getInstance()?->getImage();

        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($cloudinaryService, $originalImage) {
            $auteur = $event->getData();
            $form = $event->getForm();
            
            // Get the uploaded image file
            $imageValue = $form->get('image')->getData();
            
            // If a new file was uploaded (not empty and different from original)
            if ($imageValue && $imageValue !== $originalImage) {
                // Check if it's an UploadedFile instance (new upload)
                // EasyAdmin ImageField stores the filename, so we need to check if file exists locally
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/auteur_images/';
                $localPath = $uploadDir . $imageValue;
                
                if (file_exists($localPath) && is_file($localPath)) {
                    // Create a temporary UploadedFile from the local file
                    $tempFile = new \Symfony\Component\HttpFoundation\File\File($localPath);
                    $uploadedFile = new UploadedFile(
                        $localPath,
                        $imageValue,
                        $tempFile->getMimeType(),
                        null,
                        true
                    );
                    
                    // Cloudinary only - no local fallback
                    $cloudinaryUrl = $cloudinaryService->uploadImage($uploadedFile, 'biblio/auteur_images');
                    if ($cloudinaryUrl) {
                        // Delete local file and use Cloudinary URL
                        @unlink($localPath);
                        $auteur->setImage($cloudinaryUrl);
                    } else {
                        @unlink($localPath); // Remove local file even if Cloudinary fails
                        throw new \RuntimeException('Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                    }
                }
            }
        });

        return $formBuilder;
    }

    public function createNewFormBuilder(\EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto, \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $cloudinaryService = $this->container->get(CloudinaryService::class);

        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($cloudinaryService) {
            $auteur = $event->getData();
            $form = $event->getForm();
            
            $imageValue = $form->get('image')->getData();
            
            if ($imageValue) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/auteur_images/';
                $localPath = $uploadDir . $imageValue;
                
                if (file_exists($localPath) && is_file($localPath)) {
                    $tempFile = new \Symfony\Component\HttpFoundation\File\File($localPath);
                    $uploadedFile = new UploadedFile(
                        $localPath,
                        $imageValue,
                        $tempFile->getMimeType(),
                        null,
                        true
                    );
                    
                    // Cloudinary only - no local fallback
                    $cloudinaryUrl = $cloudinaryService->uploadImage($uploadedFile, 'biblio/auteur_images');
                    if ($cloudinaryUrl) {
                        @unlink($localPath);
                        $auteur->setImage($cloudinaryUrl);
                    } else {
                        @unlink($localPath); // Remove local file even if Cloudinary fails
                        throw new \RuntimeException('Cloudinary upload failed. Please configure CLOUDINARY_URL.');
                    }
                }
            }
        });

        return $formBuilder;
    }
}
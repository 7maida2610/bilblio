<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use App\Service\CloudinaryService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LivreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Livre::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('titre', 'Titre'),
            TextField::new('isbn', 'ISBN'),
            IntegerField::new('nbPages', 'Nombre de pages'),
            
            // Stock fields
            IntegerField::new('nbExemplaires', 'Total exemplaires')
                ->setHelp('Nombre total de référence (informatif)')
                ->hideOnIndex(),
            IntegerField::new('stockVente', 'Stock Vente')
                ->setHelp('Exemplaires disponibles à la vente')
                ->setColumns(6),
            IntegerField::new('stockEmprunt', 'Stock Emprunt')
                ->setHelp('Exemplaires disponibles pour emprunt')
                ->setColumns(6),
            IntegerField::new('totalStock', 'Stock Total')
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    return $entity->getStockVente() + $entity->getStockEmprunt();
                }),
            
            MoneyField::new('prix', 'Prix')->setCurrency('EUR'),
            DateField::new('dateEdition', 'Date d\'édition')
                ->setFormType('Symfony\Component\Form\Extension\Core\Type\DateType')
                ->hideOnIndex()
                ->hideOnDetail()
                ->hideOnForm(),
            BooleanField::new('isBorrowable', 'Disponible pour emprunt')
                ->renderAsSwitch(false),
            AssociationField::new('auteur', 'Auteur'),
            AssociationField::new('categorie', 'Catégorie'),
            AssociationField::new('editeur', 'Éditeur'),
            ImageField::new('image', 'Image de couverture')
                ->setBasePath('uploads/images/')
                ->setUploadDir('public/uploads/images/')
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
                    return 'uploads/images/' . $value;
                }),
            Field::new('pdf', 'Document PDF')
                ->setFormType(FileType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'attr' => [
                        'accept' => '.pdf'
                    ],
                    'data_class' => null, // Important for file fields
                ])
                ->onlyOnForms()
                ->setHelp('Téléchargez un fichier PDF (format accepté: .pdf). Si un fichier existe déjà, il sera remplacé.'),
            TextField::new('pdf', 'Document PDF')
                ->hideOnForm()
                ->renderAsHtml()
                ->formatValue(function ($value, $entity) {
                    if ($value) {
                        // If it's a Cloudinary URL, use it directly
                        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                            return sprintf('<a href="%s" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-file-pdf mr-1"></i>Voir PDF</a>', $value);
                        }
                        // Otherwise, it's a local file
                        return sprintf('<a href="/uploads/pdfs/%s" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-file-pdf mr-1"></i>Voir PDF</a>', $value);
                    }
                    return 'Aucun PDF';
                }),

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
            ->setEntityLabelInSingular('Livre')
            ->setEntityLabelInPlural('Livres')
            ->setSearchFields(['titre', 'isbn', 'auteur.prenom', 'auteur.nom', 'categorie.designation'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('auteur')
            ->add('categorie')
            ->add('editeur');
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

        $slugger = $this->container->get(SluggerInterface::class);
        $pdfDirectory = $this->getParameter('pdf_directory');
        
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($cloudinaryService, $slugger, $pdfDirectory) {
            $livre = $event->getData();
            $form = $event->getForm();

            $pdfFile = $form->get('pdf')->getData();
            if ($pdfFile instanceof UploadedFile) {
                // Try Cloudinary first, fallback to local storage if not configured
                $cloudinaryUrl = $cloudinaryService->uploadPdf($pdfFile, 'biblio/pdfs');
                if ($cloudinaryUrl) {
                    $livre->setPdf($cloudinaryUrl);
                } else {
                    // Fallback to local storage if Cloudinary is not configured
                    try {
                        if (!is_dir($pdfDirectory)) {
                            mkdir($pdfDirectory, 0777, true);
                        }
                        $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename)->toString();
                        $newFilename = $safeFilename . '-' . uniqid() . '.pdf';
                        $pdfFile->move($pdfDirectory, $newFilename);
                        $livre->setPdf('uploads/pdfs/' . $newFilename);
                    } catch (FileException $e) {
                        // Log error but don't throw - let the form handle it gracefully
                        error_log('PDF upload error: ' . $e->getMessage());
                    }
                }
            }
            // If no new file uploaded, keep existing PDF (do nothing)
        });

        return $formBuilder;
    }

    public function createNewFormBuilder(\EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto $entityDto, \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        $cloudinaryService = $this->container->get(CloudinaryService::class);

        $slugger = $this->container->get(SluggerInterface::class);
        $pdfDirectory = $this->getParameter('pdf_directory');
        
        $formBuilder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($cloudinaryService, $slugger, $pdfDirectory) {
            $livre = $event->getData();
            $form = $event->getForm();

            $pdfFile = $form->get('pdf')->getData();
            if ($pdfFile instanceof UploadedFile) {
                // Try Cloudinary first, fallback to local storage if not configured
                $cloudinaryUrl = $cloudinaryService->uploadPdf($pdfFile, 'biblio/pdfs');
                if ($cloudinaryUrl) {
                    $livre->setPdf($cloudinaryUrl);
                } else {
                    // Fallback to local storage if Cloudinary is not configured
                    try {
                        if (!is_dir($pdfDirectory)) {
                            mkdir($pdfDirectory, 0777, true);
                        }
                        $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename)->toString();
                        $newFilename = $safeFilename . '-' . uniqid() . '.pdf';
                        $pdfFile->move($pdfDirectory, $newFilename);
                        $livre->setPdf('uploads/pdfs/' . $newFilename);
                    } catch (FileException $e) {
                        // Log error but don't throw - let the form handle it gracefully
                        error_log('PDF upload error: ' . $e->getMessage());
                    }
                }
            }
        });

        return $formBuilder;
    }
}
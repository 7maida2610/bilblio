<?php

namespace App\Controller\Admin;

use App\Entity\Banner;
use App\Entity\UserBannerPreference;
use App\Repository\UserBannerPreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BannerCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserBannerPreferenceRepository $preferenceRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Banner::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bannière')
            ->setEntityLabelInPlural('Bannières')
            ->setPageTitle('index', 'Gestion des Bannières')
            ->setPageTitle('new', 'Créer une Bannière')
            ->setPageTitle('edit', 'Modifier la Bannière')
            ->setPageTitle('detail', 'Détails de la Bannière')
            ->setDefaultSort(['priority' => 'DESC', 'createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(ChoiceFilter::new('type', 'Type')
                ->setChoices(Banner::TYPES))
            ->add(ChoiceFilter::new('position', 'Position')
                ->setChoices(Banner::POSITIONS))
            ->add(ChoiceFilter::new('status', 'Statut')
                ->setChoices(Banner::STATUSES))
            ->add(DateTimeFilter::new('startDate', 'Date de début'))
            ->add(DateTimeFilter::new('endDate', 'Date de fin'))
            ->add(EntityFilter::new('createdBy', 'Créé par'));
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [];

        if ($pageName === Crud::PAGE_INDEX) {
            $fields = [
                IdField::new('id'),
                TextField::new('title', 'Titre'),
                ChoiceField::new('type', 'Type')
                    ->renderAsBadges([
                        Banner::TYPE_PROMOTION => 'success',
                        Banner::TYPE_ANNOUNCEMENT => 'info',
                        Banner::TYPE_WARNING => 'warning',
                        Banner::TYPE_INFO => 'primary',
                    ]),
                ChoiceField::new('position', 'Position')
                    ->setChoices(Banner::POSITIONS),
                ChoiceField::new('status', 'Statut')
                    ->renderAsBadges([
                        Banner::STATUS_ACTIVE => 'success',
                        Banner::STATUS_INACTIVE => 'secondary',
                        Banner::STATUS_SCHEDULED => 'warning',
                        Banner::STATUS_EXPIRED => 'danger',
                    ]),
                NumberField::new('priority', 'Priorité'),
            ];
        } elseif ($pageName === Crud::PAGE_DETAIL) {
            $fields = [
                IdField::new('id'),
                TextField::new('title', 'Titre'),
                TextEditorField::new('content', 'Contenu'),
                ChoiceField::new('type', 'Type')
                    ->renderAsBadges([
                        Banner::TYPE_PROMOTION => 'success',
                        Banner::TYPE_ANNOUNCEMENT => 'info',
                        Banner::TYPE_WARNING => 'warning',
                        Banner::TYPE_INFO => 'primary',
                    ]),
                ChoiceField::new('position', 'Position')
                    ->setChoices(Banner::POSITIONS),
                ChoiceField::new('status', 'Statut')
                    ->renderAsBadges([
                        Banner::STATUS_ACTIVE => 'success',
                        Banner::STATUS_INACTIVE => 'secondary',
                        Banner::STATUS_SCHEDULED => 'warning',
                        Banner::STATUS_EXPIRED => 'danger',
                    ]),
                DateTimeField::new('startDate', 'Date de début'),
                DateTimeField::new('endDate', 'Date de fin'),
                NumberField::new('priority', 'Priorité')
                    ->setHelp('Plus le nombre est élevé, plus la bannière sera prioritaire'),
                ImageField::new('image', 'Image')
                    ->setBasePath('uploads/banners/')
                    ->setUploadDir('public/uploads/banners/')
                    ->setUploadedFileNamePattern('[randomhash].[extension]'),
                UrlField::new('link', 'Lien'),
                TextField::new('linkText', 'Texte du lien'),
                TextareaField::new('targetAudience', 'Public cible')
                    ->setHelp('Format JSON array: ["guest", "ROLE_USER", "ROLE_ADMIN"]. Vide = tous les utilisateurs'),
                AssociationField::new('createdBy', 'Créé par'),
                TextField::new('createdAt', 'Créé le')
                    ->hideOnForm()
                    ->formatValue(function ($value) { return $value instanceof \DateTimeImmutable ? $value->format('d/m/Y H:i:s') : $value; }),
                TextField::new('updatedAt', 'Modifié le')
                    ->hideOnForm()
                    ->formatValue(function ($value) { return $value instanceof \DateTimeImmutable ? $value->format('d/m/Y H:i:s') : $value; }),
            ];
        } else { // EDIT & NEW
            $fields = [
                TextField::new('title', 'Titre')
                    ->setRequired(true)
                    ->setMaxLength(255),
                TextEditorField::new('content', 'Contenu')
                    ->setRequired(false)
                    ->setNumOfRows(6),
                ChoiceField::new('type', 'Type')
                    ->setChoices(Banner::TYPES)
                    ->setRequired(true),
                ChoiceField::new('position', 'Position')
                    ->setChoices(Banner::POSITIONS)
                    ->setRequired(true),
                ChoiceField::new('status', 'Statut')
                    ->setChoices(Banner::STATUSES)
                    ->setRequired(true),
                DateTimeField::new('startDate', 'Date de début')
                    ->setRequired(false)
                    ->setHelp('Laisser vide pour une activation immédiate'),
                DateTimeField::new('endDate', 'Date de fin')
                    ->setRequired(false)
                    ->setHelp('Laisser vide pour une durée illimitée'),
                NumberField::new('priority', 'Priorité')
                    ->setRequired(false)
                    ->setHelp('Plus le nombre est élevé, plus la bannière sera prioritaire (1-100)'),
                ImageField::new('image', 'Image')
                    ->setBasePath('uploads/banners/')
                    ->setUploadDir('public/uploads/banners/')
                    ->setUploadedFileNamePattern('[randomhash].[extension]')
                    ->setRequired(false),
                UrlField::new('link', 'Lien')
                    ->setRequired(false),
                TextField::new('linkText', 'Texte du lien')
                    ->setRequired(false),
                TextareaField::new('targetAudience', 'Public cible')
                    ->setRequired(false)
                    ->setHelp('JSON array: ["guest", "ROLE_USER", "ROLE_ADMIN"]. Vide = tous les utilisateurs'),
            ];
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        // Activate action
        $activateAction = Action::new('activate', 'Activer')
            ->linkToRoute('admin_banner_activate', function (Banner $banner): array {
                return ['id' => $banner->getId()];
            })
            ->setCssClass('btn btn-success btn-sm')
            ->setIcon('fa fa-power-off')
            ->displayIf(function (Banner $banner) {
                return $banner->getStatus() !== Banner::STATUS_ACTIVE;
            })
            ->addCssClass('text-white');

        // Deactivate action
        $deactivateAction = Action::new('deactivate', 'Désactiver')
            ->linkToRoute('admin_banner_deactivate', function (Banner $banner): array {
                return ['id' => $banner->getId()];
            })
            ->setCssClass('btn btn-warning btn-sm')
            ->setIcon('fa fa-ban')
            ->displayIf(function (Banner $banner) {
                return $banner->getStatus() === Banner::STATUS_ACTIVE;
            })
            ->addCssClass('text-white');

        // Preview action
        $previewAction = Action::new('preview', 'Aperçu')
            ->linkToRoute('admin_banner_preview', function (Banner $banner): array {
                return ['id' => $banner->getId()];
            })
            ->setCssClass('btn btn-info btn-sm')
            ->setIcon('fa fa-eye')
            ->addCssClass('text-white');

        // Reset preferences action
        $resetAction = Action::new('resetPrefs', 'Réinit. préférences')
            ->linkToRoute('admin_banner_reset_preferences', function (Banner $banner): array {
                return ['id' => $banner->getId()];
            })
            ->setCssClass('btn btn-secondary btn-sm')
            ->setIcon('fa fa-refresh')
            ->addCssClass('text-white');

        // Statistics action
        $statsAction = Action::new('stats', 'Statistiques')
            ->linkToRoute('admin_banner_statistics', function (Banner $banner): array {
                return ['id' => $banner->getId()];
            })
            ->setCssClass('btn btn-primary btn-sm')
            ->setIcon('fa fa-bar-chart')
            ->addCssClass('text-white');

        return $actions
            ->add(Crud::PAGE_INDEX, $activateAction)
            ->add(Crud::PAGE_INDEX, $deactivateAction)
            ->add(Crud::PAGE_INDEX, $previewAction)
            ->add(Crud::PAGE_INDEX, $statsAction)
            ->add(Crud::PAGE_DETAIL, $activateAction)
            ->add(Crud::PAGE_DETAIL, $deactivateAction)
            ->add(Crud::PAGE_DETAIL, $previewAction)
            ->add(Crud::PAGE_DETAIL, $resetAction)
            ->add(Crud::PAGE_DETAIL, $statsAction)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('+ Créer une bannière');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Éditer');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Supprimer');
            });
    }

    #[Route('/admin/banner/{id}/activate', name: 'admin_banner_activate', methods: ['GET', 'POST'])]
    public function activateBanner(Banner $banner): Response
    {
        $banner->setStatus(Banner::STATUS_ACTIVE);
        $this->em->flush();

        $this->addFlash('success', "La bannière '{$banner->getTitle()}' a été activée.");

        return $this->redirect($this->generateUrl('admin', ['crudAction' => 'index', 'crudControllerFqcn' => BannerCrudController::class]));
    }

    #[Route('/admin/banner/{id}/deactivate', name: 'admin_banner_deactivate', methods: ['GET', 'POST'])]
    public function deactivateBanner(Banner $banner): Response
    {
        $banner->setStatus(Banner::STATUS_INACTIVE);
        $this->em->flush();

        $this->addFlash('success', "La bannière '{$banner->getTitle()}' a été désactivée.");

        return $this->redirect($this->generateUrl('admin', ['crudAction' => 'index', 'crudControllerFqcn' => BannerCrudController::class]));
    }

    #[Route('/admin/banner/{id}/preview', name: 'admin_banner_preview', methods: ['GET'])]
    public function previewBanner(Banner $banner): Response
    {
        return $this->render('admin/banner/preview.html.twig', [
            'banner' => $banner,
        ]);
    }

    #[Route('/admin/banner/{id}/statistics', name: 'admin_banner_statistics', methods: ['GET'])]
    public function bannerStatistics(Banner $banner): Response
    {
        // Get dismissal statistics
        $preferences = $this->preferenceRepository->findBy(['banner' => $banner]);
        $dismissedCount = count(array_filter($preferences, function (UserBannerPreference $p) {
            return $p->isHidden();
        }));

        $stats = [
            'totalPreferences' => count($preferences),
            'dismissedCount' => $dismissedCount,
            'dismissalRate' => count($preferences) > 0 ? round(($dismissedCount / count($preferences)) * 100, 2) : 0,
            'banner' => $banner,
        ];

        return $this->render('admin/banner/statistics.html.twig', $stats);
    }

    #[Route('/admin/banner/{id}/reset-preferences', name: 'admin_banner_reset_preferences', methods: ['GET', 'POST'])]
    public function resetPreferences(Banner $banner): Response
    {
        // Reset all preferences for this banner
        $preferences = $this->preferenceRepository->findBy(['banner' => $banner]);
        foreach ($preferences as $pref) {
            $this->em->remove($pref);
        }
        $this->em->flush();

        $this->addFlash('success', "Les préférences utilisateur pour la bannière '{$banner->getTitle()}' ont été réinitialisées.");

        return $this->redirect($this->generateUrl('admin', ['crudAction' => 'detail', 'crudControllerFqcn' => BannerCrudController::class, 'entityId' => $banner->getId()]));
    }
}
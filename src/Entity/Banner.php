<?php

namespace App\Entity;

use App\Repository\BannerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BannerRepository::class)]
#[ORM\Table(name: 'banners')]
#[ORM\HasLifecycleCallbacks]
class Banner
{
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';

    public const TYPES = [
        self::TYPE_PROMOTION => 'Promotion',
        self::TYPE_ANNOUNCEMENT => 'Annonce',
        self::TYPE_WARNING => 'Avertissement',
        self::TYPE_INFO => 'Information'
    ];

    public const POSITION_TOP = 'top';
    public const POSITION_BOTTOM = 'bottom';
    public const POSITION_SIDEBAR = 'sidebar';
    public const POSITION_POPUP = 'popup';

    public const POSITIONS = [
        self::POSITION_TOP => 'En haut',
        self::POSITION_BOTTOM => 'En bas',
        self::POSITION_SIDEBAR => 'Barre latérale',
        self::POSITION_POPUP => 'Popup'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Actif',
        self::STATUS_INACTIVE => 'Inactif',
        self::STATUS_SCHEDULED => 'Programmé',
        self::STATUS_EXPIRED => 'Expiré'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(max: 255, maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: self::TYPES, message: "Type de bannière invalide")]
    private ?string $type = self::TYPE_INFO;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: self::POSITIONS, message: "Position invalide")]
    private ?string $position = self::POSITION_TOP;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_INACTIVE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $linkText = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 100, notInRangeMessage: "La priorité doit être entre {{ min }} et {{ max }}")]
    private ?int $priority = 1;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $targetAudience = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $styling = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->targetAudience = [];
        $this->styling = [];
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updateStatus();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->updateStatus();
    }

    private function updateStatus(): void
    {
        $now = new \DateTime();

        if ($this->status === self::STATUS_SCHEDULED && $this->startDate && $now >= $this->startDate) {
            $this->status = self::STATUS_ACTIVE;
        } elseif ($this->status === self::STATUS_ACTIVE && $this->endDate && $now > $this->endDate) {
            $this->status = self::STATUS_EXPIRED;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getPositionLabel(): string
    {
        return self::POSITIONS[$this->position] ?? $this->position;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    public function setLinkText(?string $linkText): static
    {
        $this->linkText = $linkText;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getTargetAudience(): array
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(array $targetAudience): static
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    public function getStyling(): array
    {
        return $this->styling;
    }

    public function setStyling(array $styling): static
    {
        $this->styling = $styling;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Check if banner is currently active
     */
    public function isActive(): bool
    {
        $now = new \DateTime();

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->startDate && $now < $this->startDate) {
            return false;
        }

        if ($this->endDate && $now > $this->endDate) {
            return false;
        }

        return true;
    }

    /**
     * Check if banner is visible for a specific user
     */
    public function isVisibleForUser(?User $user): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (empty($this->targetAudience)) {
            return true; // Visible to all users
        }

        if (!$user) {
            return in_array('guest', $this->targetAudience);
        }

        // Check user roles
        foreach ($user->getRoles() as $role) {
            if (in_array($role, $this->targetAudience)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get CSS classes for styling
     */
    public function getCssClasses(): string
    {
        $classes = ['banner', 'banner-' . $this->type];

        if (!empty($this->styling)) {
            if (isset($this->styling['background_color'])) {
                $classes[] = 'custom-bg';
            }
            if (isset($this->styling['text_color'])) {
                $classes[] = 'custom-text';
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Get inline styles
     */
    public function getInlineStyles(): string
    {
        $styles = [];

        if (!empty($this->styling)) {
            if (isset($this->styling['background_color'])) {
                $styles[] = 'background-color: ' . $this->styling['background_color'];
            }
            if (isset($this->styling['text_color'])) {
                $styles[] = 'color: ' . $this->styling['text_color'];
            }
            if (isset($this->styling['border_color'])) {
                $styles[] = 'border-color: ' . $this->styling['border_color'];
            }
        }

        return implode('; ', $styles);
    }
}
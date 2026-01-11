<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\Table(name: 'loans')]
#[ORM\HasLifecycleCallbacks]
class Loan
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED => 'Demandé',
        self::STATUS_APPROVED => 'Approuvé',
        self::STATUS_ACTIVE => 'En cours',
        self::STATUS_OVERDUE => 'En retard',
        self::STATUS_RETURNED => 'Retourné',
        self::STATUS_CANCELLED => 'Annulé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approvedBy = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_REQUESTED;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $requestedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $loanStartDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->status = self::STATUS_REQUESTED;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): static
    {
        $this->livre = $livre;

        return $this;
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

    public function getRequestedAt(): ?\DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeImmutable $requestedAt): static
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeImmutable $approvedAt): static
    {
        $this->approvedAt = $approvedAt;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): static
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    public function getLoanStartDate(): ?\DateTimeImmutable
    {
        return $this->loanStartDate;
    }

    public function setLoanStartDate(?\DateTimeImmutable $loanStartDate): static
    {
        $this->loanStartDate = $loanStartDate;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Check if loan is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE || !$this->dueDate) {
            return false;
        }

        return new \DateTimeImmutable() > $this->dueDate;
    }

    /**
     * Check if loan can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_REQUESTED && $this->livre && $this->livre->isAvailableForBorrowing();
    }

    /**
     * Check if loan can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_APPROVED]);
    }

    /**
     * Check if loan can be returned
     */
    public function canBeReturned(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get loan duration in days
     */
    public function getLoanDurationDays(): int
    {
        return 14; // Default 2 weeks
    }

    /**
     * Get days remaining until due date
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->dueDate) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->dueDate);

        return $interval->invert ? -$interval->days : $interval->days;
    }

    /**
     * Get formatted requested date
     */
    public function getRequestedAtFormatted(): string
    {
        return $this->requestedAt ? $this->requestedAt->format('d/m/Y H:i') : '';
    }

    /**
     * Get formatted approved date
     */
    public function getApprovedAtFormatted(): string
    {
        return $this->approvedAt ? $this->approvedAt->format('d/m/Y H:i') : 'Non approuvé';
    }

    /**
     * Get formatted loan start date
     */
    public function getLoanStartDateFormatted(): string
    {
        return $this->loanStartDate ? $this->loanStartDate->format('d/m/Y') : 'Non activé';
    }

    /**
     * Get formatted due date
     */
    public function getDueDateFormatted(): string
    {
        return $this->dueDate ? $this->dueDate->format('d/m/Y') : 'N/A';
    }

    /**
     * Get formatted returned date
     */
    public function getReturnedAtFormatted(): string
    {
        return $this->returnedAt ? $this->returnedAt->format('d/m/Y H:i') : 'Non retourné';
    }
}
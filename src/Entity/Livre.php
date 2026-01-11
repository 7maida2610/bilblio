<?php

namespace App\Entity;

use App\Repository\LivreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: LivreRepository::class)]
class Livre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $titre = null;

    #[ORM\Column]
    private ?int $nbPages = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateEdition = null;

    #[ORM\Column]
    private ?int $nbExemplaires = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $stockVente = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $stockEmprunt = 0;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $isbn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdf = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isBorrowable = true;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Auteur $auteur = null;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Editeur $editeur = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $updatedBy = null;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'livre')]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: BookReservation::class, mappedBy: 'livre')]
    private Collection $reservations;

    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'livre')]
    private Collection $loans;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }

    public function getNbPages(): ?int
    {
        return $this->nbPages;
    }

    public function setNbPages(int $nbPages): static
    {
        $this->nbPages = $nbPages;

        return $this;
    }

    public function getDateEdition(): ?\DateTime
    {
        return $this->dateEdition;
    }

    public function setDateEdition(\DateTime $dateEdition): static
    {
        $this->dateEdition = $dateEdition;

        return $this;
    }

    public function getNbExemplaires(): ?int
    {
        return $this->nbExemplaires;
    }

    public function setNbExemplaires(int $nbExemplaires): static
    {
        $this->nbExemplaires = $nbExemplaires;

        return $this;
    }

    public function getStockVente(): int
    {
        return $this->stockVente;
    }

    public function setStockVente(int $stockVente): static
    {
        $this->stockVente = $stockVente;

        return $this;
    }

    public function getStockEmprunt(): int
    {
        return $this->stockEmprunt;
    }

    public function setStockEmprunt(int $stockEmprunt): static
    {
        $this->stockEmprunt = $stockEmprunt;

        return $this;
    }

    /**
     * Get total stock (vente + emprunt)
     */
    public function getTotalStock(): int
    {
        return $this->stockVente + $this->stockEmprunt;
    }

    /**
     * Check if book is available for sale
     */
    public function isAvailableForSale(): bool
    {
        return $this->stockVente > 0;
    }

    /**
     * Get available copies for lending (stock - active loans)
     */
    public function getAvailableLoanCopies(): int
    {
        return max(0, $this->stockEmprunt - $this->getActiveLoansCount());
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): static
    {
        $this->isbn = $isbn;

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

    public function getPdf(): ?string
    {
        return $this->pdf;
    }

    public function setPdf(?string $pdf): static
    {
        $this->pdf = $pdf;

        return $this;
    }

    public function isBorrowable(): bool
    {
        return $this->isBorrowable;
    }

    public function setIsBorrowable(bool $isBorrowable): static
    {
        $this->isBorrowable = $isBorrowable;

        return $this;
    }

    public function getAuteur(): ?Auteur
    {
        return $this->auteur;
    }

    public function setAuteur(?Auteur $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getEditeur(): ?Editeur
    {
        return $this->editeur;
    }

    public function setEditeur(?Editeur $editeur): static
    {
        $this->editeur = $editeur;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getCreatedAtFormatted(): string
    {
        return $this->createdAt ? $this->createdAt->format('d/m/Y H:i:s') : '';
    }

    public function getUpdatedAtFormatted(): string
    {
        return $this->updatedAt ? $this->updatedAt->format('d/m/Y H:i:s') : '';
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setLivre($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getLivre() === $this) {
                $review->setLivre(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BookReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(BookReservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setLivre($this);
        }

        return $this;
    }

    public function removeReservation(BookReservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getLivre() === $this) {
                $reservation->setLivre(null);
            }
        }

        return $this;
    }

    /**
     * Get active reservations count
     */
    public function getActiveReservationsCount(): int
    {
        return $this->reservations->filter(function (BookReservation $reservation) {
            return $reservation->isActive();
        })->count();
    }

    /**
     * Check if book is available for borrowing
     */
    public function isAvailableForBorrowing(): bool
    {
        if (!$this->isBorrowable) {
            return false;
        }

        // Check if there are available copies for lending
        return $this->getAvailableLoanCopies() > 0;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setLivre($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getLivre() === $this) {
                $loan->setLivre(null);
            }
        }

        return $this;
    }

    /**
     * Get count of active loans (includes requested, approved, active, overdue)
     */
    public function getActiveLoansCount(): int
    {
        return $this->loans->filter(function (Loan $loan) {
            return in_array($loan->getStatus(), [
                Loan::STATUS_REQUESTED,
                Loan::STATUS_APPROVED,
                Loan::STATUS_ACTIVE,
                Loan::STATUS_OVERDUE
            ]);
        })->count();
    }
}

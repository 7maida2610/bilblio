<?php

namespace App\Entity;

use App\Repository\BookReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookReservationRepository::class)]
#[ORM\Table(name: 'book_reservations')]
#[ORM\HasLifecycleCallbacks]
class BookReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $requestedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $notifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expectedAvailableDate = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    #[ORM\PrePersist]
    public function setRequestedAtValue(): void
    {
        $this->requestedAt = new \DateTimeImmutable();
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

    public function getRequestedAt(): ?\DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeImmutable $requestedAt): static
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeImmutable $notifiedAt): static
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }

    public function getExpectedAvailableDate(): ?\DateTimeImmutable
    {
        return $this->expectedAvailableDate;
    }

    public function setExpectedAvailableDate(?\DateTimeImmutable $expectedAvailableDate): static
    {
        $this->expectedAvailableDate = $expectedAvailableDate;

        return $this;
    }

    public function getExpectedAvailableDateFormatted(): string
    {
        return $this->expectedAvailableDate ? $this->expectedAvailableDate->format('d/m/Y') : 'Non déterminée';
    }

    public function getRequestedAtFormatted(): string
    {
        return $this->requestedAt ? $this->requestedAt->format('d/m/Y H:i') : '';
    }

    public function getNotifiedAtFormatted(): string
    {
        return $this->notifiedAt ? $this->notifiedAt->format('d/m/Y H:i') : 'Non notifie';
    }

    /**
     * Check if user can be notified (book became available)
     */
    public function canBeNotified(): bool
    {
        return $this->isActive && $this->notifiedAt === null;
    }
}
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $billingAddress = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Livre::class)]
    #[ORM\JoinTable(name: 'user_wishlist')]
    private Collection $wishlist;

    #[ORM\ManyToMany(targetEntity: Livre::class)]
    #[ORM\JoinTable(name: 'user_owned_books')]
    private Collection $ownedBooks;

    #[ORM\ManyToMany(targetEntity: Auteur::class)]
    #[ORM\JoinTable(name: 'user_favorite_authors')]
    private Collection $favoriteAuthors;

    #[ORM\OneToMany(targetEntity: ReadingProgress::class, mappedBy: 'user')]
    private Collection $readingProgress;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Cart::class, orphanRemoval: true)]
    private Collection $carts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class, orphanRemoval: true)]
    private Collection $orders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Loan::class, orphanRemoval: true)]
    private Collection $loans;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BookReservation::class, orphanRemoval: true)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ReadingGoal::class, orphanRemoval: true)]
    private Collection $readingGoals;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserBannerPreference::class, orphanRemoval: true)]
    private Collection $bannerPreferences;

    public function __construct()
    {
        $this->wishlist = new ArrayCollection();
        $this->ownedBooks = new ArrayCollection();
        $this->favoriteAuthors = new ArrayCollection();
        $this->readingProgress = new ArrayCollection();
        $this->carts = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->loans = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->readingGoals = new ArrayCollection();
        $this->bannerPreferences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Livre>
     */
    public function getWishlist(): Collection
    {
        return $this->wishlist;
    }

    public function addToWishlist(Livre $livre): static
    {
        if (!$this->wishlist->contains($livre)) {
            $this->wishlist->add($livre);
        }

        return $this;
    }

    public function removeFromWishlist(Livre $livre): static
    {
        $this->wishlist->removeElement($livre);

        return $this;
    }

    /**
     * @return Collection<int, Livre>
     */
    public function getOwnedBooks(): Collection
    {
        return $this->ownedBooks;
    }

    public function addOwnedBook(Livre $livre): static
    {
        if (!$this->ownedBooks->contains($livre)) {
            $this->ownedBooks->add($livre);
        }

        return $this;
    }

    public function removeOwnedBook(Livre $livre): static
    {
        $this->ownedBooks->removeElement($livre);

        return $this;
    }

    /**
     * @return Collection<int, Auteur>
     */
    public function getFavoriteAuthors(): Collection
    {
        return $this->favoriteAuthors;
    }

    public function addFavoriteAuthor(Auteur $auteur): static
    {
        if (!$this->favoriteAuthors->contains($auteur)) {
            $this->favoriteAuthors->add($auteur);
        }

        return $this;
    }

    public function removeFavoriteAuthor(Auteur $auteur): static
    {
        $this->favoriteAuthors->removeElement($auteur);

        return $this;
    }

    /**
     * @return Collection<int, ReadingProgress>
     */
    public function getReadingProgress(): Collection
    {
        return $this->readingProgress;
    }

    public function addReadingProgress(ReadingProgress $readingProgress): static
    {
        if (!$this->readingProgress->contains($readingProgress)) {
            $this->readingProgress->add($readingProgress);
            $readingProgress->setUser($this);
        }

        return $this;
    }

    public function removeReadingProgress(ReadingProgress $readingProgress): static
    {
        if ($this->readingProgress->removeElement($readingProgress)) {
            // set the owning side to null (unless already changed)
            if ($readingProgress->getUser() === $this) {
                $readingProgress->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->username ?? $this->email ?? 'User #' . $this->id;
    }

    public function getLastLoginFormatted(): string
    {
        return $this->lastLogin ? $this->lastLogin->format('d/m/Y H:i:s') : 'Never';
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
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): static
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setUser($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): static
    {
        if ($this->carts->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getUser() === $this) {
                $cart->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Get active cart or null
     */
    public function getActiveCart(): ?Cart
    {
        foreach ($this->carts as $cart) {
            return $cart; // For now, return first cart. In real app, might have logic for active cart
        }
        return null;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
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
            $loan->setUser($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getUser() === $this) {
                $loan->setUser(null);
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
            $reservation->setUser($this);
        }

        return $this;
    }

    public function removeReservation(BookReservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Get active loans
     */
    public function getActiveLoans(): Collection
    {
        return $this->loans->filter(function (Loan $loan) {
            return in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE]);
        });
    }

    /**
     * Get completed orders count
     */
    public function getCompletedOrdersCount(): int
    {
        return $this->orders->filter(function (Order $order) {
            return $order->getStatus() === Order::STATUS_DELIVERED;
        })->count();
    }

    /**
     * Get total spent (all orders with payment: pending, paid, processing, shipped, delivered)
     */
    public function getTotalSpent(): float
    {
        $total = 0.0;
        foreach ($this->orders as $order) {
            // Count all non-cancelled and non-refunded orders
            if (!in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED])) {
                $total += $order->getTotalAmountFloat();
            }
        }
        return $total;
    }

    /**
     * Get total orders count (all non-cancelled orders)
     */
    public function getTotalOrdersCount(): int
    {
        return $this->orders->filter(function (Order $order) {
            return $order->getStatus() !== Order::STATUS_CANCELLED;
        })->count();
    }

    /**
     * Get books from completed orders (all non-cancelled orders, not just delivered)
     * @return Collection<int, Livre>
     */
    public function getPurchasedBooks(): Collection
    {
        $purchasedBooks = new ArrayCollection();

        foreach ($this->orders as $order) {
            // Include all orders except cancelled ones
            if ($order->getStatus() !== Order::STATUS_CANCELLED) {
                foreach ($order->getOrderItems() as $orderItem) {
                    $book = $orderItem->getLivre();
                    if ($book && !$purchasedBooks->contains($book)) {
                        $purchasedBooks->add($book);
                    }
                }
            }
        }

        return $purchasedBooks;
    }

    /**
     * @return Collection<int, ReadingGoal>
     */
    public function getReadingGoals(): Collection
    {
        return $this->readingGoals;
    }

    public function addReadingGoal(ReadingGoal $readingGoal): static
    {
        if (!$this->readingGoals->contains($readingGoal)) {
            $this->readingGoals->add($readingGoal);
            $readingGoal->setUser($this);
        }

        return $this;
    }

    public function removeReadingGoal(ReadingGoal $readingGoal): static
    {
        if ($this->readingGoals->removeElement($readingGoal)) {
            // set the owning side to null (unless already changed)
            if ($readingGoal->getUser() === $this) {
                $readingGoal->setUser(null);
            }
        }

        return $this;
    }

    public function getBannerPreferences(): Collection
    {
        return $this->bannerPreferences;
    }

    public function addBannerPreference(UserBannerPreference $bannerPreference): static
    {
        if (!$this->bannerPreferences->contains($bannerPreference)) {
            $this->bannerPreferences->add($bannerPreference);
            $bannerPreference->setUser($this);
        }

        return $this;
    }

    public function removeBannerPreference(UserBannerPreference $bannerPreference): static
    {
        if ($this->bannerPreferences->removeElement($bannerPreference)) {
            if ($bannerPreference->getUser() === $this) {
                $bannerPreference->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBillingAddress(): ?array
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?array $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }
}

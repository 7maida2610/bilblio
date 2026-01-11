<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_items')]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $quantity = 1;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $addedAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    /**
     * Get unit price
     */
    public function getUnitPrice(): float
    {
        return $this->livre ? $this->livre->getPrix() : 0.0;
    }

    /**
     * Get subtotal
     */
    public function getSubtotal(): float
    {
        return $this->getUnitPrice() * $this->quantity;
    }

    /**
     * Get subtotal formatted
     */
    public function getSubtotalFormatted(): string
    {
        return number_format($this->getSubtotal(), 2, ',', ' ') . ' €';
    }

    /**
     * Check if item is available in stock (using stock for sale)
     */
    public function isAvailable(): bool
    {
        return $this->livre && $this->livre->getStockVente() >= $this->quantity;
    }

    /**
     * Get available stock for sale
     */
    public function getAvailableStock(): int
    {
        return $this->livre ? $this->livre->getStockVente() : 0;
    }
}
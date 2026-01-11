<?php

namespace App\Repository;

use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Find cart item by cart and livre
     */
    public function findByCartAndLivre(int $cartId, int $livreId): ?CartItem
    {
        return $this->findOneBy([
            'cart' => $cartId,
            'livre' => $livreId
        ]);
    }
}
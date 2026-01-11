<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Find active cart for user
     */
    public function findActiveCartForUser(User $user): ?Cart
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Get or create cart for user
     */
    public function getOrCreateCartForUser(User $user): Cart
    {
        $cart = $this->findActiveCartForUser($user);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->_em->persist($cart);
            $this->_em->flush();
        }

        return $cart;
    }

    /**
     * Get cart with items
     */
    public function findCartWithItems(int $cartId): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cartItems', 'ci')
            ->leftJoin('ci.livre', 'l')
            ->addSelect('ci', 'l')
            ->where('c.id = :id')
            ->setParameter('id', $cartId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get cart with items for user
     */
    public function findCartWithItemsForUser(User $user): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cartItems', 'ci')
            ->leftJoin('ci.livre', 'l')
            ->addSelect('ci', 'l')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Clean up empty carts
     */
    public function removeEmptyCarts(): int
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cartItems', 'ci')
            ->having('COUNT(ci.id) = 0')
            ->groupBy('c.id')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
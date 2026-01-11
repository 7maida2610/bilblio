<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_cart_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, Livre $livre): Response
    {
        $user = $this->getUser();

        // Get or create cart
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
        }

        // Check if item already exists in cart
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getLivre() === $livre) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            // Increase quantity
            $newQuantity = $existingItem->getQuantity() + 1;
            if ($newQuantity <= $livre->getStockVente()) {
                $existingItem->setQuantity($newQuantity);
                $this->addFlash('success', 'Quantité mise à jour dans le panier.');
            } else {
                $this->addFlash('warning', 'Stock vente insuffisant.');
            }
        } else {
            // Add new item
            if ($livre->getStockVente() > 0) {
                $cartItem = new CartItem();
                $cartItem->setCart($cart);
                $cartItem->setLivre($livre);
                $cartItem->setQuantity(1);

                $cart->addCartItem($cartItem);
                $this->entityManager->persist($cartItem);
                $this->addFlash('success', 'Livre ajouté au panier.');
            } else {
                $this->addFlash('warning', 'Ce livre n\'est plus en stock.');
            }
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Redirect back to previous page or livre show
        $referer = $request->headers->get('referer');
        if ($referer && !str_contains($referer, '/cart/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartItem $cartItem): Response
    {
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $quantity = (int) $request->request->get('quantity', 1);
        $quantity = max(1, min($quantity, $cartItem->getAvailableStock()));

        $cartItem->setQuantity($quantity);
        $cartItem->getCart()->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', 'Quantité mise à jour.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem): Response
    {
        if ($cartItem->getCart()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $cart = $cartItem->getCart();
        $cart->removeCartItem($cartItem);
        $cart->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();

        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(): Response
    {
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $this->entityManager->remove($item);
            }
            $this->entityManager->flush();
            $this->addFlash('success', 'Panier vidé.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/count', name: 'app_cart_count', methods: ['GET'])]
    public function getCount(): Response
    {
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        $count = $cart ? $cart->getTotalItems() : 0;

        return $this->json(['count' => $count]);
    }
}
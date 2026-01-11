<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Service\StripePaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout')]
#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripePaymentService $stripeService,
        private ParameterBagInterface $parameterBag
    ) {}

    #[Route('', name: 'app_checkout')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Check stock availability
        $hasUnavailableItems = false;
        foreach ($cart->getCartItems() as $item) {
            if (!$item->isAvailable()) {
                $hasUnavailableItems = true;
                break;
            }
        }

        // Get billing information from user profile
        $billingInfo = [
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
        ];

        // Add billing address from user profile if available
        if ($user->getBillingAddress()) {
            $billingInfo = array_merge($billingInfo, $user->getBillingAddress());
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'hasUnavailableItems' => $hasUnavailableItems,
            'stripe_publishable_key' => $this->parameterBag->get('stripe_publishable_key'),
            'billingInfo' => $billingInfo,
        ]);
    }

    #[Route('/create-payment-intent', name: 'app_checkout_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): Response
    {
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        // Get payment method from request (supports both JSON and form data)
        $paymentMethod = $request->request->get('payment_method') 
            ?? ($request->toArray()['payment_method'] ?? Order::PAYMENT_METHOD_STRIPE);

        // Create order from cart (but don't flush yet)
        $order = $this->createOrderFromCart($cart);
        $order->setPaymentMethod($paymentMethod);

        // If payment method is not Stripe, create order and redirect
        if ($paymentMethod !== Order::PAYMENT_METHOD_STRIPE) {
            $order->setStatus(Order::STATUS_PENDING);
            $this->entityManager->flush(); // NOW flush

            // Update stock
            $this->updateStock($order);

            // Clear cart
            $this->entityManager->remove($cart);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'orderId' => $order->getId(),
                'paymentMethod' => $paymentMethod,
                'redirect' => $this->generateUrl('app_order_show', ['id' => $order->getId()]),
            ]);
        }

        // Create Stripe PaymentIntent for Stripe payments only
        $result = $this->stripeService->createPaymentIntent($order);

        if ($result['success']) {
            $order->setStripePaymentIntentId($result['payment_intent_id']);
            $this->entityManager->flush(); // NOW flush

            return $this->json([
                'clientSecret' => $result['client_secret'],
                'orderId' => $order->getId(),
            ]);
        }

        return $this->json(['error' => $result['error']], 400);
    }

    #[Route('/manual-payment', name: 'app_checkout_manual_payment', methods: ['POST'])]
    public function manualPayment(Request $request): Response
    {
        $user = $this->getUser();
        $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return $this->json(['error' => 'Panier vide'], 400);
        }

        $paymentMethod = $request->request->get('payment_method', Order::PAYMENT_METHOD_MANUAL);

        // Create order from cart
        $order = $this->createOrderFromCart($cart);
        $order->setPaymentMethod($paymentMethod);
        $order->setStatus(Order::STATUS_PENDING);

        // Update stock
        $this->updateStock($order);

        // Clear cart
        $this->entityManager->remove($cart);
        $this->entityManager->flush();

        $this->addFlash('success', 'Commande creee avec succes. Merci pour votre achat !');
        return $this->json([
            'success' => true,
            'orderId' => $order->getId(),
            'redirect' => $this->generateUrl('app_order_show', ['id' => $order->getId()]),
        ]);
    }

    #[Route('/success', name: 'app_checkout_success')]
    public function success(Request $request): Response
    {
        $paymentIntentId = $request->query->get('payment_intent');

        if (!$paymentIntentId) {
            $this->addFlash('error', 'Paiement non trouvé.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Verify payment with Stripe
        $paymentResult = $this->stripeService->confirmPaymentIntent($paymentIntentId);

        if ($paymentResult['success'] && $paymentResult['status'] === 'succeeded') {
            // Find and update order
            $order = $this->entityManager->getRepository(Order::class)->findOneBy([
                'stripePaymentIntentId' => $paymentIntentId
            ]);

            if ($order) {
                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());

                // Clear user's cart
                $cart = $this->entityManager->getRepository(Cart::class)->findOneBy(['user' => $this->getUser()]);
                if ($cart) {
                    $this->entityManager->remove($cart);
                }

                // Update stock
                $this->updateStock($order);

                $this->entityManager->flush();

                $this->addFlash('success', 'Paiement réussi ! Votre commande a été confirmée.');
                return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
            }
        }

        $this->addFlash('error', 'Erreur lors de la vérification du paiement.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/cancel', name: 'app_checkout_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('info', 'Paiement annulé.');
        return $this->redirectToRoute('app_cart_index');
    }

    private function createOrderFromCart(Cart $cart): Order
    {
        $order = new Order();
        $order->setUser($cart->getUser());
        $order->setTotalAmount($cart->getTotalPrice());
        $order->setStatus(Order::STATUS_PENDING);

        // Create order items from cart items
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setLivre($cartItem->getLivre());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setUnitPrice($cartItem->getUnitPrice());
            $orderItem->setSubtotal($cartItem->getSubtotal());

            $order->addOrderItem($orderItem);
        }

        // Copy shipping and billing address from user profile if available
        if ($cart->getUser()->getShippingAddress()) {
            $order->setShippingAddress($cart->getUser()->getShippingAddress());
        }
        if ($cart->getUser()->getBillingAddress()) {
            $order->setBillingAddress($cart->getUser()->getBillingAddress());
        }

        $this->entityManager->persist($order);
        // DO NOT FLUSH HERE - let the caller decide when to flush

        return $order;
    }

    private function updateStock(Order $order): void
    {
        foreach ($order->getOrderItems() as $item) {
            $livre = $item->getLivre();
            // Decrement stockVente for sales, not stockEmprunt
            $newStock = $livre->getStockVente() - $item->getQuantity();
            $livre->setStockVente(max(0, $newStock));
            // Also update total nbExemplaires for backwards compatibility
            $livre->setNbExemplaires($livre->getStockVente() + $livre->getStockEmprunt());
        }
    }
}
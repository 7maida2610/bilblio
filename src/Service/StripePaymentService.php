<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
        $this->stripe = new StripeClient($this->parameterBag->get('stripe_secret_key'));
    }

    /**
     * Create a PaymentIntent for an order
     */
    public function createPaymentIntent(Order $order): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int)($order->getTotalAmountFloat() * 100), // Amount in cents
                'currency' => strtolower($order->getCurrency()),
                'metadata' => [
                    'order_number' => $order->getOrderNumber(),
                    'order_id' => $order->getId(),
                    'user_id' => $order->getUser()->getId(),
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm a payment intent
     */
    public function confirmPaymentIntent(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a refund
     */
    public function createRefund(string $paymentIntentId, ?int $amount = null): array
    {
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount) {
                $refundData['amount'] = $amount;
            }

            $refund = $this->stripe->refunds->create($refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment intent details
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'created' => $paymentIntent->created,
                'description' => $paymentIntent->description,
                'metadata' => $paymentIntent->metadata,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create checkout session (alternative approach)
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        try {
            $lineItems = [];
            foreach ($order->getOrderItems() as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => strtolower($order->getCurrency()),
                        'product_data' => [
                            'name' => $item->getLivre()->getTitre(),
                            'description' => 'Auteur: ' . ($item->getLivre()->getAuteur() ? $item->getLivre()->getAuteur()->getPrenom() . ' ' . $item->getLivre()->getAuteur()->getNom() : 'N/A'),
                        ],
                        'unit_amount' => (int)($item->getUnitPriceFloat() * 100),
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            }

            $checkoutSession = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'order_number' => $order->getOrderNumber(),
                    'order_id' => $order->getId(),
                    'user_id' => $order->getUser()->getId(),
                ],
            ]);

            return [
                'success' => true,
                'session_id' => $checkoutSession->id,
                'url' => $checkoutSession->url,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle webhook events
     */
    public function handleWebhookEvent(array $eventData): array
    {
        try {
            switch ($eventData['type']) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $eventData['data']['object'];
                    return [
                        'type' => 'payment_succeeded',
                        'payment_intent_id' => $paymentIntent['id'],
                        'amount' => $paymentIntent['amount'],
                        'metadata' => $paymentIntent['metadata'] ?? [],
                    ];

                case 'payment_intent.payment_failed':
                    $paymentIntent = $eventData['data']['object'];
                    return [
                        'type' => 'payment_failed',
                        'payment_intent_id' => $paymentIntent['id'],
                        'error' => $paymentIntent['last_payment_error'] ?? null,
                        'metadata' => $paymentIntent['metadata'] ?? [],
                    ];

                default:
                    return [
                        'type' => 'unknown',
                        'event_type' => $eventData['type'],
                    ];
            }
        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
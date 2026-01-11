<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class OrderAdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    #[Route('', name: 'app_admin_orders_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        $orderRepository = $this->entityManager->getRepository(Order::class);
        
        // Get total count
        $total = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get paginated orders
        $orders = $orderRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        $totalPages = ceil($total / $limit);
        
        return $this->render('admin/order/list.html.twig', [
            'orders' => $orders,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/mark-paid', name: 'app_admin_order_mark_paid', methods: ['POST'])]
    public function markAsPaid(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('mark-paid-' . $order->getId(), $request->request->get('_token'))) {
            if ($order->getStatus() === Order::STATUS_PENDING) {
                $order->setStatus(Order::STATUS_PAID);
                $order->setPaidAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                // Send status update notification
                try {
                    $this->emailService->sendOrderStatusUpdateEmail($order);
                } catch (\Exception $e) {
                    // Log error but don't break the flow
                    error_log('Failed to send order status update email: ' . $e->getMessage());
                }

                $this->addFlash('success', 'La commande a été marquée comme payée.');
            }
        }

        return $this->redirectToRoute('admin', [
            'entity' => 'Order',
            'action' => 'index'
        ]);
    }

    #[Route('/{id}/mark-shipped', name: 'app_admin_order_mark_shipped', methods: ['POST'])]
    public function markAsShipped(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('mark-shipped-' . $order->getId(), $request->request->get('_token'))) {
            if (in_array($order->getStatus(), [Order::STATUS_PAID, Order::STATUS_PROCESSING])) {
                $order->setStatus(Order::STATUS_SHIPPED);
                $order->setShippedAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                // Send shipped notification
                try {
                    $this->emailService->sendOrderShippedEmail($order);
                } catch (\Exception $e) {
                    // Log error but don't break the flow
                    error_log('Failed to send order shipped email: ' . $e->getMessage());
                }

                $this->addFlash('success', 'La commande a été marquée comme expédiée.');
            }
        }

        return $this->redirectToRoute('admin', [
            'entity' => 'Order',
            'action' => 'index'
        ]);
    }

    #[Route('/{id}/mark-delivered', name: 'app_admin_order_mark_delivered', methods: ['POST'])]
    public function markAsDelivered(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('mark-delivered-' . $order->getId(), $request->request->get('_token'))) {
            if ($order->getStatus() === Order::STATUS_SHIPPED) {
                $order->setStatus(Order::STATUS_DELIVERED);
                $order->setDeliveredAt(new \DateTimeImmutable());
                $this->entityManager->flush();

                // Send delivered notification
                try {
                    $this->emailService->sendOrderDeliveredEmail($order);
                } catch (\Exception $e) {
                    // Log error but don't break the flow
                    error_log('Failed to send order delivered email: ' . $e->getMessage());
                }

                $this->addFlash('success', 'La commande a été marquée comme livrée.');
            }
        }

        return $this->redirectToRoute('admin', [
            'entity' => 'Order',
            'action' => 'index'
        ]);
    }

    #[Route('/{id}/change-status', name: 'app_admin_order_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Order $order): Response
    {
        $newStatus = $request->request->get('status');
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ];

        if (!in_array($newStatus, $validStatuses)) {
            return $this->json(['error' => 'Status invalide'], 400);
        }

        $oldStatus = $order->getStatus();
        $order->setStatus($newStatus);

        // Set timestamps based on status
        $now = new \DateTimeImmutable();
        match($newStatus) {
            Order::STATUS_PAID => $order->setPaidAt($now),
            Order::STATUS_SHIPPED => $order->setShippedAt($now),
            Order::STATUS_DELIVERED => $order->setDeliveredAt($now),
            default => null
        };

        $this->entityManager->flush();

        // Send appropriate email notification
        try {
            match($newStatus) {
                Order::STATUS_PAID => $this->emailService->sendOrderStatusUpdateEmail($order),
                Order::STATUS_SHIPPED => $this->emailService->sendOrderShippedEmail($order),
                Order::STATUS_DELIVERED => $this->emailService->sendOrderDeliveredEmail($order),
                default => null
            };
        } catch (\Exception $e) {
            error_log('Failed to send order status email: ' . $e->getMessage());
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'status' => $newStatus,
                'statusLabel' => $order->getStatusLabel(),
                'message' => "Statut changé de $oldStatus à $newStatus"
            ]);
        }

        $this->addFlash('success', 'Statut de la commande modifié avec succès.');
        return $this->redirectToRoute('app_admin_order_show', ['id' => $order->getId()]);
    }
}
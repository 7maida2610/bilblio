<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/borrowing-settings')]
#[IsGranted('ROLE_ADMIN')]
class BorrowingSettingsController extends AbstractController
{
    #[Route('', name: 'app_borrowing_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request): Response
    {
        // Get current borrowing messages with defaults
        $currentMessages = [
            'available' => 'Disponible',
            'unavailable' => 'Indisponible',
            'borrow_now' => 'Emprunter maintenant',
            'reserve_later' => 'Réserver pour plus tard',
        ];

        if ($request->isMethod('POST')) {
            // Handle form submission - in a real app, you'd save to database
            // For now, we'll just show a success message
            $this->addFlash('success', 'Paramètres de emprunt mis à jour avec succès.');
            return $this->redirectToRoute('app_borrowing_settings');
        }

        return $this->render('admin/borrowing_settings.html.twig', [
            'messages' => $currentMessages,
        ]);
    }
}
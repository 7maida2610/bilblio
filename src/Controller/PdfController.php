<?php

namespace App\Controller;

use App\Entity\Livre;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/pdf')]
#[IsGranted('ROLE_USER')]
class PdfController extends AbstractController
{
    #[Route('/view/{id}', name: 'app_pdf_view', methods: ['GET'])]
    public function view(Livre $livre): Response
    {
        // Check if user has access to this PDF
        if (!$this->canAccessPdf($livre)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce PDF. Vous devez emprunter le livre ou en être le propriétaire.');
        }

        // Check if PDF file exists
        if (!$livre->getPdf()) {
            throw $this->createNotFoundException('PDF non trouvé pour ce livre.');
        }

        $pdfPath = $livre->getPdf();

        // If it's a Cloudinary URL, redirect to it
        if (str_starts_with($pdfPath, 'http://') || str_starts_with($pdfPath, 'https://')) {
            return $this->redirect($pdfPath);
        }

        // Otherwise, it's a local file
        $localPdfPath = $this->getParameter('pdf_directory') . '/' . $pdfPath;

        if (!file_exists($localPdfPath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable.');
        }

        // Return PDF for inline viewing
        $response = new BinaryFileResponse($localPdfPath);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            basename($localPdfPath)
        );

        return $response;
    }

    #[Route('/download/{id}', name: 'app_pdf_download', methods: ['GET'])]
    public function download(Livre $livre): Response
    {
        // Check if user has access to this PDF
        if (!$this->canAccessPdf($livre)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce PDF. Vous devez emprunter le livre ou en être le propriétaire.');
        }

        // Check if PDF file exists
        if (!$livre->getPdf()) {
            throw $this->createNotFoundException('PDF non trouvé pour ce livre.');
        }

        $pdfPath = $livre->getPdf();

        // If it's a Cloudinary URL, redirect to it
        if (str_starts_with($pdfPath, 'http://') || str_starts_with($pdfPath, 'https://')) {
            return $this->redirect($pdfPath);
        }

        // Otherwise, it's a local file
        $localPdfPath = $this->getParameter('pdf_directory') . '/' . $pdfPath;

        if (!file_exists($localPdfPath)) {
            throw $this->createNotFoundException('Fichier PDF introuvable.');
        }

        // Return PDF for download
        $response = new BinaryFileResponse($localPdfPath);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $livre->getTitre() . '.pdf'
        );

        return $response;
    }

    private function canAccessPdf(Livre $livre): bool
    {
        $user = $this->getUser();

        // Admin has access to all PDFs
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Check if user owns the book
        if ($livre->getCreatedBy() === $user) {
            return true;
        }

        // Check if user has an active loan for this book
        foreach ($livre->getLoans() as $loan) {
            if ($loan->getUser() === $user &&
                in_array($loan->getStatus(), ['active', 'overdue'])) {
                return true;
            }
        }

        return false;
    }
}
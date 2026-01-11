<?php

namespace App\Controller;

use App\Entity\Livre;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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

        // If it's a Cloudinary URL, proxy it to allow iframe embedding
        if (str_starts_with($pdfPath, 'http://') || str_starts_with($pdfPath, 'https://')) {
            try {
                // Use HttpClient if available, otherwise fallback to curl
                if ($this->httpClient) {
                    $response = $this->httpClient->request('GET', $pdfPath, [
                        'timeout' => 30,
                        'max_redirects' => 5,
                    ]);
                    $pdfContent = $response->getContent();
                } else {
                    // Fallback to curl
                    $ch = curl_init($pdfPath);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    $pdfContent = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode !== 200 || !$pdfContent || $error) {
                        throw new \RuntimeException("Failed to fetch PDF: HTTP $httpCode - $error");
                    }
                }

                // Return PDF with proper headers for iframe embedding
                $response = new Response($pdfContent);
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'inline; filename="' . basename(parse_url($pdfPath, PHP_URL_PATH)) . '"');
                // Allow PDF to be displayed in iframe
                $response->headers->remove('X-Frame-Options');
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                
                return $response;
            } catch (\Exception $e) {
                throw $this->createNotFoundException('Impossible de charger le PDF: ' . $e->getMessage());
            }
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
        
        // Allow PDF to be displayed in iframe (remove X-Frame-Options)
        $response->headers->remove('X-Frame-Options');
        // Allow content from same origin to be embedded
        $response->headers->set('X-Content-Type-Options', 'nosniff');

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
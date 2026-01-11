<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service pour gérer les uploads vers Cloudinary
 * Remplace les uploads locaux pour la production Railway
 */
class CloudinaryService
{
    private Cloudinary $cloudinary;
    private LoggerInterface $logger;
    private bool $enabled;

    public function __construct(LoggerInterface $logger, ?string $cloudinaryUrl = null)
    {
        $this->logger = $logger;
        $this->enabled = !empty($cloudinaryUrl);

        if ($this->enabled && $cloudinaryUrl) {
            Configuration::instance($cloudinaryUrl);
            $this->cloudinary = new Cloudinary();
        }
    }

    /**
     * Upload une image vers Cloudinary
     *
     * @param UploadedFile $file Fichier uploadé
     * @param string $folder Dossier Cloudinary (ex: 'biblio/images', 'biblio/pdfs')
     * @param array $options Options Cloudinary (transformation, etc.)
     * @return string|null URL publique du fichier uploadé
     */
    public function uploadImage(UploadedFile $file, string $folder = 'biblio/images', array $options = []): ?string
    {
        if (!$this->enabled) {
            $this->logger->warning('Cloudinary is not configured, upload skipped');
            return null;
        }

        try {
            $options = array_merge([
                'folder' => $folder,
                'resource_type' => 'image',
                'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-' . uniqid(),
            ], $options);

            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $options
            );

            return $result['secure_url'] ?? $result['url'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Cloudinary upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
            ]);
            return null;
        }
    }

    /**
     * Upload un PDF vers Cloudinary
     *
     * @param UploadedFile $file Fichier PDF
     * @param string $folder Dossier Cloudinary
     * @return string|null URL publique du PDF
     */
    public function uploadPdf(UploadedFile $file, string $folder = 'biblio/pdfs'): ?string
    {
        if (!$this->enabled) {
            $this->logger->warning('Cloudinary is not configured, PDF upload skipped');
            return null;
        }

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'folder' => $folder,
                    'resource_type' => 'raw',
                    'public_id' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '-' . uniqid(),
                ]
            );

            return $result['secure_url'] ?? $result['url'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Cloudinary PDF upload failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'folder' => $folder,
            ]);
            return null;
        }
    }

    /**
     * Supprime un fichier de Cloudinary
     *
     * @param string $publicId ID public du fichier (sans extension)
     * @param string $resourceType Type de ressource ('image' ou 'raw')
     * @return bool
     */
    public function deleteFile(string $publicId, string $resourceType = 'image'): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cloudinary delete failed: ' . $e->getMessage(), [
                'public_id' => $publicId,
            ]);
            return false;
        }
    }

    /**
     * Vérifie si Cloudinary est configuré
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Génère une URL avec transformations Cloudinary
     *
     * @param string $publicId ID public du fichier
     * @param array $transformations Transformations à appliquer
     * @param string $resourceType Type de ressource
     * @return string URL avec transformations
     */
    public function url(string $publicId, array $transformations = [], string $resourceType = 'image'): string
    {
        if (!$this->enabled) {
            return '';
        }

        try {
            return $this->cloudinary->image($publicId)->toUrl($transformations);
        } catch (\Exception $e) {
            $this->logger->error('Cloudinary URL generation failed: ' . $e->getMessage());
            return '';
        }
    }
}

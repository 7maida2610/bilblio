<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('image_url', [$this, 'getImageUrl']),
            new TwigFunction('pdf_url', [$this, 'getPdfUrl']),
            new TwigFunction('profile_picture_url', [$this, 'getProfilePictureUrl']),
            new TwigFunction('author_image_url', [$this, 'getAuthorImageUrl']),
        ];
    }

    /**
     * Returns the image URL - handles both Cloudinary URLs and local file paths
     */
    public function getImageUrl(?string $imagePath, string $localPath = 'uploads/images/'): string
    {
        if (!$imagePath) {
            return '';
        }

        // If it's already a full URL (Cloudinary), return it directly
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        // Otherwise, it's a local file path
        return '/' . $localPath . $imagePath;
    }

    /**
     * Returns the PDF URL - handles both Cloudinary URLs and local file paths
     */
    public function getPdfUrl(?string $pdfPath, string $localPath = 'uploads/pdfs/'): string
    {
        if (!$pdfPath) {
            return '';
        }

        // If it's already a full URL (Cloudinary), return it directly
        if (str_starts_with($pdfPath, 'http://') || str_starts_with($pdfPath, 'https://')) {
            return $pdfPath;
        }

        // Otherwise, it's a local file path
        return '/' . $localPath . $pdfPath;
    }

    /**
     * Returns profile picture URL - handles both Cloudinary URLs and local file paths
     */
    public function getProfilePictureUrl(?string $imagePath): string
    {
        return $this->getImageUrl($imagePath, 'uploads/profile_pictures/');
    }

    /**
     * Returns author image URL - handles both Cloudinary URLs and local file paths
     */
    public function getAuthorImageUrl(?string $imagePath): string
    {
        return $this->getImageUrl($imagePath, 'uploads/auteur/');
    }
}

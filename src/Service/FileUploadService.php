<?php

declare(strict_types=1);

namespace App\Service;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service for handling file uploads with Flysystem.
 *
 * Provides secure file upload handling with validation, sanitization,
 * and support for multiple storage backends via Flysystem.
 */
class FileUploadService
{
    // Allowed MIME types for uploads
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
    ];

    private const ALLOWED_ARCHIVE_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ];

    // Maximum file size (in bytes) - 10MB default
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function __construct(
        private readonly FilesystemOperator $uploadsStorage,
        private readonly FilesystemOperator $documentsStorage,
        private readonly SluggerInterface $slugger,
    ) {
    }

    /**
     * Upload a file to the public uploads directory.
     *
     * @param UploadedFile $file The uploaded file
     * @param string|null $subdirectory Optional subdirectory within uploads (e.g., 'images', 'avatars')
     * @param bool $preserveOriginalName Whether to preserve the original filename
     *
     * @return string The path to the uploaded file (relative to storage root)
     *
     * @throws FileException If the upload fails
     */
    public function uploadPublicFile(
        UploadedFile $file,
        ?string $subdirectory = null,
        bool $preserveOriginalName = false,
    ): string {
        $this->validateFile($file);

        $fileName = $this->generateSafeFilename($file, $preserveOriginalName);

        if ($subdirectory !== null) {
            $subdirectory = trim($subdirectory, '/');
            $fileName = $subdirectory . '/' . $fileName;
        }

        try {
            $stream = fopen($file->getPathname(), 'r');
            if ($stream === false) {
                throw new FileException('Could not read uploaded file');
            }

            $this->uploadsStorage->writeStream($fileName, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $fileName;
        } catch (\Exception $e) {
            throw new FileException('Could not upload file: ' . $e->getMessage());
        }
    }

    /**
     * Upload a file to the private documents directory.
     *
     * @param UploadedFile $file The uploaded file
     * @param string|null $subdirectory Optional subdirectory
     * @param bool $preserveOriginalName Whether to preserve the original filename
     *
     * @return string The path to the uploaded file
     *
     * @throws FileException If the upload fails
     */
    public function uploadPrivateFile(
        UploadedFile $file,
        ?string $subdirectory = null,
        bool $preserveOriginalName = false,
    ): string {
        $this->validateFile($file);

        $fileName = $this->generateSafeFilename($file, $preserveOriginalName);

        if ($subdirectory !== null) {
            $subdirectory = trim($subdirectory, '/');
            $fileName = $subdirectory . '/' . $fileName;
        }

        try {
            $stream = fopen($file->getPathname(), 'r');
            if ($stream === false) {
                throw new FileException('Could not read uploaded file');
            }

            $this->documentsStorage->writeStream($fileName, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $fileName;
        } catch (\Exception $e) {
            throw new FileException('Could not upload file: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from public uploads.
     *
     * @param string $path The file path to delete
     *
     * @return bool True if deleted, false if not found
     */
    public function deletePublicFile(string $path): bool
    {
        try {
            if ($this->uploadsStorage->fileExists($path)) {
                $this->uploadsStorage->delete($path);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a file from private documents.
     *
     * @param string $path The file path to delete
     *
     * @return bool True if deleted, false if not found
     */
    public function deletePrivateFile(string $path): bool
    {
        try {
            if ($this->documentsStorage->fileExists($path)) {
                $this->documentsStorage->delete($path);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the public URL for an uploaded file.
     *
     * @param string $path The file path
     *
     * @return string The public URL
     */
    public function getPublicUrl(string $path): string
    {
        return '/uploads/' . ltrim($path, '/');
    }

    /**
     * Check if a file exists in public uploads.
     *
     * @param string $path The file path
     *
     * @return bool True if exists, false otherwise
     */
    public function fileExists(string $path): bool
    {
        try {
            return $this->uploadsStorage->fileExists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get allowed MIME types for validation.
     *
     * @return array<string> Array of allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return array_merge(
            self::ALLOWED_IMAGE_TYPES,
            self::ALLOWED_DOCUMENT_TYPES,
            self::ALLOWED_ARCHIVE_TYPES,
        );
    }

    /**
     * Check if a MIME type is allowed for images.
     *
     * @param string $mimeType The MIME type to check
     *
     * @return bool True if allowed for images
     */
    public function isImageType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true);
    }

    /**
     * Check if a MIME type is allowed for documents.
     *
     * @param string $mimeType The MIME type to check
     *
     * @return bool True if allowed for documents
     */
    public function isDocumentType(string $mimeType): bool
    {
        return in_array($mimeType, self::ALLOWED_DOCUMENT_TYPES, true);
    }

    /**
     * Validate an uploaded file.
     *
     * @param UploadedFile $file The file to validate
     *
     * @throws FileException If validation fails
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new FileException(sprintf('File size (%s) exceeds maximum allowed size (%s)', $this->formatBytes($file->getSize()), $this->formatBytes(self::MAX_FILE_SIZE)));
        }

        // Additional security check: ensure file is actually uploaded
        if (!$file->isValid()) {
            throw new FileException('Invalid file upload');
        }

        // Get allowed MIME types
        $allowedTypes = array_merge(
            self::ALLOWED_IMAGE_TYPES,
            self::ALLOWED_DOCUMENT_TYPES,
            self::ALLOWED_ARCHIVE_TYPES,
        );

        // Check client-reported MIME type
        $clientMimeType = $file->getMimeType();
        if ($clientMimeType === null || !in_array($clientMimeType, $allowedTypes, true)) {
            throw new FileException(sprintf('File type "%s" is not allowed. Allowed types: %s', $clientMimeType ?? 'unknown', implode(', ', $allowedTypes)));
        }

        // SECURITY: Verify actual file content matches claimed MIME type
        // This prevents MIME type spoofing attacks
        $actualMimeType = $this->detectActualMimeType($file->getPathname());
        if ($actualMimeType !== null && !in_array($actualMimeType, $allowedTypes, true)) {
            throw new FileException(sprintf('File content does not match allowed types. Detected: %s', $actualMimeType));
        }

        // Additional check for image files - verify they're actually valid images
        if (in_array($clientMimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            if (!$this->isValidImageFile($file->getPathname())) {
                throw new FileException('File is not a valid image');
            }
        }
    }

    /**
     * Generate a safe filename for storage.
     *
     * @param UploadedFile $file The uploaded file
     * @param bool $preserveOriginalName Whether to preserve the original filename
     *
     * @return string The safe filename
     */
    private function generateSafeFilename(UploadedFile $file, bool $preserveOriginalName): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->guessExtension();

        $safeFilename = $this->slugger->slug($originalFilename)->lower()->toString();

        if (!$preserveOriginalName) {
            // Generate unique filename with timestamp
            $safeFilename .= '-' . uniqid('', true);
        }

        return $safeFilename . '.' . $extension;
    }

    /**
     * Detect actual MIME type by reading file content.
     * Uses finfo to analyze file magic bytes.
     *
     * @param string $filePath Path to the file
     *
     * @return string|null The detected MIME type, or null on failure
     */
    private function detectActualMimeType(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        return $mimeType !== false ? $mimeType : null;
    }

    /**
     * Verify that a file is actually a valid image.
     * Prevents malicious files disguised as images.
     *
     * @param string $filePath Path to the file
     *
     * @return bool True if the file is a valid image
     */
    private function isValidImageFile(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        // getimagesize returns false for non-image files
        $imageInfo = @getimagesize($filePath);

        if ($imageInfo === false) {
            return false;
        }

        // Verify the image type is one we accept
        $validImageTypes = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG,
            IMAGETYPE_GIF,
            IMAGETYPE_WEBP,
        ];

        return in_array($imageInfo[2], $validImageTypes, true);
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes The number of bytes
     *
     * @return string Formatted string (e.g., "2.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

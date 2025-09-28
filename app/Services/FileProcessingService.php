<?php

namespace App\Services;

use App\Utilities\LoggingUtil;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaraUtilX\Traits\FileProcessingTrait;
use LaraUtilX\Utilities\CachingUtil;

class FileProcessingService
{
    use FileProcessingTrait;

    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/json',
    ];

    protected array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'txt', 'json',
    ];

    protected int $maxFileSize = 10 * 1024 * 1024; // 10MB

    /**
     * Upload a file with validation and processing
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', array $options = []): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateUniqueFilename($file);
            $path = $directory.'/'.$filename;

            // Store file
            $storedPath = $file->storeAs($directory, $filename, 'public');

            // Get file info
            $fileInfo = [
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'path' => $storedPath,
                'url' => Storage::url($storedPath),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'uploaded_at' => now()->toDateTimeString(),
            ];

            // Process image if it's an image
            if ($this->isImage($file)) {
                $fileInfo = array_merge($fileInfo, $this->processImage($file, $storedPath, $options));
            }

            // Cache file info
            $cacheKey = "file_info_{$filename}";
            CachingUtil::remember($cacheKey, now()->addDays(30), function () use ($fileInfo) {
                return $fileInfo;
            });

            LoggingUtil::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $storedPath,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ], 'file_processing');

            return [
                'success' => true,
                'data' => $fileInfo,
                'message' => 'File uploaded successfully.',
            ];

        } catch (\Exception $e) {
            LoggingUtil::error('File upload failed', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ], 'file_processing');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'File upload failed.',
            ];
        }
    }

    /**
     * Process multiple files
     */
    public function uploadMultipleFiles(array $files, string $directory = 'uploads', array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($files as $file) {
            $result = $this->uploadFile($file, $directory, $options);
            $results[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        LoggingUtil::info('Multiple files upload completed', [
            'total_files' => count($files),
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ], 'file_processing');

        return [
            'success' => $errorCount === 0,
            'data' => $results,
            'summary' => [
                'total_files' => count($files),
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ],
            'message' => "Uploaded {$successCount} files successfully.".($errorCount > 0 ? " {$errorCount} files failed." : ''),
        ];
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $filePath): array
    {
        try {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);

                // Clear cache
                $filename = basename($filePath);
                CachingUtil::forget("file_info_{$filename}");

                LoggingUtil::info('File deleted successfully', [
                    'file_path' => $filePath,
                ], 'file_processing');

                return [
                    'success' => true,
                    'message' => 'File deleted successfully.',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'message' => 'File not found.',
                ];
            }

        } catch (\Exception $e) {
            LoggingUtil::error('File deletion failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ], 'file_processing');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'File deletion failed.',
            ];
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $filePath): array
    {
        try {
            if (! Storage::exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found',
                    'message' => 'File not found.',
                ];
            }

            $filename = basename($filePath);
            $cacheKey = "file_info_{$filename}";

            // Try to get from cache first
            $fileInfo = CachingUtil::get($cacheKey);

            if (! $fileInfo) {
                // Generate file info
                $fileInfo = [
                    'filename' => $filename,
                    'path' => $filePath,
                    'url' => Storage::url($filePath),
                    'size' => Storage::size($filePath),
                    'mime_type' => Storage::mimeType($filePath),
                    'last_modified' => Storage::lastModified($filePath),
                    'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
                ];

                // Cache for 30 days
                CachingUtil::remember($cacheKey, now()->addDays(30), function () use ($fileInfo) {
                    return $fileInfo;
                });
            }

            return [
                'success' => true,
                'data' => $fileInfo,
                'message' => 'File information retrieved successfully.',
            ];

        } catch (\Exception $e) {
            LoggingUtil::error('Failed to get file information', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ], 'file_processing');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get file information.',
            ];
        }
    }

    /**
     * List files in directory
     */
    public function listFiles(string $directory = 'uploads', array $options = []): array
    {
        try {
            $cacheKey = "files_list_{$directory}_".md5(serialize($options));

            $files = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () use ($directory, $options) {
                $files = Storage::files($directory);

                // Apply filters
                if (isset($options['extension'])) {
                    $extension = $options['extension'];
                    $files = array_filter($files, function ($file) use ($extension) {
                        return pathinfo($file, PATHINFO_EXTENSION) === $extension;
                    });
                }

                if (isset($options['mime_type'])) {
                    $mimeType = $options['mime_type'];
                    $files = array_filter($files, function ($file) use ($mimeType) {
                        return Storage::mimeType($file) === $mimeType;
                    });
                }

                // Get file info for each file
                return array_map(function ($file) {
                    return [
                        'filename' => basename($file),
                        'path' => $file,
                        'url' => Storage::url($file),
                        'size' => Storage::size($file),
                        'mime_type' => Storage::mimeType($file),
                        'last_modified' => Storage::lastModified($file),
                        'extension' => pathinfo($file, PATHINFO_EXTENSION),
                    ];
                }, $files);
            });

            return [
                'success' => true,
                'data' => $files,
                'count' => count($files),
                'message' => 'Files listed successfully.',
            ];

        } catch (\Exception $e) {
            LoggingUtil::error('Failed to list files', [
                'error' => $e->getMessage(),
                'directory' => $directory,
            ], 'file_processing');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to list files.',
            ];
        }
    }

    /**
     * Validate file
     *
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed size of '.($this->maxFileSize / 1024 / 1024).'MB.');
        }

        // Check MIME type
        if (! in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \Exception('File type not allowed. Allowed types: '.implode(', ', $this->allowedMimeTypes));
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $this->allowedExtensions)) {
            throw new \Exception('File extension not allowed. Allowed extensions: '.implode(', ', $this->allowedExtensions));
        }

        // Check if file is valid
        if (! $file->isValid()) {
            throw new \Exception('Invalid file upload.');
        }
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = str_random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image
     */
    protected function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Process image file
     */
    protected function processImage(UploadedFile $file, string $storedPath, array $options = []): array
    {
        try {
            // Get image dimensions
            $imagePath = Storage::path($storedPath);
            $imageInfo = getimagesize($imagePath);

            $imageData = [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
                'aspect_ratio' => null,
            ];

            if ($imageData['width'] && $imageData['height']) {
                $imageData['aspect_ratio'] = round($imageData['width'] / $imageData['height'], 2);
            }

            // Create thumbnails if requested
            if (isset($options['create_thumbnails']) && $options['create_thumbnails']) {
                $thumbnailSizes = $options['thumbnail_sizes'] ?? [
                    ['width' => 150, 'height' => 150],
                    ['width' => 300, 'height' => 300],
                ];

                $thumbnails = [];
                foreach ($thumbnailSizes as $size) {
                    $thumbnailPath = $this->createThumbnail($imagePath, $size['width'], $size['height']);
                    if ($thumbnailPath) {
                        $thumbnails[] = [
                            'size' => $size,
                            'path' => $thumbnailPath,
                            'url' => Storage::url($thumbnailPath),
                        ];
                    }
                }

                $imageData['thumbnails'] = $thumbnails;
            }

            return $imageData;

        } catch (\Exception $e) {
            LoggingUtil::warning('Image processing failed', [
                'error' => $e->getMessage(),
                'file_path' => $storedPath,
            ], 'file_processing');

            return [];
        }
    }

    /**
     * Create thumbnail
     */
    protected function createThumbnail(string $imagePath, int $width, int $height): ?string
    {
        try {
            // This is a simplified thumbnail creation
            // In production, you'd want to use a proper image processing library like Intervention Image

            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'].'/thumbnails/'.$pathInfo['filename']."_{$width}x{$height}.".$pathInfo['extension'];

            // Create thumbnail directory if it doesn't exist
            $thumbnailDir = dirname($thumbnailPath);
            if (! is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // For now, just copy the original file
            // In production, implement proper image resizing
            copy($imagePath, $thumbnailPath);

            return str_replace(Storage::path(''), '', $thumbnailPath);

        } catch (\Exception $e) {
            LoggingUtil::warning('Thumbnail creation failed', [
                'error' => $e->getMessage(),
                'image_path' => $imagePath,
                'width' => $width,
                'height' => $height,
            ], 'file_processing');

            return null;
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        try {
            $cacheKey = 'storage_statistics';

            $stats = CachingUtil::remember($cacheKey, now()->addMinutes(30), function () {
                $directories = ['uploads', 'thumbnails', 'temp'];
                $totalFiles = 0;
                $totalSize = 0;
                $filesByType = [];

                foreach ($directories as $directory) {
                    $files = Storage::files($directory);
                    $totalFiles += count($files);

                    foreach ($files as $file) {
                        $size = Storage::size($file);
                        $totalSize += $size;

                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $filesByType[$extension] = ($filesByType[$extension] ?? 0) + 1;
                    }
                }

                return [
                    'total_files' => $totalFiles,
                    'total_size' => $totalSize,
                    'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                    'files_by_type' => $filesByType,
                    'directories' => $directories,
                ];
            });

            return [
                'success' => true,
                'data' => $stats,
                'message' => 'Storage statistics retrieved successfully.',
            ];

        } catch (\Exception $e) {
            LoggingUtil::error('Failed to get storage statistics', [
                'error' => $e->getMessage(),
            ], 'file_processing');

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to get storage statistics.',
            ];
        }
    }
}

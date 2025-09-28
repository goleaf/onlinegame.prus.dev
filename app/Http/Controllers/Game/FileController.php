<?php

namespace App\Http\Controllers\Game;

use App\Services\FileProcessingService;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\FileProcessingTrait;
use LaraUtilX\Utilities\RateLimiterUtil;

/**
 * @group File Management
 *
 * API endpoints for file uploads, downloads, and management.
 * Supports image processing, thumbnails, and file validation.
 *
 * @authenticated
 *
 * @tag File System
 * @tag Uploads
 * @tag File Processing
 */
class FileController extends CrudController
{
    use ApiResponseTrait;
    use FileProcessingTrait;
    use ValidationHelperTrait;

    protected FileProcessingService $fileProcessingService;

    protected RateLimiterUtil $rateLimiter;

    protected array $validationRules = [];

    protected function getValidationRules(): array
    {
        return [
            'file' => 'required|file|max:10240',  // 10MB max
            'directory' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function __construct(FileProcessingService $fileProcessingService, RateLimiterUtil $rateLimiter)
    {
        $this->fileProcessingService = $fileProcessingService;
        $this->rateLimiter = $rateLimiter;
        $this->validationRules = $this->getValidationRules();
        parent::__construct(null);  // FileController doesn't use a specific model
    }

    /**
     * Upload a single file
     *
     * @authenticated
     *
     * @description Upload a file with validation and processing.
     *
     * @bodyParam file file required The file to upload. Example: image.jpg
     * @bodyParam directory string The directory to store the file. Example: "uploads/game"
     * @bodyParam description string Optional description for the file. Example: "Game screenshot"
     * @bodyParam create_thumbnails boolean Whether to create thumbnails for images. Example: true
     * @bodyParam thumbnail_sizes array Array of thumbnail sizes. Example: [{"width": 150, "height": 150}]
     *
     * @response 201 {
     *   "success": true,
     *   "message": "File uploaded successfully",
     *   "data": {
     *     "original_name": "image.jpg",
     *     "filename": "2023-01-01_12-00-00_abc12345.jpg",
     *     "path": "uploads/2023-01-01_12-00-00_abc12345.jpg",
     *     "url": "https://example.com/storage/uploads/2023-01-01_12-00-00_abc12345.jpg",
     *     "size": 1024000,
     *     "mime_type": "image/jpeg",
     *     "extension": "jpg",
     *     "uploaded_at": "2023-01-01T12:00:00.000000Z",
     *     "width": 1920,
     *     "height": 1080,
     *     "aspect_ratio": 1.78
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "file": ["The file field is required."]
     *   }
     * }
     *
     * @tag File System
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // Rate limiting for file uploads
            $rateLimitKey = 'file_upload_'.(auth()->id() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
                return $this->errorResponse('Too many file uploads. Please try again later.', 429);
            }

            $validated = $this->validateRequestData($request, $this->validationRules);

            $file = $request->file('file');
            $directory = $validated['directory'] ?? 'uploads';

            $options = [];
            if ($request->has('create_thumbnails')) {
                $options['create_thumbnails'] = $request->boolean('create_thumbnails');
            }
            if ($request->has('thumbnail_sizes')) {
                $options['thumbnail_sizes'] = $request->input('thumbnail_sizes');
            }

            $result = $this->fileProcessingService->uploadFile($file, $directory, $options);

            if ($result['success']) {
                LoggingUtil::info('File uploaded via API', [
                    'user_id' => auth()->id(),
                    'filename' => $result['data']['filename'],
                    'size' => $result['data']['size'],
                    'directory' => $directory,
                ], 'file_management');

                return $this->successResponse($result['data'], $result['message'], 201);
            } else {
                return $this->errorResponse($result['error'], 422);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('File upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('File upload failed.', 500);
        }
    }

    /**
     * Upload multiple files
     *
     * @authenticated
     *
     * @description Upload multiple files at once with validation and processing.
     *
     * @bodyParam files[] file required Array of files to upload.
     * @bodyParam directory string The directory to store the files. Example: "uploads/batch"
     * @bodyParam create_thumbnails boolean Whether to create thumbnails for images. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Uploaded 3 files successfully.",
     *   "data": [
     *     {
     *       "success": true,
     *       "data": {...}
     *     }
     *   ],
     *   "summary": {
     *     "total_files": 3,
     *     "success_count": 3,
     *     "error_count": 0
     *   }
     * }
     *
     * @tag File System
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            // Rate limiting for multiple file uploads
            $rateLimitKey = 'multiple_file_upload_'.(auth()->id() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 3, 1)) {
                return $this->errorResponse('Too many multiple file uploads. Please try again later.', 429);
            }

            $validated = $this->validateRequestData($request, [
                'files.*' => 'required|file|max:10240',
                'directory' => 'nullable|string|max:255',
                'create_thumbnails' => 'nullable|boolean',
            ]);

            $files = $request->file('files');
            $directory = $validated['directory'] ?? 'uploads/batch';

            $options = [];
            if ($request->has('create_thumbnails')) {
                $options['create_thumbnails'] = $request->boolean('create_thumbnails');
            }

            $result = $this->fileProcessingService->uploadMultipleFiles($files, $directory, $options);

            LoggingUtil::info('Multiple files uploaded via API', [
                'user_id' => auth()->id(),
                'total_files' => $result['summary']['total_files'],
                'success_count' => $result['summary']['success_count'],
                'error_count' => $result['summary']['error_count'],
                'directory' => $directory,
            ], 'file_management');

            return $this->successResponse($result, $result['message'], 201);
        } catch (\Exception $e) {
            LoggingUtil::error('Multiple file upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('Multiple file upload failed.', 500);
        }
    }

    /**
     * Get file information
     *
     * @authenticated
     *
     * @description Get detailed information about a specific file.
     *
     * @urlParam filePath string required The path to the file. Example: "uploads/image.jpg"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "File information retrieved successfully",
     *   "data": {
     *     "filename": "image.jpg",
     *     "path": "uploads/image.jpg",
     *     "url": "https://example.com/storage/uploads/image.jpg",
     *     "size": 1024000,
     *     "mime_type": "image/jpeg",
     *     "last_modified": 1672531200,
     *     "extension": "jpg"
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "File not found"
     * }
     *
     * @tag File System
     */
    public function info(string $filePath): JsonResponse
    {
        try {
            $result = $this->fileProcessingService->getFileInfo($filePath);

            if ($result['success']) {
                LoggingUtil::info('File information retrieved', [
                    'user_id' => auth()->id(),
                    'file_path' => $filePath,
                ], 'file_management');

                return $this->successResponse($result['data'], $result['message']);
            } else {
                return $this->errorResponse($result['error'], 404);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving file information', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('Failed to retrieve file information.', 500);
        }
    }

    /**
     * List files in directory
     *
     * @authenticated
     *
     * @description List all files in a directory with optional filtering.
     *
     * @queryParam directory string The directory to list files from. Example: "uploads"
     * @queryParam extension string Filter by file extension. Example: "jpg"
     * @queryParam mime_type string Filter by MIME type. Example: "image/jpeg"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Files listed successfully",
     *   "data": [
     *     {
     *       "filename": "image.jpg",
     *       "path": "uploads/image.jpg",
     *       "url": "https://example.com/storage/uploads/image.jpg",
     *       "size": 1024000,
     *       "mime_type": "image/jpeg",
     *       "last_modified": 1672531200,
     *       "extension": "jpg"
     *     }
     *   ],
     *   "count": 1
     * }
     *
     * @tag File System
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $directory = $request->get('directory', 'uploads');
            $options = [];

            if ($request->has('extension')) {
                $options['extension'] = $request->get('extension');
            }

            if ($request->has('mime_type')) {
                $options['mime_type'] = $request->get('mime_type');
            }

            $result = $this->fileProcessingService->listFiles($directory, $options);

            LoggingUtil::info('Files listed', [
                'user_id' => auth()->id(),
                'directory' => $directory,
                'options' => $options,
                'file_count' => $result['count'] ?? 0,
            ], 'file_management');

            return $this->successResponse($result['data'], $result['message'], 200, [
                'count' => $result['count'],
            ]);
        } catch (\Exception $e) {
            LoggingUtil::error('Error listing files', [
                'error' => $e->getMessage(),
                'directory' => $request->get('directory', 'uploads'),
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('Failed to list files.', 500);
        }
    }

    /**
     * Delete a file
     *
     * @authenticated
     *
     * @description Delete a specific file from storage.
     *
     * @urlParam filePath string required The path to the file to delete. Example: "uploads/image.jpg"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "File deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "File not found"
     * }
     *
     * @tag File System
     */
    public function delete(string $filePath): JsonResponse
    {
        try {
            $result = $this->fileProcessingService->deleteFile($filePath);

            if ($result['success']) {
                LoggingUtil::info('File deleted via API', [
                    'user_id' => auth()->id(),
                    'file_path' => $filePath,
                ], 'file_management');

                return $this->successResponse(null, $result['message']);
            } else {
                return $this->errorResponse($result['error'], 404);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('File deletion error', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('File deletion failed.', 500);
        }
    }

    /**
     * Get storage statistics
     *
     * @authenticated
     *
     * @description Get comprehensive storage statistics including file counts and sizes.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Storage statistics retrieved successfully",
     *   "data": {
     *     "total_files": 150,
     *     "total_size": 52428800,
     *     "total_size_mb": 50.0,
     *     "files_by_type": {
     *       "jpg": 75,
     *       "png": 50,
     *       "pdf": 25
     *     },
     *     "directories": ["uploads", "thumbnails", "temp"]
     *   }
     * }
     *
     * @tag File System
     */
    public function stats(): JsonResponse
    {
        try {
            $result = $this->fileProcessingService->getStorageStats();

            if ($result['success']) {
                LoggingUtil::info('Storage statistics retrieved', [
                    'user_id' => auth()->id(),
                    'total_files' => $result['data']['total_files'],
                    'total_size_mb' => $result['data']['total_size_mb'],
                ], 'file_management');

                return $this->successResponse($result['data'], $result['message']);
            } else {
                return $this->errorResponse($result['error'], 500);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving storage statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'file_management');

            return $this->errorResponse('Failed to retrieve storage statistics.', 500);
        }
    }

    /**
     * Download a file
     *
     * @authenticated
     *
     * @description Download a file from storage.
     *
     * @urlParam filePath string required The path to the file to download. Example: "uploads/image.jpg"
     *
     * @response 200 Binary file content
     * @response 404 {
     *   "success": false,
     *   "message": "File not found"
     * }
     *
     * @tag File System
     */
    public function download(string $filePath): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            if (! \Storage::exists($filePath)) {
                LoggingUtil::warning('File download attempted for non-existent file', [
                    'user_id' => auth()->id(),
                    'file_path' => $filePath,
                ], 'file_management');

                abort(404, 'File not found');
            }

            LoggingUtil::info('File downloaded', [
                'user_id' => auth()->id(),
                'file_path' => $filePath,
            ], 'file_management');

            return \Storage::download($filePath);
        } catch (\Exception $e) {
            LoggingUtil::error('File download error', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'user_id' => auth()->id(),
            ], 'file_management');

            abort(500, 'File download failed');
        }
    }
}

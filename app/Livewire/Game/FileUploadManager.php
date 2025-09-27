<?php

namespace App\Livewire\Game;

use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\FileProcessingTrait;
use Livewire\Component;
use Livewire\WithFileUploads;

class FileUploadManager extends Component
{
    use WithFileUploads, FileProcessingTrait, ApiResponseTrait;

    public $player;
    public $uploadedFiles = [];
    public $isUploading = false;
    public $uploadProgress = 0;
    public $notifications = [];
    // File upload properties
    public $avatar;
    public $screenshots = [];
    public $documents = [];
    public $maxFileSize = 5120;  // 5MB in KB
    public $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];

    protected $listeners = [
        'fileUploaded',
        'fileDeleted',
        'uploadProgress',
    ];

    public function mount()
    {
        $this->player = Auth::user()->player;
        $this->loadUploadedFiles();
    }

    public function loadUploadedFiles()
    {
        // Load existing files for the player
        $this->uploadedFiles = [
            'avatar' => $this->getFile('avatar.jpg', 'player_uploads'),
            'screenshots' => $this->getFile('screenshots.json', 'player_uploads'),
            'documents' => $this->getFile('documents.json', 'player_uploads'),
        ];
    }

    public function uploadAvatar()
    {
        $this->validate([
            'avatar' => 'required|image|max:' . $this->maxFileSize,
        ]);

        $this->isUploading = true;
        $this->uploadProgress = 0;

        try {
            // Use FileProcessingTrait for file upload
            $filename = $this->uploadFile($this->avatar, 'player_uploads');

            // Store file info in player data
            $this->player->update([
                'avatar_path' => $filename,
            ]);

            $this->uploadProgress = 100;
            $this->addNotification('Avatar uploaded successfully!', 'success');
            $this->dispatch('fileUploaded', ['type' => 'avatar', 'filename' => $filename]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to upload avatar: ' . $e->getMessage(), 'error');
        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    public function uploadScreenshots()
    {
        $this->validate([
            'screenshots.*' => 'required|image|max:' . $this->maxFileSize,
        ]);

        $this->isUploading = true;
        $this->uploadProgress = 0;

        try {
            // Use FileProcessingTrait for multiple file upload
            $filenames = $this->uploadFiles($this->screenshots, 'player_uploads/screenshots');

            // Store screenshot info
            $screenshots = json_decode($this->getFile('screenshots.json', 'player_uploads') ?? '[]', true);
            $screenshots = array_merge($screenshots, $filenames);

            // Save updated screenshots list
            \Storage::put('player_uploads/screenshots.json', json_encode($screenshots));

            $this->uploadProgress = 100;
            $this->addNotification('Screenshots uploaded successfully!', 'success');
            $this->dispatch('fileUploaded', ['type' => 'screenshots', 'filenames' => $filenames]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to upload screenshots: ' . $e->getMessage(), 'error');
        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    public function uploadDocuments()
    {
        $this->validate([
            'documents.*' => 'required|file|max:' . $this->maxFileSize,
        ]);

        $this->isUploading = true;
        $this->uploadProgress = 0;

        try {
            // Use FileProcessingTrait for multiple file upload
            $filenames = $this->uploadFiles($this->documents, 'player_uploads/documents');

            // Store document info
            $documents = json_decode($this->getFile('documents.json', 'player_uploads') ?? '[]', true);
            $documents = array_merge($documents, $filenames);

            // Save updated documents list
            \Storage::put('player_uploads/documents.json', json_encode($documents));

            $this->uploadProgress = 100;
            $this->addNotification('Documents uploaded successfully!', 'success');
            $this->dispatch('fileUploaded', ['type' => 'documents', 'filenames' => $filenames]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to upload documents: ' . $e->getMessage(), 'error');
        } finally {
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    public function deleteFile($filename, $type)
    {
        try {
            // Use FileProcessingTrait for file deletion
            $this->deleteFile($filename, "player_uploads/{$type}");

            // Update file lists
            if ($type === 'screenshots') {
                $screenshots = json_decode($this->getFile('screenshots.json', 'player_uploads') ?? '[]', true);
                $screenshots = array_filter($screenshots, fn($f) => $f !== $filename);
                \Storage::put('player_uploads/screenshots.json', json_encode($screenshots));
            } elseif ($type === 'documents') {
                $documents = json_decode($this->getFile('documents.json', 'player_uploads') ?? '[]', true);
                $documents = array_filter($documents, fn($f) => $f !== $filename);
                \Storage::put('player_uploads/documents.json', json_encode($documents));
            }

            $this->addNotification('File deleted successfully!', 'success');
            $this->dispatch('fileDeleted', ['type' => $type, 'filename' => $filename]);
            $this->loadUploadedFiles();
        } catch (\Exception $e) {
            $this->addNotification('Failed to delete file: ' . $e->getMessage(), 'error');
        }
    }

    public function deleteAllFiles($type)
    {
        try {
            $files = json_decode($this->getFile("{$type}.json", 'player_uploads') ?? '[]', true);

            // Use FileProcessingTrait for multiple file deletion
            $this->deleteFiles($files, "player_uploads/{$type}");

            // Clear file list
            \Storage::put("player_uploads/{$type}.json", json_encode([]));

            $this->addNotification("All {$type} deleted successfully!", 'success');
            $this->dispatch('fileDeleted', ['type' => $type, 'all' => true]);
            $this->loadUploadedFiles();
        } catch (\Exception $e) {
            $this->addNotification('Failed to delete files: ' . $e->getMessage(), 'error');
        }
    }

    public function getFileUrl($filename, $type)
    {
        return \Storage::url("player_uploads/{$type}/{$filename}");
    }

    public function getFileSize($filename, $type)
    {
        $path = "player_uploads/{$type}/{$filename}";
        if (\Storage::exists($path)) {
            return \Storage::size($path);
        }
        return 0;
    }

    public function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function render()
    {
        return view('livewire.game.file-upload-manager', [
            'player' => $this->player,
            'uploadedFiles' => $this->uploadedFiles,
            'isUploading' => $this->isUploading,
            'uploadProgress' => $this->uploadProgress,
            'notifications' => $this->notifications,
        ]);
    }
}

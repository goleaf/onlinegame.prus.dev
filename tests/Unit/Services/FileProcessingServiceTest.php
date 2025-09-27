<?php

namespace Tests\Unit\Services;

use App\Services\FileProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FileProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FileProcessingService();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_valid_file()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('File uploaded successfully.', $result['message']);

        $fileData = $result['data'];
        $this->assertEquals('test-image.jpg', $fileData['original_name']);
        $this->assertEquals('image/jpeg', $fileData['mime_type']);
        $this->assertEquals('jpg', $fileData['extension']);
        $this->assertArrayHasKey('width', $fileData);
        $this->assertArrayHasKey('height', $fileData);
        $this->assertArrayHasKey('aspect_ratio', $fileData);

        // Check file was stored
        Storage::disk('public')->assertExists($fileData['path']);
    }

    /** @test */
    public function it_validates_file_size()
    {
        $file = UploadedFile::fake()->create('large-file.pdf', 11000); // 11MB

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertFalse($result['success']);
        $this->assertStringContains('File size exceeds maximum', $result['error']);
    }

    /** @test */
    public function it_validates_file_mime_type()
    {
        $file = UploadedFile::fake()->create('document.exe', 1000, 'application/x-executable');

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertFalse($result['success']);
        $this->assertStringContains('File type not allowed', $result['error']);
    }

    /** @test */
    public function it_validates_file_extension()
    {
        $file = UploadedFile::fake()->create('document.exe', 1000);

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertFalse($result['success']);
        $this->assertStringContains('File extension not allowed', $result['error']);
    }

    /** @test */
    public function it_handles_invalid_file_upload()
    {
        $file = UploadedFile::fake()->create('invalid-file.exe', 1000);

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_upload_multiple_files()
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.png'),
            UploadedFile::fake()->create('document.pdf', 1000),
        ];

        $result = $this->service->uploadMultipleFiles($files, 'uploads/batch');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['summary']['total_files']);
        $this->assertEquals(3, $result['summary']['success_count']);
        $this->assertEquals(0, $result['summary']['error_count']);
        $this->assertCount(3, $result['data']);
    }

    /** @test */
    public function it_handles_mixed_success_failure_in_multiple_upload()
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg'), // Valid
            UploadedFile::fake()->create('invalid.exe', 1000), // Invalid
            UploadedFile::fake()->image('image2.png'), // Valid
        ];

        $result = $this->service->uploadMultipleFiles($files, 'uploads/batch');

        $this->assertFalse($result['success']); // Overall failure due to one invalid file
        $this->assertEquals(3, $result['summary']['total_files']);
        $this->assertEquals(2, $result['summary']['success_count']);
        $this->assertEquals(1, $result['summary']['error_count']);
        $this->assertCount(3, $result['data']);
    }

    /** @test */
    public function it_can_delete_existing_file()
    {
        // First upload a file
        $file = UploadedFile::fake()->image('test-image.jpg');
        $uploadResult = $this->service->uploadFile($file, 'uploads/test');
        
        $filePath = $uploadResult['data']['path'];

        // Then delete it
        $result = $this->service->deleteFile($filePath);

        $this->assertTrue($result['success']);
        $this->assertEquals('File deleted successfully.', $result['message']);

        // Check file was deleted
        Storage::disk('public')->assertMissing($filePath);
    }

    /** @test */
    public function it_handles_deleting_nonexistent_file()
    {
        $result = $this->service->deleteFile('uploads/nonexistent-file.jpg');

        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
        $this->assertEquals('File not found.', $result['message']);
    }

    /** @test */
    public function it_can_get_file_information()
    {
        // First upload a file
        $file = UploadedFile::fake()->image('test-image.jpg');
        $uploadResult = $this->service->uploadFile($file, 'uploads/test');
        
        $filePath = $uploadResult['data']['path'];

        // Then get file info
        $result = $this->service->getFileInfo($filePath);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('File information retrieved successfully.', $result['message']);

        $fileData = $result['data'];
        $this->assertEquals('test-image.jpg', $fileData['filename']);
        $this->assertEquals('image/jpeg', $fileData['mime_type']);
        $this->assertEquals('jpg', $fileData['extension']);
        $this->assertArrayHasKey('size', $fileData);
        $this->assertArrayHasKey('url', $fileData);
        $this->assertArrayHasKey('last_modified', $fileData);
    }

    /** @test */
    public function it_handles_getting_info_for_nonexistent_file()
    {
        $result = $this->service->getFileInfo('uploads/nonexistent-file.jpg');

        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
        $this->assertEquals('File not found.', $result['message']);
    }

    /** @test */
    public function it_can_list_files_in_directory()
    {
        // Upload some files
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file2 = UploadedFile::fake()->image('image2.png');
        $file3 = UploadedFile::fake()->create('document.pdf', 1000);

        $this->service->uploadFile($file1, 'uploads/test');
        $this->service->uploadFile($file2, 'uploads/test');
        $this->service->uploadFile($file3, 'uploads/test');

        $result = $this->service->listFiles('uploads/test');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['count']);
        $this->assertCount(3, $result['data']);
        $this->assertEquals('Files listed successfully.', $result['message']);

        // Check file data structure
        $fileData = $result['data'][0];
        $this->assertArrayHasKey('filename', $fileData);
        $this->assertArrayHasKey('path', $fileData);
        $this->assertArrayHasKey('url', $fileData);
        $this->assertArrayHasKey('size', $fileData);
        $this->assertArrayHasKey('mime_type', $fileData);
        $this->assertArrayHasKey('extension', $fileData);
    }

    /** @test */
    public function it_can_filter_files_by_extension()
    {
        // Upload files with different extensions
        $imageFile = UploadedFile::fake()->image('image.jpg');
        $pdfFile = UploadedFile::fake()->create('document.pdf', 1000);

        $this->service->uploadFile($imageFile, 'uploads/test');
        $this->service->uploadFile($pdfFile, 'uploads/test');

        $result = $this->service->listFiles('uploads/test', ['extension' => 'jpg']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('jpg', $result['data'][0]['extension']);
    }

    /** @test */
    public function it_can_filter_files_by_mime_type()
    {
        // Upload files with different MIME types
        $imageFile = UploadedFile::fake()->image('image.jpg');
        $pdfFile = UploadedFile::fake()->create('document.pdf', 1000);

        $this->service->uploadFile($imageFile, 'uploads/test');
        $this->service->uploadFile($pdfFile, 'uploads/test');

        $result = $this->service->listFiles('uploads/test', ['mime_type' => 'image/jpeg']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('image/jpeg', $result['data'][0]['mime_type']);
    }

    /** @test */
    public function it_can_get_storage_statistics()
    {
        // Upload some files
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file2 = UploadedFile::fake()->image('image2.png');
        $file3 = UploadedFile::fake()->create('document.pdf', 1000);

        $this->service->uploadFile($file1, 'uploads');
        $this->service->uploadFile($file2, 'uploads');
        $this->service->uploadFile($file3, 'uploads');

        $result = $this->service->getStorageStats();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('Storage statistics retrieved successfully.', $result['message']);

        $stats = $result['data'];
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_size_mb', $stats);
        $this->assertArrayHasKey('files_by_type', $stats);
        $this->assertArrayHasKey('directories', $stats);

        $this->assertGreaterThanOrEqual(3, $stats['total_files']);
        $this->assertIsFloat($stats['total_size_mb']);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $file1 = UploadedFile::fake()->image('test-image.jpg');
        $file2 = UploadedFile::fake()->image('test-image.jpg'); // Same original name

        $result1 = $this->service->uploadFile($file1, 'uploads/test');
        $result2 = $this->service->uploadFile($file2, 'uploads/test');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);

        // Filenames should be different even with same original name
        $this->assertNotEquals(
            $result1['data']['filename'],
            $result2['data']['filename']
        );

        // But original names should be the same
        $this->assertEquals(
            $result1['data']['original_name'],
            $result2['data']['original_name']
        );
    }

    /** @test */
    public function it_processes_image_files_correctly()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $result = $this->service->uploadFile($file, 'uploads/test');

        $this->assertTrue($result['success']);
        
        $fileData = $result['data'];
        $this->assertArrayHasKey('width', $fileData);
        $this->assertArrayHasKey('height', $fileData);
        $this->assertArrayHasKey('aspect_ratio', $fileData);
        
        // Aspect ratio should be calculated correctly
        $expectedRatio = round(1920 / 1080, 2);
        $this->assertEquals($expectedRatio, $fileData['aspect_ratio']);
    }

    /** @test */
    public function it_creates_thumbnails_when_requested()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $options = [
            'create_thumbnails' => true,
            'thumbnail_sizes' => [
                ['width' => 150, 'height' => 150],
                ['width' => 300, 'height' => 300],
            ],
        ];

        $result = $this->service->uploadFile($file, 'uploads/test', $options);

        $this->assertTrue($result['success']);
        
        $fileData = $result['data'];
        $this->assertArrayHasKey('thumbnails', $fileData);
        $this->assertCount(2, $fileData['thumbnails']);

        // Check thumbnail structure
        $thumbnail = $fileData['thumbnails'][0];
        $this->assertArrayHasKey('size', $thumbnail);
        $this->assertArrayHasKey('width', $thumbnail['size']);
        $this->assertArrayHasKey('height', $thumbnail['size']);
        $this->assertArrayHasKey('path', $thumbnail);
        $this->assertArrayHasKey('url', $thumbnail);
    }

    /** @test */
    public function it_handles_thumbnail_creation_failure_gracefully()
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');

        $options = [
            'create_thumbnails' => true,
        ];

        $result = $this->service->uploadFile($file, 'uploads/test', $options);

        $this->assertTrue($result['success']);
        
        $fileData = $result['data'];
        // PDF files shouldn't have thumbnails, but shouldn't fail
        $this->assertArrayNotHasKey('thumbnails', $fileData);
    }
}

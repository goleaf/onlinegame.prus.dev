<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Mock rate limiter
        $this->mock(RateLimiterUtil::class, function ($mock): void {
            $mock->shouldReceive('attempt')->andReturn(true);
        });

        // Fake storage
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_single_file()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
                'directory' => 'uploads/test',
                'description' => 'Test image upload',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'original_name',
                    'filename',
                    'path',
                    'url',
                    'size',
                    'mime_type',
                    'extension',
                    'uploaded_at',
                    'width',
                    'height',
                    'aspect_ratio',
                ],
            ]);

        $response->assertJsonPath('data.original_name', 'test-image.jpg');
        $response->assertJsonPath('data.mime_type', 'image/jpeg');
        $response->assertJsonPath('data.extension', 'jpg');

        // Check file was stored
        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    /** @test */
    public function it_validates_required_fields_for_upload()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_size()
    {
        $file = UploadedFile::fake()->create('large-file.pdf', 11000); // 11MB

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_type()
    {
        $file = UploadedFile::fake()->create('document.exe', 1000);

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_upload_multiple_files()
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.png'),
            UploadedFile::fake()->create('document.pdf', 1000),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload-multiple', [
                'files' => $files,
                'directory' => 'uploads/batch',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'success',
                        'data' => [
                            'original_name',
                            'filename',
                            'path',
                            'url',
                        ],
                    ],
                ],
                'summary' => [
                    'total_files',
                    'success_count',
                    'error_count',
                ],
            ]);

        $response->assertJsonPath('summary.total_files', 3);
        $response->assertJsonPath('summary.success_count', 3);
        $response->assertJsonPath('summary.error_count', 0);
    }

    /** @test */
    public function it_can_get_file_information()
    {
        // Upload a file first
        $file = UploadedFile::fake()->image('test-image.jpg');

        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
                'directory' => 'uploads',
            ]);

        $filePath = $uploadResponse->json('data.path');

        // Get file info
        $response = $this->actingAs($this->user)
            ->getJson("/game/api/files/info/{$filePath}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'filename',
                    'path',
                    'url',
                    'size',
                    'mime_type',
                    'last_modified',
                    'extension',
                ],
            ]);

        $response->assertJsonPath('data.filename', 'test-image.jpg');
        $response->assertJsonPath('data.mime_type', 'image/jpeg');
    }

    /** @test */
    public function it_returns_404_for_nonexistent_file_info()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/game/api/files/info/nonexistent-file.jpg');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'File not found.',
            ]);
    }

    /** @test */
    public function it_can_list_files_in_directory()
    {
        // Upload some files
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file2 = UploadedFile::fake()->image('image2.png');
        $file3 = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file1,
            'directory' => 'uploads/test',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file2,
            'directory' => 'uploads/test',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file3,
            'directory' => 'uploads/test',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/files/list?directory=uploads/test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'filename',
                        'path',
                        'url',
                        'size',
                        'mime_type',
                        'last_modified',
                        'extension',
                    ],
                ],
            ]);

        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_filter_files_by_extension()
    {
        // Upload files with different extensions
        $imageFile = UploadedFile::fake()->image('image.jpg');
        $pdfFile = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $imageFile,
            'directory' => 'uploads/test',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $pdfFile,
            'directory' => 'uploads/test',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/files/list?directory=uploads/test&extension=jpg');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.extension', 'jpg');
    }

    /** @test */
    public function it_can_filter_files_by_mime_type()
    {
        // Upload files with different MIME types
        $imageFile = UploadedFile::fake()->image('image.jpg');
        $pdfFile = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $imageFile,
            'directory' => 'uploads/test',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $pdfFile,
            'directory' => 'uploads/test',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/files/list?directory=uploads/test&mime_type=image/jpeg');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.mime_type', 'image/jpeg');
    }

    /** @test */
    public function it_can_delete_file()
    {
        // Upload a file first
        $file = UploadedFile::fake()->image('test-image.jpg');

        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
                'directory' => 'uploads',
            ]);

        $filePath = $uploadResponse->json('data.path');

        // Delete the file
        $response = $this->actingAs($this->user)
            ->deleteJson("/game/api/files/delete/{$filePath}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File deleted successfully.',
            ]);

        // Check file was deleted
        Storage::disk('public')->assertMissing($filePath);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_file()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/game/api/files/delete/nonexistent-file.jpg');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'File not found.',
            ]);
    }

    /** @test */
    public function it_can_get_storage_statistics()
    {
        // Upload some files
        $file1 = UploadedFile::fake()->image('image1.jpg');
        $file2 = UploadedFile::fake()->image('image2.png');
        $file3 = UploadedFile::fake()->create('document.pdf', 1000);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file1,
            'directory' => 'uploads',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file2,
            'directory' => 'uploads',
        ]);

        $this->actingAs($this->user)->postJson('/game/api/files/upload', [
            'file' => $file3,
            'directory' => 'uploads',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/files/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_files',
                    'total_size',
                    'total_size_mb',
                    'files_by_type',
                    'directories',
                ],
            ]);

        $response->assertJsonPath('data.total_files', 3);
        $response->assertArrayHasKey('files_by_type', $response->json('data'));
        $response->assertArrayHasKey('directories', $response->json('data'));
    }

    /** @test */
    public function it_can_download_file()
    {
        // Upload a file first
        $file = UploadedFile::fake()->create('test-document.txt', 100, 'text/plain');

        $uploadResponse = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
                'directory' => 'uploads',
            ]);

        $filePath = $uploadResponse->json('data.path');

        // Download the file
        $response = $this->actingAs($this->user)
            ->get("/game/api/files/download/{$filePath}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function it_returns_404_when_downloading_nonexistent_file()
    {
        $response = $this->actingAs($this->user)
            ->get('/game/api/files/download/nonexistent-file.jpg');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_thumbnails_for_images()
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 1920, 1080);

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
                'directory' => 'uploads',
                'create_thumbnails' => true,
                'thumbnail_sizes' => [
                    ['width' => 150, 'height' => 150],
                    ['width' => 300, 'height' => 300],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'thumbnails' => [
                        '*' => [
                            'size' => [
                                'width',
                                'height',
                            ],
                            'path',
                            'url',
                        ],
                    ],
                ],
            ]);

        $response->assertJsonCount(2, 'data.thumbnails');
    }

    /** @test */
    public function it_respects_rate_limiting()
    {
        // Mock rate limiter to return false
        $this->mock(RateLimiterUtil::class, function ($mock): void {
            $mock->shouldReceive('attempt')->andReturn(false);
        });

        $file = UploadedFile::fake()->image('test-image.jpg');

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many file uploads. Please try again later.',
            ]);
    }

    /** @test */
    public function it_validates_file_upload_parameters()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload', [
                'directory' => str_repeat('a', 256), // Too long
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['directory']);
    }

    /** @test */
    public function it_validates_multiple_file_upload_parameters()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/files/upload-multiple', [
                'files' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['files']);
    }
}

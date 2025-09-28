<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @test
     */
    public function it_can_upload_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
            'description' => 'Test image upload',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'file' => [
                    'id',
                    'filename',
                    'original_name',
                    'mime_type',
                    'size',
                    'path',
                    'url',
                    'directory',
                    'description',
                    'created_at',
                ],
            ]);

        Storage::disk('public')->assertExists('uploads/'.$file->hashName());
    }

    /**
     * @test
     */
    public function it_can_get_uploaded_files()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/files');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'filename',
                        'original_name',
                        'mime_type',
                        'size',
                        'path',
                        'url',
                        'directory',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $uploadResponse = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $fileId = $uploadResponse->json('file.id');

        $response = $this->actingAs($user)->get("/api/game/files/{$fileId}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'filename',
                'original_name',
                'mime_type',
                'size',
                'path',
                'url',
                'directory',
                'description',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_download_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $uploadResponse = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $fileId = $uploadResponse->json('file.id');

        $response = $this->actingAs($user)->get("/api/game/files/{$fileId}/download");

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    /**
     * @test
     */
    public function it_can_delete_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $uploadResponse = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $fileId = $uploadResponse->json('file.id');

        $response = $this->actingAs($user)->delete("/api/game/files/{$fileId}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_files_by_directory()
    {
        $user = User::factory()->create();
        $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file1,
            'directory' => 'uploads',
        ]);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file2,
            'directory' => 'documents',
        ]);

        $response = $this->actingAs($user)->get('/api/game/files?directory=uploads');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_files_by_type()
    {
        $user = User::factory()->create();
        $imageFile = UploadedFile::fake()->image('test.jpg', 100, 100);
        $pdfFile = UploadedFile::fake()->create('test.pdf', 100);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $imageFile,
            'directory' => 'uploads',
        ]);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $pdfFile,
            'directory' => 'uploads',
        ]);

        $response = $this->actingAs($user)->get('/api/game/files?type=image');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_search_files()
    {
        $user = User::factory()->create();
        $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file1,
            'directory' => 'uploads',
            'description' => 'Test image 1',
        ]);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file2,
            'directory' => 'uploads',
            'description' => 'Test image 2',
        ]);

        $response = $this->actingAs($user)->get('/api/game/files?search=Test image 1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_file_statistics()
    {
        $user = User::factory()->create();
        $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file1,
            'directory' => 'uploads',
        ]);

        $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file2,
            'directory' => 'uploads',
        ]);

        $response = $this->actingAs($user)->get('/api/game/files/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_files',
                'total_size',
                'files_by_type',
                'files_by_directory',
                'recent_uploads',
            ]);
    }

    /**
     * @test
     */
    public function it_can_bulk_upload_files()
    {
        $user = User::factory()->create();
        $files = [
            UploadedFile::fake()->image('test1.jpg', 100, 100),
            UploadedFile::fake()->image('test2.jpg', 100, 100),
        ];

        $response = $this->actingAs($user)->post('/api/game/files/bulk-upload', [
            'files' => $files,
            'directory' => 'uploads',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'uploaded_files' => [
                    '*' => [
                        'id',
                        'filename',
                        'original_name',
                        'mime_type',
                        'size',
                        'path',
                        'url',
                    ],
                ],
                'upload_count',
            ]);
    }

    /**
     * @test
     */
    public function it_can_bulk_delete_files()
    {
        $user = User::factory()->create();
        $file1 = UploadedFile::fake()->image('test1.jpg', 100, 100);
        $file2 = UploadedFile::fake()->image('test2.jpg', 100, 100);

        $uploadResponse1 = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file1,
            'directory' => 'uploads',
        ]);

        $uploadResponse2 = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file2,
            'directory' => 'uploads',
        ]);

        $fileIds = [
            $uploadResponse1->json('file.id'),
            $uploadResponse2->json('file.id'),
        ];

        $response = $this->actingAs($user)->post('/api/game/files/bulk-delete', [
            'file_ids' => $fileIds,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'deleted_count',
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_thumbnail()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $uploadResponse = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $fileId = $uploadResponse->json('file.id');

        $response = $this->actingAs($user)->post("/api/game/files/{$fileId}/thumbnail", [
            'width' => 50,
            'height' => 50,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'thumbnail' => [
                    'id',
                    'filename',
                    'path',
                    'url',
                    'width',
                    'height',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_file_metadata()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $uploadResponse = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $fileId = $uploadResponse->json('file.id');

        $response = $this->actingAs($user)->get("/api/game/files/{$fileId}/metadata");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'file',
                'metadata' => [
                    'dimensions',
                    'color_space',
                    'exif_data',
                    'file_info',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_validate_file()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user)->post('/api/game/files/validate', [
            'file' => $file,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'valid',
                'file_info' => [
                    'filename',
                    'mime_type',
                    'size',
                    'dimensions',
                ],
                'validation_results' => [
                    'size_valid',
                    'type_valid',
                    'dimensions_valid',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/files');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_file_upload()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/files/upload', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * @test
     */
    public function it_validates_file_size()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 11000);  // 11MB, exceeds 10MB limit

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * @test
     */
    public function it_validates_file_type()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.exe', 1000);  // Executable file

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * @test
     */
    public function it_validates_directory_name()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => str_repeat('a', 256),  // Exceeds max 255
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['directory']);
    }

    /**
     * @test
     */
    public function it_validates_description_length()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
            'description' => str_repeat('a', 501),  // Exceeds max 500
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_file()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/files/999');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_handles_file_processing_errors()
    {
        $user = User::factory()->create();

        // Mock file processing service to return an error
        $this->mock(\App\Services\FileProcessingService::class, function ($mock): void {
            $mock
                ->shouldReceive('processFile')
                ->andThrow(new \Exception('File processing failed'));
        });

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->actingAs($user)->post('/api/game/files/upload', [
            'file' => $file,
            'directory' => 'uploads',
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}

<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GenerateSitemap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateSitemapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_generate_sitemap()
    {
        Storage::fake('public');

        // Create test data
        DB::table('users')->insert([
            [
                'name' => 'User 1',
                'email' => 'user1@test.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@test.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this
            ->artisan('sitemap:generate')
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Static pages: 3')
            ->expectsOutput('Dynamic pages: 2')
            ->expectsOutput('Total URLs: 5')
            ->expectsOutput('Sitemap generated successfully!')
            ->expectsOutput('Sitemap saved to: sitemap.xml')
            ->assertExitCode(0);

        // Verify sitemap file was created
        $this->assertTrue(Storage::disk('public')->exists('sitemap.xml'));
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_custom_domain()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--domain' => 'https://example.com'])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Using custom domain: https://example.com')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);

        // Verify sitemap contains custom domain
        $sitemap = Storage::disk('public')->get('sitemap.xml');
        $this->assertStringContainsString('https://example.com', $sitemap);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_compression()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--compress' => true])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Enabling compression...')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->expectsOutput('Compressed sitemap saved to: sitemap.xml.gz')
            ->assertExitCode(0);

        // Verify compressed sitemap file was created
        $this->assertTrue(Storage::disk('public')->exists('sitemap.xml.gz'));
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_specific_sections()
    {
        Storage::fake('public');

        DB::table('users')->insert([
            [
                'name' => 'User 1',
                'email' => 'user1@test.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this
            ->artisan('sitemap:generate', ['--sections' => 'users,static'])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Including sections: users,static')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_priority_settings()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--priority-high' => '1.0', '--priority-medium' => '0.7', '--priority-low' => '0.3'])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Priority settings: High=1.0, Medium=0.7, Low=0.3')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_change_frequency()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--change-freq' => 'weekly'])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Change frequency: weekly')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_index()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--index' => true])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Generating sitemap index...')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap index generated successfully!')
            ->expectsOutput('Sitemap index saved to: sitemap_index.xml')
            ->assertExitCode(0);

        // Verify sitemap index file was created
        $this->assertTrue(Storage::disk('public')->exists('sitemap_index.xml'));
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_limit()
    {
        Storage::fake('public');

        // Create many test users
        for ($i = 1; $i <= 100; $i++) {
            DB::table('users')->insert([
                'name' => "User $i",
                'email' => "user$i@test.com",
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this
            ->artisan('sitemap:generate', ['--limit' => '50'])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Limiting to 50 URLs per sitemap')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_sitemap_generation_error()
    {
        Storage::fake('public');
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andThrow(new \Exception('Storage error'));

        $this
            ->artisan('sitemap:generate')
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Error generating sitemap: Storage error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_validate_sitemap_after_generation()
    {
        Storage::fake('public');

        $this
            ->artisan('sitemap:generate', ['--validate' => true])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Validating sitemap...')
            ->expectsOutput('Sitemap validation: PASSED')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_with_verbose_output()
    {
        Storage::fake('public');

        DB::table('users')->insert([
            [
                'name' => 'User 1',
                'email' => 'user1@test.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this
            ->artisan('sitemap:generate', ['--verbose' => true])
            ->expectsOutput('Generating sitemap...')
            ->expectsOutput('Processing static pages...')
            ->expectsOutput('Processing user pages...')
            ->expectsOutput('=== Sitemap Generation Report ===')
            ->expectsOutput('Sitemap generated successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GenerateSitemap();
        $this->assertEquals('sitemap:generate', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GenerateSitemap();
        $this->assertEquals('Generate XML sitemap for SEO', $command->getDescription());
    }
}

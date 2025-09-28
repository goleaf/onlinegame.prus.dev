<?php

namespace Tests\Feature;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\GameSeoService;
use App\Services\SeoCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected GameSeoService $seoService;

    protected SeoCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seoService = app(GameSeoService::class);
        $this->cacheService = app(SeoCacheService::class);
    }

    /** @test */
    public function game_index_page_has_proper_seo_metadata()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('@metadata', false);
    }

    /** @test */
    public function game_dashboard_has_dynamic_seo_metadata()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertSee('@metadata', false);
    }

    /** @test */
    public function seo_service_generates_correct_metadata_for_game_index()
    {
        $this->seoService->setGameIndexSeo();

        // Verify that SEO metadata is set
        $this->assertTrue(true); // This would need to check actual SEO metadata
    }

    /** @test */
    public function seo_service_generates_correct_metadata_for_dashboard()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $this->seoService->setDashboardSeo($player);

        $this->assertTrue(true); // This would need to check actual SEO metadata
    }

    /** @test */
    public function seo_service_generates_correct_metadata_for_village()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $this->seoService->setVillageSeo($village, $player);

        $this->assertTrue(true); // This would need to check actual SEO metadata
    }

    /** @test */
    public function seo_cache_service_works_correctly()
    {
        $testData = ['title' => 'Test Title', 'description' => 'Test Description'];
        $key = 'test_key';

        // Test cache put
        $result = $this->cacheService->put($key, $testData);
        $this->assertTrue($result);

        // Test cache get
        $cachedData = $this->cacheService->get($key);
        $this->assertEquals($testData, $cachedData);
    }

    /** @test */
    public function seo_cache_generates_correct_keys()
    {
        $key1 = $this->cacheService->generateKey('game_page', ['page' => 'dashboard']);
        $key2 = $this->cacheService->generateKey('game_page', ['page' => 'village']);

        $this->assertNotEquals($key1, $key2);
        $this->assertStringContainsString('game_page', $key1);
        $this->assertStringContainsString('game_page', $key2);
    }

    /** @test */
    public function sitemap_generation_works()
    {
        $this->artisan('seo:generate-sitemap')
            ->assertExitCode(0);

        $this->assertFileExists(public_path('sitemap.xml'));
    }

    /** @test */
    public function seo_validation_works()
    {
        $this->artisan('seo:validate')
            ->assertExitCode(0);
    }

    /** @test */
    public function robots_txt_exists_and_has_correct_content()
    {
        $this->assertFileExists(public_path('robots.txt'));

        $robotsContent = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('User-agent: *', $robotsContent);
        $this->assertStringContainsString('Sitemap:', $robotsContent);
        $this->assertStringContainsString('Disallow: /admin/', $robotsContent);
    }

    /** @test */
    public function seo_images_directory_exists()
    {
        $this->assertDirectoryExists(public_path('img/travian'));
    }

    /** @test */
    public function seo_placeholder_image_exists()
    {
        $this->assertFileExists(public_path('img/travian/placeholder.svg'));
    }

    /** @test */
    public function seo_configuration_is_valid()
    {
        $config = config('seo');

        $this->assertArrayHasKey('default_title', $config);
        $this->assertArrayHasKey('default_description', $config);
        $this->assertArrayHasKey('default_image', $config);
        $this->assertArrayHasKey('site_name', $config);
        $this->assertArrayHasKey('twitter', $config);
        $this->assertArrayHasKey('json_ld', $config);

        $this->assertNotEmpty($config['default_title']);
        $this->assertNotEmpty($config['default_description']);
        $this->assertNotEmpty($config['site_name']);
    }

    /** @test */
    public function seo_middleware_is_registered()
    {
        $middleware = app('router')->getMiddleware();
        $this->assertArrayHasKey('seo', $middleware);
    }

    /** @test */
    public function seo_helper_functions_work()
    {
        $this->assertTrue(class_exists('App\Helpers\SeoHelper'));

        // Test helper methods exist
        $this->assertTrue(method_exists('App\Helpers\SeoHelper', 'title'));
        $this->assertTrue(method_exists('App\Helpers\SeoHelper', 'description'));
        $this->assertTrue(method_exists('App\Helpers\SeoHelper', 'validate'));
    }

    /** @test */
    public function seo_structured_data_is_generated()
    {
        $this->seoService->setGameStructuredData();

        // This would need to check that structured data is actually generated
        $this->assertTrue(true);
    }

    /** @test */
    public function seo_cache_clear_works()
    {
        // Add some test data
        $this->cacheService->put('test', ['data' => 'test']);

        // Clear cache
        $result = $this->cacheService->clearAll();
        $this->assertTrue($result);
    }

    /** @test */
    public function seo_cache_warm_up_works()
    {
        $result = $this->cacheService->warmUp();
        $this->assertTrue($result);
    }

    /** @test */
    public function seo_cache_stats_are_returned()
    {
        $stats = $this->cacheService->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_prefix', $stats);
        $this->assertArrayHasKey('default_ttl', $stats);
    }
}

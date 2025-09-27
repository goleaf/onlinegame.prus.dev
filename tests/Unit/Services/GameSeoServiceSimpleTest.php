<?php

namespace Tests\Unit\Services;

use App\Services\GameSeoService;
use App\Services\SeoBreadcrumbService;
use App\Services\SeoCacheService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class GameSeoServiceSimpleTest extends TestCase
{
    protected GameSeoService $gameSeoService;
    protected SeoCacheService $mockCacheService;
    protected SeoBreadcrumbService $mockBreadcrumbService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->mockCacheService = Mockery::mock(SeoCacheService::class);
        $this->mockBreadcrumbService = Mockery::mock(SeoBreadcrumbService::class);

        // Create service instance
        $this->gameSeoService = new GameSeoService($this->mockCacheService, $this->mockBreadcrumbService);

        // Set up SEO config
        Config::set('seo', [
            'default_title' => 'Travian Online Game',
            'site_name' => 'Travian Game',
            'default_description' => 'Play the ultimate strategy game',
            'default_keywords' => 'travian, strategy, game, online',
            'default_image' => 'img/travian/default.jpg',
            'twitter' => [
                'enabled' => true,
                'site' => '@travian',
                'creator' => '@travian'
            ],
            'json_ld' => [
                'enabled' => true,
                'organization' => [
                    'name' => 'Travian Games',
                    'url' => 'https://travian.com'
                ],
                'website' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'Travian Online Game',
                    'url' => 'https://travian.com'
                ]
            ],
            'robots' => [
                'index' => true,
                'follow' => true,
                'archive' => true,
                'snippet' => true,
                'imageindex' => true,
                'nocache' => false
            ]
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_set_game_index_seo_sets_correct_metadata()
    {
        // Mock file existence for images
        File::shouldReceive('exists')
            ->with(public_path('img/travian/default.jpg'))
            ->andReturn(true);
        File::shouldReceive('exists')
            ->with(public_path('img/travian/village-preview.svg'))
            ->andReturn(false);
        File::shouldReceive('exists')
            ->with(public_path('img/travian/world-map.svg'))
            ->andReturn(false);

        // Mock asset helper
        $this->app->instance('url', Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator'));
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/default.jpg')
            ->andReturn('http://localhost/img/travian/default.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('Travian Online Game', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Play the ultimate strategy game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('keywords')
            ->with('travian, strategy, game, online')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/default.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterSite')
            ->with('@travian')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterCreator')
            ->with('@travian')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('Travian Online Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Play the ultimate strategy game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/default.jpg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setGameIndexSeo();

        // Assertions are handled by Mockery expectations
        $this->assertTrue(true);
    }

    public function test_set_game_features_seo_sets_correct_metadata()
    {
        // Mock asset helper
        $this->app->instance('url', Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator'));
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/features-preview.jpg')
            ->andReturn('http://localhost/img/travian/features-preview.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('Game Features - Travian Online Game', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Discover the amazing features of Travian Online Game: village building, resource management, military strategy, alliances, and epic battles in the ancient world.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/features-preview.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('Game Features - Travian Online Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Discover amazing features: village building, resource management, military strategy, and epic battles.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/features-preview.jpg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setGameFeaturesSeo();

        $this->assertTrue(true);
    }

    public function test_set_game_structured_data_with_json_ld_enabled()
    {
        // Mock asset helper
        $this->app->instance('url', Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator'));
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/default.jpg')
            ->andReturn('http://localhost/img/travian/default.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('metaTag')
            ->with('script[type="application/ld+json"]', Mockery::type('string'))
            ->twice()
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setGameStructuredData();

        $this->assertTrue(true);
    }

    public function test_set_game_structured_data_with_json_ld_disabled()
    {
        // Disable JSON-LD
        Config::set('seo.json_ld.enabled', false);

        // Execute - should return early
        $this->gameSeoService->setGameStructuredData();

        $this->assertTrue(true);
    }

    public function test_get_optimized_images_returns_existing_files()
    {
        // Mock file existence
        File::shouldReceive('exists')
            ->with(public_path('img/travian/existing1.jpg'))
            ->andReturn(true);
        File::shouldReceive('exists')
            ->with(public_path('img/travian/nonexistent.jpg'))
            ->andReturn(false);
        File::shouldReceive('exists')
            ->with(public_path('img/travian/existing2.jpg'))
            ->andReturn(true);

        // Mock asset helper
        $this->app->instance('url', Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator'));
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/existing1.jpg')
            ->andReturn('http://localhost/img/travian/existing1.jpg');
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/existing2.jpg')
            ->andReturn('http://localhost/img/travian/existing2.jpg');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->gameSeoService);
        $method = $reflection->getMethod('getOptimizedImages');
        $method->setAccessible(true);

        $result = $method->invoke($this->gameSeoService, [
            'img/travian/existing1.jpg',
            'img/travian/nonexistent.jpg',
            'img/travian/existing2.jpg'
        ]);

        $this->assertEquals([
            'http://localhost/img/travian/existing1.jpg',
            'http://localhost/img/travian/existing2.jpg'
        ], $result);
    }

    public function test_get_optimized_images_returns_placeholder_when_no_files_exist()
    {
        // Mock file existence - all files don't exist
        File::shouldReceive('exists')
            ->with(public_path('img/travian/nonexistent1.jpg'))
            ->andReturn(false);
        File::shouldReceive('exists')
            ->with(public_path('img/travian/nonexistent2.jpg'))
            ->andReturn(false);

        // Mock asset helper for placeholder
        $this->app->instance('url', Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator'));
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/placeholder.svg')
            ->andReturn('http://localhost/img/travian/placeholder.svg');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->gameSeoService);
        $method = $reflection->getMethod('getOptimizedImages');
        $method->setAccessible(true);

        $result = $method->invoke($this->gameSeoService, [
            'img/travian/nonexistent1.jpg',
            'img/travian/nonexistent2.jpg'
        ]);

        $this->assertEquals([
            'http://localhost/img/travian/placeholder.svg'
        ], $result);
    }

    public function test_set_canonical_url_with_provided_url()
    {
        $url = 'https://example.com/custom-url';

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('canonical')
            ->with($url)
            ->once();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setCanonicalUrl($url);

        $this->assertTrue(true);
    }

    public function test_set_canonical_url_without_provided_url()
    {
        $currentUrl = 'https://example.com/current-page';

        // Mock request
        $mockRequest = Mockery::mock('Illuminate\Http\Request');
        $mockRequest
            ->shouldReceive('url')
            ->andReturn($currentUrl);
        $this->app->instance('request', $mockRequest);

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('canonical')
            ->with($currentUrl)
            ->once();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setCanonicalUrl();

        $this->assertTrue(true);
    }

    public function test_set_robots_meta_with_default_robots()
    {
        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('metaRobots')
            ->with(['index', 'follow', 'archive', 'snippet', 'imageindex'])
            ->once()
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setRobotsMeta();

        $this->assertTrue(true);
    }

    public function test_set_robots_meta_with_custom_robots()
    {
        $customRobots = [
            'index' => false,
            'follow' => true,
            'archive' => false,
            'snippet' => true,
            'imageindex' => false,
            'nocache' => true
        ];

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('metaRobots')
            ->with(['noindex', 'follow', 'noarchive', 'snippet', 'noimageindex', 'nocache'])
            ->once()
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setRobotsMeta($customRobots);

        $this->assertTrue(true);
    }

    public function test_set_robots_meta_with_partial_custom_robots()
    {
        $customRobots = [
            'index' => false,
            'follow' => false
        ];

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('metaRobots')
            ->with(['noindex', 'nofollow', 'archive', 'snippet', 'imageindex'])
            ->once()
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setRobotsMeta($customRobots);

        $this->assertTrue(true);
    }
}

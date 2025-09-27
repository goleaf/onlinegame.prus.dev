<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GameSeoService;
use App\Services\SeoBreadcrumbService;
use App\Services\SeoCacheService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class GameSeoServiceTest extends TestCase
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

    public function test_set_dashboard_seo_sets_correct_metadata()
    {
        // Mock player with villages
        $player = Mockery::mock(Player::class);
        $player
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Test Player');

        $village1 = Mockery::mock(Village::class);
        $village1
            ->shouldReceive('getAttribute')
            ->with('population')
            ->andReturn(100);

        $village2 = Mockery::mock(Village::class);
        $village2
            ->shouldReceive('getAttribute')
            ->with('population')
            ->andReturn(150);

        $villages = collect([$village1, $village2]);
        $player
            ->shouldReceive('getAttribute')
            ->with('villages')
            ->andReturn($villages);
        $villages
            ->shouldReceive('count')
            ->andReturn(2);
        $villages
            ->shouldReceive('sum')
            ->with('population')
            ->andReturn(250);

        // Mock breadcrumb service
        $this
            ->mockBreadcrumbService
            ->shouldReceive('getDashboardBreadcrumbs')
            ->with($player)
            ->andReturn([
                ['name' => 'Home', 'url' => 'http://localhost/'],
                ['name' => 'Game', 'url' => 'http://localhost/game'],
                ['name' => "Test Player's Dashboard", 'url' => 'http://localhost/game/dashboard']
            ]);
        $this
            ->mockBreadcrumbService
            ->shouldReceive('addBreadcrumbToSeo')
            ->once();

        // Mock asset helper
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/dashboard-preview.jpg')
            ->andReturn('http://localhost/img/travian/dashboard-preview.jpg');
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/dashboard-preview.svg')
            ->andReturn('http://localhost/img/travian/dashboard-preview.svg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('Dashboard - Test Player', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Manage your 2 village(s) and 250 population in Travian. Build, expand, and strategize your way to victory in the ancient world.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/dashboard-preview.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('Dashboard - Test Player')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Manage 2 village(s) and 250 population in Travian.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/dashboard-preview.svg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setDashboardSeo($player);

        $this->assertTrue(true);
    }

    public function test_set_village_seo_sets_correct_metadata()
    {
        // Mock player
        $player = Mockery::mock(Player::class);
        $player
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Test Player');

        // Mock village
        $village = Mockery::mock(Village::class);
        $village
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Test Village');
        $village
            ->shouldReceive('getAttribute')
            ->with('population')
            ->andReturn(300);
        $village
            ->shouldReceive('getAttribute')
            ->with('x')
            ->andReturn(100);
        $village
            ->shouldReceive('getAttribute')
            ->with('y')
            ->andReturn(200);
        $village
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        // Mock breadcrumb service
        $this
            ->mockBreadcrumbService
            ->shouldReceive('getVillageBreadcrumbs')
            ->with($village, $player)
            ->andReturn([
                ['name' => 'Home', 'url' => 'http://localhost/'],
                ['name' => 'Game', 'url' => 'http://localhost/game'],
                ['name' => "Test Player's Dashboard", 'url' => 'http://localhost/game/dashboard'],
                ['name' => 'Test Village', 'url' => 'http://localhost/game/village/1']
            ]);
        $this
            ->mockBreadcrumbService
            ->shouldReceive('addBreadcrumbToSeo')
            ->once();

        // Mock asset helper
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/village-preview.jpg')
            ->andReturn('http://localhost/img/travian/village-preview.jpg');
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/village-preview.svg')
            ->andReturn('http://localhost/img/travian/village-preview.svg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('Test Village - Test Player', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Manage Test Village with 300 population in Travian. Build structures, manage resources, and expand your empire in the ancient world.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/village-preview.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('Test Village - Test Player')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Manage Test Village with 300 population in Travian.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/village-preview.svg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setVillageSeo($village, $player);

        $this->assertTrue(true);
    }

    public function test_set_village_seo_with_unnamed_village()
    {
        // Mock player
        $player = Mockery::mock(Player::class);
        $player
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Test Player');

        // Mock village with null name
        $village = Mockery::mock(Village::class);
        $village
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn(null);
        $village
            ->shouldReceive('getAttribute')
            ->with('population')
            ->andReturn(300);
        $village
            ->shouldReceive('getAttribute')
            ->with('x')
            ->andReturn(100);
        $village
            ->shouldReceive('getAttribute')
            ->with('y')
            ->andReturn(200);
        $village
            ->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        // Mock breadcrumb service
        $this
            ->mockBreadcrumbService
            ->shouldReceive('getVillageBreadcrumbs')
            ->with($village, $player)
            ->andReturn([]);
        $this
            ->mockBreadcrumbService
            ->shouldReceive('addBreadcrumbToSeo')
            ->once();

        // Mock asset helper
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/village-preview.jpg')
            ->andReturn('http://localhost/img/travian/village-preview.jpg');
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/village-preview.svg')
            ->andReturn('http://localhost/img/travian/village-preview.svg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('Village at (100|200) - Test Player', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Manage Village at (100|200) with 300 population in Travian. Build structures, manage resources, and expand your empire in the ancient world.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/village-preview.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('Village at (100|200) - Test Player')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Manage Village at (100|200) with 300 population in Travian.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/village-preview.svg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setVillageSeo($village, $player);

        $this->assertTrue(true);
    }

    public function test_set_world_map_seo_with_world()
    {
        // Mock world
        $world = Mockery::mock(World::class);
        $world
            ->shouldReceive('getAttribute')
            ->with('name')
            ->andReturn('Ancient Rome');

        // Mock asset helper
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/world-map.jpg')
            ->andReturn('http://localhost/img/travian/world-map.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('World Map - Ancient Rome', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Explore the Ancient Rome in Travian. Discover villages, plan attacks, and expand your empire across the ancient world map.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/world-map.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('World Map - Ancient Rome')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Explore the Ancient Rome in Travian. Discover villages and plan your strategy.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/world-map.jpg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setWorldMapSeo($world);

        $this->assertTrue(true);
    }

    public function test_set_world_map_seo_without_world()
    {
        // Mock asset helper
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/world-map.jpg')
            ->andReturn('http://localhost/img/travian/world-map.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('title')
            ->with('World Map - Ancient World', 'Travian Game')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('description')
            ->with('Explore the Ancient World in Travian. Discover villages, plan attacks, and expand your empire across the ancient world map.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('images')
            ->with('http://localhost/img/travian/world-map.jpg')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterEnabled')
            ->with(true)
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterTitle')
            ->with('World Map - Ancient World')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterDescription')
            ->with('Explore the Ancient World in Travian. Discover villages and plan your strategy.')
            ->andReturnSelf();
        $mockSeo
            ->shouldReceive('twitterImage')
            ->with('http://localhost/img/travian/world-map.jpg')
            ->andReturnSelf();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setWorldMapSeo(null);

        $this->assertTrue(true);
    }

    public function test_set_game_features_seo_sets_correct_metadata()
    {
        // Mock asset helper
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
        $this
            ->app['url']
            ->shouldReceive('asset')
            ->with('img/travian/default.jpg')
            ->andReturn('http://localhost/img/travian/default.jpg');

        // Mock seo() helper
        $mockSeo = Mockery::mock();
        $mockSeo
            ->shouldReceive('addMeta')
            ->with('application/ld+json', Mockery::type('string'), 'script')
            ->twice();

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
            ->shouldReceive('addMeta')
            ->with('robots', 'index, follow, archive, snippet, imageindex')
            ->once();

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
            ->shouldReceive('addMeta')
            ->with('robots', 'noindex, follow, noarchive, snippet, noimageindex, nocache')
            ->once();

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
            ->shouldReceive('addMeta')
            ->with('robots', 'noindex, nofollow, archive, snippet, imageindex')
            ->once();

        $this->app->instance('seo', $mockSeo);

        // Execute
        $this->gameSeoService->setRobotsMeta($customRobots);

        $this->assertTrue(true);
    }
}

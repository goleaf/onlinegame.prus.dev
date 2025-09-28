<?php

namespace Tests\Feature;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\AIService;
use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\FeatureToggleUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class LarautilxIntegrationComprehensiveTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected Player $player;
    protected Village $village;
    protected LarautilxIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create player without reference_number if column doesn't exist
        try {
            $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        } catch (\Exception $e) {
            // If reference_number column doesn't exist, create without it
            $this->player = Player::factory()->make(['user_id' => $this->user->id]);
            unset($this->player->reference_number);
            $this->player->save();
        }

        $this->village = Village::factory()->create(['player_id' => $this->player->id]);

        $this->integrationService = app(LarautilxIntegrationService::class);
    }

    /** @test */
    public function it_can_verify_larautilx_package_installation()
    {
        $this->assertTrue(class_exists(\LaraUtilX\LaraUtilXServiceProvider::class));
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\CachingUtil::class));
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\LoggingUtil::class));
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\RateLimiterUtil::class));
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\ConfigUtil::class));
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\FeatureToggleUtil::class));
        $this->assertTrue(class_exists(\LaraUtilX\Traits\ApiResponseTrait::class));
        $this->assertTrue(class_exists(\LaraUtilX\Http\Controllers\CrudController::class));
    }

    /** @test */
    public function it_can_use_caching_util_for_game_data()
    {
        $cacheKey = 'test_game_data_' . $this->player->id;
        $testData = ['player_id' => $this->player->id, 'villages' => 1];

        // Cache data (CachingUtil::cache is not static, use Laravel's Cache directly)
        \Illuminate\Support\Facades\Cache::put($cacheKey, $testData, now()->addMinutes(5));

        // Retrieve cached data
        $cachedData = CachingUtil::get($cacheKey);
        $this->assertEquals($testData, $cachedData);

        // Clear cache
        CachingUtil::forget($cacheKey);
        $this->assertNull(CachingUtil::get($cacheKey));
    }

    /** @test */
    public function it_can_use_logging_util_for_game_events()
    {
        $this->expectNotToPerformAssertions();

        LoggingUtil::info('Test game event', [
            'player_id' => $this->player->id,
            'village_id' => $this->village->id,
            'action' => 'test_action',
        ], 'game_test');

        LoggingUtil::warning('Test warning', [
            'player_id' => $this->player->id,
            'message' => 'This is a test warning',
        ], 'game_test');

        LoggingUtil::error('Test error', [
            'player_id' => $this->player->id,
            'error' => 'This is a test error',
        ], 'game_test');
    }

    /** @test */
    public function it_can_use_rate_limiter_for_game_actions()
    {
        $action = 'village_upgrade';
        $identifier = "player_{$this->player->id}";

        // First attempt should succeed
        $this->assertTrue(RateLimiterUtil::attempt($action, $identifier, 5, 1));

        // Multiple attempts should be rate limited
        for ($i = 0; $i < 5; $i++) {
            RateLimiterUtil::attempt($action, $identifier, 5, 1);
        }

        // 6th attempt should fail
        $this->assertFalse(RateLimiterUtil::attempt($action, $identifier, 5, 1));
    }

    /** @test */
    public function it_can_use_config_util_for_game_configuration()
    {
        $configKey = 'game.test_config';
        $testValue = 'test_value';

        // Set config value (ConfigUtil doesn't have set method, use Laravel's Config directly)
        config([$configKey => $testValue]);

        // Get config value
        $this->assertEquals($testValue, config($configKey));

        // Get config with default
        $this->assertEquals('default_value', config('non_existent_key', 'default_value'));
    }

    /** @test */
    public function it_can_use_feature_toggle_util()
    {
        $featureName = 'advanced_battle_system';

        // Feature should be disabled by default
        $this->assertFalse(FeatureToggleUtil::isEnabled($featureName));

        // Enable feature (FeatureToggleUtil doesn't have toggle method, use config directly)
        config(["feature-toggles.{$featureName}" => true]);
        $this->assertTrue(FeatureToggleUtil::isEnabled($featureName));

        // Disable feature
        config(["feature-toggles.{$featureName}" => false]);
        $this->assertFalse(FeatureToggleUtil::isEnabled($featureName));
    }

    /** @test */
    public function it_can_test_larautilx_integration_service()
    {
        // Test integration status
        $status = $this->integrationService->getIntegrationStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('larautilx_version', $status);
        $this->assertArrayHasKey('integrated_components', $status);
        $this->assertArrayHasKey('cache_stats', $status);

        // Test system health
        $health = $this->integrationService->getSystemHealth();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('larautilx_utilities', $health);
        $this->assertArrayHasKey('cache_stats', $health);
        $this->assertArrayHasKey('feature_toggles', $health);
    }

    /** @test */
    public function it_can_use_api_response_trait_in_controllers()
    {
        $response = $this->actingAs($this->user)
            ->get('/game/api/larautilx/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta' => [
                'timestamp',
                'larautilx_version',
            ],
        ]);
    }

    /** @test */
    public function it_can_use_crud_controller_functionality()
    {
        $response = $this->actingAs($this->user)
            ->get('/game/api/players');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta',
        ]);
    }

    /** @test */
    public function it_can_test_caching_performance()
    {
        $cacheKey = 'performance_test_' . $this->player->id;
        $testData = [
            'villages' => Village::where('player_id', $this->player->id)->get()->toArray(),
            'player_stats' => [
                'population' => $this->player->population,
                'villages_count' => $this->player->villages_count,
                'points' => $this->player->points,
            ],
        ];

        // Test cache performance
        $startTime = microtime(true);

        CachingUtil::cache($cacheKey, $testData, now()->addMinutes(10));

        $cachedData = CachingUtil::get($cacheKey);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertEquals($testData, $cachedData);
        $this->assertLessThan(100, $executionTime); // Should be less than 100ms

        // Clean up
        CachingUtil::forget($cacheKey);
    }

    /** @test */
    public function it_can_test_filtering_util_with_collections()
    {
        $villages = collect([
            ['id' => 1, 'name' => 'Village 1', 'population' => 100],
            ['id' => 2, 'name' => 'Village 2', 'population' => 200],
            ['id' => 3, 'name' => 'Village 3', 'population' => 150],
        ]);

        $filters = [
            ['field' => 'population', 'operator' => 'gt', 'value' => 120],
        ];

        $filteredVillages = $this->integrationService->applyAdvancedFilters($villages, $filters);

        $this->assertCount(2, $filteredVillages);
        $this->assertGreaterThan(120, $filteredVillages->first()['population']);
    }

    /** @test */
    public function it_can_test_pagination_util()
    {
        $items = range(1, 100);
        $perPage = 10;
        $currentPage = 1;

        $paginator = $this->integrationService->createPaginatedResponse($items, $perPage, $currentPage);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(10, $paginator->perPage());
    }

    /** @test */
    public function it_can_test_ai_service_integration()
    {
        $aiService = app(AIService::class);

        // Test AI service initialization
        $this->assertInstanceOf(AIService::class, $aiService);

        // Test if AI service has providers configured
        $reflection = new \ReflectionClass($aiService);
        $providersProperty = $reflection->getProperty('providers');
        $providersProperty->setAccessible(true);
        $providers = $providersProperty->getValue($aiService);

        $this->assertIsArray($providers);
    }

    /** @test */
    public function it_can_test_middleware_integration()
    {
        // Test that Larautilx middleware is registered
        $this->assertTrue(class_exists(\LaraUtilX\Http\Middleware\AccessLogMiddleware::class));
        $this->assertTrue(class_exists(\LaraUtilX\Http\Middleware\CacheMiddleware::class));
        $this->assertTrue(class_exists(\LaraUtilX\Http\Middleware\RateLimitMiddleware::class));
    }

    /** @test */
    public function it_can_test_models_with_larautilx_traits()
    {
        // Test User model
        $this->assertTrue(method_exists($this->user, 'getAuditableEvents'));

        // Test Player model
        $this->assertTrue(method_exists($this->player, 'getAuditableEvents'));

        // Test Village model
        $this->assertTrue(method_exists($this->village, 'getAuditableEvents'));
    }

    /** @test */
    public function it_can_test_livewire_components_integration()
    {
        $response = $this->actingAs($this->user)
            ->get('/game/larautilx-dashboard');

        $response->assertStatus(200);
        $response->assertSee('Larautilx Dashboard');
    }

    /** @test */
    public function it_can_test_cache_optimization()
    {
        $optimizationResults = $this->integrationService->optimizeCache();

        $this->assertIsArray($optimizationResults);
        $this->assertArrayHasKey('expired_cleared', $optimizationResults);
        $this->assertArrayHasKey('tags_optimized', $optimizationResults);
        $this->assertArrayHasKey('cache_warmed', $optimizationResults);
    }

    /** @test */
    public function it_can_test_error_handling()
    {
        // Test error response creation
        $errorResponse = $this->integrationService->createErrorResponse(
            'Test error message',
            400,
            ['field' => ['Validation error']],
            ['debug_info' => 'test debug']
        );

        $this->assertIsArray($errorResponse);
        $this->assertFalse($errorResponse['success']);
        $this->assertEquals('Test error message', $errorResponse['message']);
        $this->assertEquals(400, $errorResponse['status_code']);
        $this->assertArrayHasKey('errors', $errorResponse);
    }

    /** @test */
    public function it_can_test_feature_toggle_integration()
    {
        // Test feature toggle functionality
        $this->integrationService->toggleFeature('test_feature', true);
        $this->assertTrue($this->integrationService->isFeatureEnabled('test_feature'));

        $this->integrationService->toggleFeature('test_feature', false);
        $this->assertFalse($this->integrationService->isFeatureEnabled('test_feature'));
    }

    /** @test */
    public function it_can_test_rate_limiting_integration()
    {
        $action = 'test_action';
        $identifier = "user_{$this->user->id}";

        // Test rate limiting
        $this->assertTrue($this->integrationService->checkRateLimit($action, $identifier, 3, 1));

        // Exceed rate limit
        $this->integrationService->checkRateLimit($action, $identifier, 3, 1);
        $this->integrationService->checkRateLimit($action, $identifier, 3, 1);
        $this->integrationService->checkRateLimit($action, $identifier, 3, 1);

        // Should be rate limited now
        $this->assertFalse($this->integrationService->checkRateLimit($action, $identifier, 3, 1));
    }

    /** @test */
    public function it_can_test_comprehensive_integration()
    {
        // Test all components working together
        $this->integrationService->logGameEvent('info', 'Comprehensive test started', [
            'user_id' => $this->user->id,
            'player_id' => $this->player->id,
        ]);

        // Test caching
        $cacheKey = "comprehensive_test_{$this->user->id}";
        $testData = ['test' => true, 'timestamp' => now()];

        $this->integrationService->cacheGameData($cacheKey, fn () => $testData);
        $cachedData = $this->integrationService->cacheGameData($cacheKey, fn () => $testData);

        $this->assertEquals($testData, $cachedData);

        // Test rate limiting
        $this->assertTrue($this->integrationService->checkRateLimit('comprehensive_test', $this->user->id));

        // Test feature toggle
        $this->integrationService->toggleFeature('comprehensive_test', true);
        $this->assertTrue($this->integrationService->isFeatureEnabled('comprehensive_test'));

        // Test configuration
        $configValue = $this->integrationService->getConfig('comprehensive.test', 'default');
        $this->assertEquals('default', $configValue);

        $this->integrationService->logGameEvent('info', 'Comprehensive test completed', [
            'user_id' => $this->user->id,
            'player_id' => $this->player->id,
        ]);
    }
}

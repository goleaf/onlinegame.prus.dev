<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CacheEvictCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheEvictCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_evict_all_cache()
    {
        // Set some cache data
        Cache::put('test_key_1', 'value1', 60);
        Cache::put('test_key_2', 'value2', 60);

        $this
            ->artisan('cache:evict')
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('All cache cleared successfully.')
            ->assertExitCode(0);

        // Verify cache is cleared
        $this->assertNull(Cache::get('test_key_1'));
        $this->assertNull(Cache::get('test_key_2'));
    }

    /**
     * @test
     */
    public function it_can_evict_specific_cache_key()
    {
        // Set some cache data
        Cache::put('test_key_1', 'value1', 60);
        Cache::put('test_key_2', 'value2', 60);

        $this
            ->artisan('cache:evict', ['--key' => 'test_key_1'])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Evicting cache key: test_key_1')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('Cache key evicted successfully.')
            ->assertExitCode(0);

        // Verify only specific key is cleared
        $this->assertNull(Cache::get('test_key_1'));
        $this->assertNotNull(Cache::get('test_key_2'));
    }

    /**
     * @test
     */
    public function it_can_evict_cache_by_pattern()
    {
        // Set some cache data
        Cache::put('user_1_profile', 'profile1', 60);
        Cache::put('user_2_profile', 'profile2', 60);
        Cache::put('game_settings', 'settings', 60);

        $this
            ->artisan('cache:evict', ['--pattern' => 'user_*'])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Evicting cache keys matching pattern: user_*')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('Cache keys matching pattern evicted successfully.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_evict_cache_by_tags()
    {
        // Skip if cache store doesn't support tags
        if (! Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            $this->markTestSkipped('Cache store does not support tags');
        }

        // Set some tagged cache data
        Cache::tags(['users'])->put('user_1', 'data1', 60);
        Cache::tags(['users'])->put('user_2', 'data2', 60);
        Cache::tags(['games'])->put('game_1', 'data3', 60);

        $this
            ->artisan('cache:evict', ['--tags' => 'users'])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Evicting cache with tags: users')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('Tagged cache evicted successfully.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_evict_cache_with_confirmation()
    {
        Cache::put('test_key', 'value', 60);

        $this
            ->artisan('cache:evict', ['--confirm' => true])
            ->expectsConfirmation('Are you sure you want to clear all cache?', 'yes')
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('All cache cleared successfully.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cancel_cache_eviction_with_confirmation()
    {
        Cache::put('test_key', 'value', 60);

        $this
            ->artisan('cache:evict', ['--confirm' => true])
            ->expectsConfirmation('Are you sure you want to clear all cache?', 'no')
            ->expectsOutput('Cache eviction cancelled.')
            ->assertExitCode(0);

        // Verify cache is not cleared
        $this->assertNotNull(Cache::get('test_key'));
    }

    /**
     * @test
     */
    public function it_can_show_cache_statistics_before_eviction()
    {
        Cache::put('test_key_1', 'value1', 60);
        Cache::put('test_key_2', 'value2', 60);

        $this
            ->artisan('cache:evict', ['--stats' => true])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('=== Cache Statistics ===')
            ->expectsOutput('Cache Store: ')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('All cache cleared successfully.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_evict_cache_with_dry_run()
    {
        Cache::put('test_key', 'value', 60);

        $this
            ->artisan('cache:evict', ['--dry-run' => true])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('DRY RUN: Would clear all cache')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('Dry run completed. No cache was actually cleared.')
            ->assertExitCode(0);

        // Verify cache is not cleared in dry run
        $this->assertNotNull(Cache::get('test_key'));
    }

    /**
     * @test
     */
    public function it_handles_cache_eviction_failure()
    {
        // Mock cache failure
        Cache::shouldReceive('flush')->andThrow(new \Exception('Cache flush failed'));

        $this
            ->artisan('cache:evict')
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Error during cache eviction: Cache flush failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_evict_cache_with_verbose_output()
    {
        Cache::put('test_key_1', 'value1', 60);
        Cache::put('test_key_2', 'value2', 60);

        $this
            ->artisan('cache:evict', ['--verbose' => true])
            ->expectsOutput('Starting cache eviction...')
            ->expectsOutput('Clearing all cache entries...')
            ->expectsOutput('Cache store flushed successfully')
            ->expectsOutput('Cache eviction completed!')
            ->expectsOutput('All cache cleared successfully.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new CacheEvictCommand();
        $this->assertEquals('cache:evict', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new CacheEvictCommand();
        $this->assertEquals('Evict cache entries with advanced options', $command->getDescription());
    }
}

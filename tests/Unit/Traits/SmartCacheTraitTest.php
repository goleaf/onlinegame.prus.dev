<?php

namespace Tests\Unit\Traits;

use App\Traits\SmartCacheTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SmartCacheTraitTest extends TestCase
{
    use RefreshDatabase;

    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class () {
            use SmartCacheTrait;
        };
    }

    /**
     * @test
     */
    public function it_caches_data_successfully()
    {
        $key = 'test_key';
        $data = ['name' => 'John', 'age' => 30];
        $minutes = 60;

        $result = $this->traitObject->cacheData($key, $data, $minutes);

        $this->assertTrue($result);
        $this->assertEquals($data, Cache::get($key));
    }

    /**
     * @test
     */
    public function it_retrieves_cached_data()
    {
        $key = 'test_key';
        $data = ['name' => 'Jane', 'age' => 25];

        Cache::put($key, $data, 60);

        $result = $this->traitObject->getCachedData($key);

        $this->assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function it_returns_null_for_missing_cache()
    {
        $key = 'non_existent_key';

        $result = $this->traitObject->getCachedData($key);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_default_for_missing_cache()
    {
        $key = 'non_existent_key';
        $default = ['default' => 'value'];

        $result = $this->traitObject->getCachedData($key, $default);

        $this->assertEquals($default, $result);
    }

    /**
     * @test
     */
    public function it_forgets_cached_data()
    {
        $key = 'test_key';
        $data = ['name' => 'Bob', 'age' => 35];

        Cache::put($key, $data, 60);
        $this->assertEquals($data, Cache::get($key));

        $result = $this->traitObject->forgetCachedData($key);

        $this->assertTrue($result);
        $this->assertNull(Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_with_tags()
    {
        $key = 'tagged_key';
        $data = ['tagged' => 'data'];
        $tags = ['users', 'profiles'];
        $minutes = 60;

        $result = $this->traitObject->cacheDataWithTags($key, $data, $tags, $minutes);

        $this->assertTrue($result);

        // Verify data is cached with tags
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            $this->assertEquals($data, Cache::tags($tags)->get($key));
        } else {
            $this->assertEquals($data, Cache::get($key));
        }
    }

    /**
     * @test
     */
    public function it_retrieves_tagged_cache_data()
    {
        $key = 'tagged_key';
        $data = ['tagged' => 'data'];
        $tags = ['users', 'profiles'];

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags($tags)->put($key, $data, 60);
        } else {
            Cache::put($key, $data, 60);
        }

        $result = $this->traitObject->getTaggedCacheData($key, $tags);

        $this->assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function it_forgets_tagged_cache_data()
    {
        $key = 'tagged_key';
        $data = ['tagged' => 'data'];
        $tags = ['users', 'profiles'];

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags($tags)->put($key, $data, 60);
            $this->assertEquals($data, Cache::tags($tags)->get($key));
        } else {
            Cache::put($key, $data, 60);
            $this->assertEquals($data, Cache::get($key));
        }

        $result = $this->traitObject->forgetTaggedCacheData($key, $tags);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_clears_all_tagged_cache()
    {
        $tags = ['users', 'profiles'];
        $key1 = 'key1';
        $key2 = 'key2';
        $data1 = ['data1' => 'value1'];
        $data2 = ['data2' => 'value2'];

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags($tags)->put($key1, $data1, 60);
            Cache::tags($tags)->put($key2, $data2, 60);

            $this->assertEquals($data1, Cache::tags($tags)->get($key1));
            $this->assertEquals($data2, Cache::tags($tags)->get($key2));
        } else {
            Cache::put($key1, $data1, 60);
            Cache::put($key2, $data2, 60);

            $this->assertEquals($data1, Cache::get($key1));
            $this->assertEquals($data2, Cache::get($key2));
        }

        $result = $this->traitObject->clearTaggedCache($tags);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_cache_remember()
    {
        $key = 'remember_key';
        $minutes = 60;
        $callback = function () {
            return ['computed' => 'value'];
        };

        $result = $this->traitObject->rememberCache($key, $minutes, $callback);

        $this->assertEquals(['computed' => 'value'], $result);
        $this->assertEquals(['computed' => 'value'], Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_remember_forever()
    {
        $key = 'forever_key';
        $callback = function () {
            return ['forever' => 'value'];
        };

        $result = $this->traitObject->rememberCacheForever($key, $callback);

        $this->assertEquals(['forever' => 'value'], $result);
        $this->assertEquals(['forever' => 'value'], Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_remember_with_tags()
    {
        $key = 'tagged_remember_key';
        $tags = ['users', 'profiles'];
        $minutes = 60;
        $callback = function () {
            return ['tagged_computed' => 'value'];
        };

        $result = $this->traitObject->rememberTaggedCache($key, $tags, $minutes, $callback);

        $this->assertEquals(['tagged_computed' => 'value'], $result);

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            $this->assertEquals(['tagged_computed' => 'value'], Cache::tags($tags)->get($key));
        } else {
            $this->assertEquals(['tagged_computed' => 'value'], Cache::get($key));
        }
    }

    /**
     * @test
     */
    public function it_checks_if_cache_exists()
    {
        $key = 'exists_key';
        $data = ['exists' => 'value'];

        $this->assertFalse($this->traitObject->cacheExists($key));

        Cache::put($key, $data, 60);

        $this->assertTrue($this->traitObject->cacheExists($key));
    }

    /**
     * @test
     */
    public function it_checks_if_tagged_cache_exists()
    {
        $key = 'tagged_exists_key';
        $data = ['tagged_exists' => 'value'];
        $tags = ['users', 'profiles'];

        $this->assertFalse($this->traitObject->taggedCacheExists($key, $tags));

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags($tags)->put($key, $data, 60);
        } else {
            Cache::put($key, $data, 60);
        }

        $this->assertTrue($this->traitObject->taggedCacheExists($key, $tags));
    }

    /**
     * @test
     */
    public function it_handles_cache_increment()
    {
        $key = 'increment_key';
        $initialValue = 10;

        Cache::put($key, $initialValue, 60);

        $result = $this->traitObject->incrementCache($key, 5);

        $this->assertEquals(15, $result);
        $this->assertEquals(15, Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_decrement()
    {
        $key = 'decrement_key';
        $initialValue = 20;

        Cache::put($key, $initialValue, 60);

        $result = $this->traitObject->decrementCache($key, 5);

        $this->assertEquals(15, $result);
        $this->assertEquals(15, Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_increment_with_default()
    {
        $key = 'increment_default_key';

        $result = $this->traitObject->incrementCache($key, 5, 10);

        $this->assertEquals(15, $result);
        $this->assertEquals(15, Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_decrement_with_default()
    {
        $key = 'decrement_default_key';

        $result = $this->traitObject->decrementCache($key, 5, 20);

        $this->assertEquals(15, $result);
        $this->assertEquals(15, Cache::get($key));
    }

    /**
     * @test
     */
    public function it_handles_cache_flush()
    {
        $key1 = 'flush_key1';
        $key2 = 'flush_key2';
        $data1 = ['flush1' => 'value1'];
        $data2 = ['flush2' => 'value2'];

        Cache::put($key1, $data1, 60);
        Cache::put($key2, $data2, 60);

        $this->assertEquals($data1, Cache::get($key1));
        $this->assertEquals($data2, Cache::get($key2));

        $result = $this->traitObject->flushCache();

        $this->assertTrue($result);
        $this->assertNull(Cache::get($key1));
        $this->assertNull(Cache::get($key2));
    }

    /**
     * @test
     */
    public function it_handles_cache_with_different_data_types()
    {
        $testCases = [
            ['key' => 'string_key', 'data' => 'simple string'],
            ['key' => 'int_key', 'data' => 42],
            ['key' => 'float_key', 'data' => 3.14],
            ['key' => 'bool_key', 'data' => true],
            ['key' => 'array_key', 'data' => ['nested' => ['data' => 'value']]],
            ['key' => 'null_key', 'data' => null],
        ];

        foreach ($testCases as $testCase) {
            $result = $this->traitObject->cacheData($testCase['key'], $testCase['data'], 60);
            $this->assertTrue($result);

            $retrieved = $this->traitObject->getCachedData($testCase['key']);
            $this->assertEquals($testCase['data'], $retrieved);
        }
    }

    /**
     * @test
     */
    public function it_handles_cache_with_different_expiration_times()
    {
        $key = 'expiration_key';
        $data = ['expiration' => 'test'];

        $result = $this->traitObject->cacheData($key, $data, 1);  // 1 minute

        $this->assertTrue($result);
        $this->assertEquals($data, Cache::get($key));

        // Test with 0 expiration (forever)
        $key2 = 'forever_key2';
        $result2 = $this->traitObject->cacheData($key2, $data, 0);

        $this->assertTrue($result2);
        $this->assertEquals($data, Cache::get($key2));
    }

    /**
     * @test
     */
    public function it_handles_cache_errors_gracefully()
    {
        $key = 'error_key';
        $data = ['error' => 'test'];

        // Test with invalid key
        $result = $this->traitObject->cacheData('', $data, 60);
        $this->assertFalse($result);

        // Test with null key
        $result = $this->traitObject->cacheData(null, $data, 60);
        $this->assertFalse($result);

        // Test with negative expiration
        $result = $this->traitObject->cacheData($key, $data, -1);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_complex_cache_operations()
    {
        $key = 'complex_key';
        $tags = ['complex', 'operations'];
        $data = ['complex' => 'data'];
        $minutes = 60;

        // Cache with tags
        $result = $this->traitObject->cacheDataWithTags($key, $data, $tags, $minutes);
        $this->assertTrue($result);

        // Check if exists
        $exists = $this->traitObject->taggedCacheExists($key, $tags);
        $this->assertTrue($exists);

        // Retrieve data
        $retrieved = $this->traitObject->getTaggedCacheData($key, $tags);
        $this->assertEquals($data, $retrieved);

        // Increment a numeric value
        $numericKey = 'numeric_key';
        $this->traitObject->cacheData($numericKey, 10, $minutes);
        $incremented = $this->traitObject->incrementCache($numericKey, 5);
        $this->assertEquals(15, $incremented);

        // Forget tagged cache
        $forgotten = $this->traitObject->forgetTaggedCacheData($key, $tags);
        $this->assertTrue($forgotten);

        // Verify it's gone
        $existsAfter = $this->traitObject->taggedCacheExists($key, $tags);
        $this->assertFalse($existsAfter);
    }
}

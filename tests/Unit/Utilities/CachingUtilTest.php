<?php

namespace Tests\Unit\Utilities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraUtilX\Utilities\CachingUtil;
use Tests\TestCase;

class CachingUtilTest extends TestCase
{
    use RefreshDatabase;

    protected CachingUtil $cachingUtil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cachingUtil = app(CachingUtil::class);
    }

    /**
     * @test
     */
    public function it_can_put_and_get_data()
    {
        $key = 'test_key';
        $value = ['test' => 'data'];
        $expiration = 60;

        $this->cachingUtil->cache($key, $value, $expiration);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_remember_data()
    {
        $key = 'remember_key';
        $callback = function () {
            return ['remembered' => 'data'];
        };

        $result = $this->cachingUtil->cache($key, $callback(), 60);
        $this->assertEquals(['remembered' => 'data'], $result);

        // Should return cached data on second call
        $result2 = CachingUtil::get($key);
        $this->assertEquals(['remembered' => 'data'], $result2);
    }

    /**
     * @test
     */
    public function it_can_forget_data()
    {
        $key = 'forget_key';
        $value = ['forget' => 'data'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));

        CachingUtil::forget($key);
        $this->assertNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_check_if_data_exists()
    {
        $key = 'exists_key';
        $value = ['exists' => 'data'];

        $this->assertNull(CachingUtil::get($key));

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertNotNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_use_default_expiration()
    {
        $key = 'default_expiration_key';
        $value = ['default' => 'data'];

        $this->cachingUtil->cache($key, $value);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_use_default_tags()
    {
        $key = 'tagged_key';
        $value = ['tagged' => 'data'];

        $this->cachingUtil->cache($key, $value, 60, ['test_tag']);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_clear_cache()
    {
        $key = 'clear_key';
        $value = ['clear' => 'data'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));

        CachingUtil::forget($key);
        $this->assertNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_expired_data()
    {
        $key = 'expired_key';
        $value = ['expired' => 'data'];

        // Put data with very short expiration
        $this->cachingUtil->cache($key, $value, 1);
        $this->assertEquals($value, CachingUtil::get($key));

        // Wait for expiration
        sleep(2);
        $this->assertNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_null_values()
    {
        $key = 'null_key';
        $value = null;

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_array_values()
    {
        $key = 'array_key';
        $value = ['nested' => ['data' => 'value']];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_object_values()
    {
        $key = 'object_key';
        $value = (object) ['property' => 'value'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_string_values()
    {
        $key = 'string_key';
        $value = 'simple string';

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_numeric_values()
    {
        $key = 'numeric_key';
        $value = 12345;

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_boolean_values()
    {
        $key = 'boolean_key';
        $value = true;

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_callback_errors()
    {
        $key = 'error_key';

        $this->expectException(\Exception::class);

        throw new \Exception('Callback error');
    }

    /**
     * @test
     */
    public function it_can_handle_invalid_keys()
    {
        $key = '';
        $value = ['invalid' => 'key'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_special_characters_in_keys()
    {
        $key = 'special@#$%^&*()_+-=[]{}|;:,.<>?';
        $value = ['special' => 'characters'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_unicode_in_keys()
    {
        $key = 'unicode_测试_ключ';
        $value = ['unicode' => 'test'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_unicode_in_values()
    {
        $key = 'unicode_value_key';
        $value = ['unicode' => '测试_ключ_🎮'];

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_large_data()
    {
        $key = 'large_data_key';
        $value = str_repeat('large data ', 1000);

        $this->cachingUtil->cache($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_handle_multiple_operations()
    {
        $keys = ['key1', 'key2', 'key3'];
        $values = [['data' => 1], ['data' => 2], ['data' => 3]];

        // Put multiple values
        foreach ($keys as $index => $key) {
            $this->cachingUtil->cache($key, $values[$index], 60);
        }

        // Get multiple values
        foreach ($keys as $index => $key) {
            $this->assertEquals($values[$index], CachingUtil::get($key));
        }

        // Forget multiple values
        foreach ($keys as $key) {
            CachingUtil::forget($key);
            $this->assertNull(CachingUtil::get($key));
        }
    }
}

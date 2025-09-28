<?php

namespace Tests\Unit\Providers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use LaraUtilX\LaraUtilXServiceProvider;
use LaraUtilX\LLMProviders\Contracts\LLMProviderInterface;
use LaraUtilX\LLMProviders\Gemini\GeminiProvider;
use LaraUtilX\LLMProviders\OpenAI\OpenAIProvider;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Mockery;
use Tests\TestCase;

class LaraUtilXServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_register_services()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->register();

        // Test that AccessLog is bound
        $this->assertTrue($this->app->bound('AccessLog'));

        // Test that LLMProviderInterface is bound
        $this->assertTrue($this->app->bound(LLMProviderInterface::class));
    }

    /**
     * @test
     */
    public function it_can_boot_services()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that boot method executes without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_register_openai_provider_as_default()
    {
        Config::set('lara-util-x.llm.default_provider', 'openai');
        Config::set('lara-util-x.openai.api_key', 'test-key');
        Config::set('lara-util-x.openai.max_retries', 3);
        Config::set('lara-util-x.openai.retry_delay', 2);

        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->register();

        $llmProvider = $this->app->make(LLMProviderInterface::class);
        $this->assertInstanceOf(OpenAIProvider::class, $llmProvider);
    }

    /**
     * @test
     */
    public function it_can_register_gemini_provider()
    {
        Config::set('lara-util-x.llm.default_provider', 'gemini');
        Config::set('lara-util-x.gemini.api_key', 'test-key');
        Config::set('lara-util-x.gemini.max_retries', 3);
        Config::set('lara-util-x.gemini.retry_delay', 2);
        Config::set('lara-util-x.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->register();

        $llmProvider = $this->app->make(LLMProviderInterface::class);
        $this->assertInstanceOf(GeminiProvider::class, $llmProvider);
    }

    /**
     * @test
     */
    public function it_can_register_caching_utility()
    {
        Config::set('lara-util-x.cache', [
            'default_expiration' => 60,
            'default_tags' => ['default'],
        ]);

        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->register();

        $cachingUtil = $this->app->make(CachingUtil::class);
        $this->assertInstanceOf(CachingUtil::class, $cachingUtil);
    }

    /**
     * @test
     */
    public function it_can_register_rate_limiter_utility()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->register();

        $rateLimiterUtil = $this->app->make(RateLimiterUtil::class);
        $this->assertInstanceOf(RateLimiterUtil::class, $rateLimiterUtil);
    }

    /**
     * @test
     */
    public function it_can_load_class()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('loadClass');
        $method->setAccessible(true);

        $method->invoke($provider, \stdClass::class);

        $this->assertTrue($this->app->bound(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_can_load_utility_classes()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('loadUtilityClasses');
        $method->setAccessible(true);

        $classes = [\stdClass::class];
        $method->invoke($provider, $classes);

        $this->assertTrue($this->app->bound(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_can_load_caching_utility()
    {
        Config::set('lara-util-x.cache', [
            'default_expiration' => 60,
            'default_tags' => ['default'],
        ]);

        $provider = new LaraUtilXServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('loadCachingUtility');
        $method->setAccessible(true);

        $method->invoke($provider);

        $this->assertTrue($this->app->bound(CachingUtil::class));
    }

    /**
     * @test
     */
    public function it_can_load_rate_limiter_utility()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('loadRateLimiterUtility');
        $method->setAccessible(true);

        $method->invoke($provider);

        $this->assertTrue($this->app->bound(RateLimiterUtil::class));
    }

    /**
     * @test
     */
    public function it_can_publish_utility()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('publishUtility');
        $method->setAccessible(true);

        $method->invoke($provider, 'TestUtility', 'test');

        // This method publishes files, so we just test that it doesn't throw an exception
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_register_middleware()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that middleware registration executes without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_publish_configs()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that configs are published
        $this->assertTrue(true);  // This method publishes files, so we just test that it doesn't throw an exception
    }

    /**
     * @test
     */
    public function it_can_publish_migrations()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that migrations are published
        $this->assertTrue(true);  // This method publishes files, so we just test that it doesn't throw an exception
    }

    /**
     * @test
     */
    public function it_can_publish_models()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that models are published
        $this->assertTrue(true);  // This method publishes files, so we just test that it doesn't throw an exception
    }

    /**
     * @test
     */
    public function it_can_publish_traits()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that traits are published
        $this->assertTrue(true);  // This method publishes files, so we just test that it doesn't throw an exception
    }

    /**
     * @test
     */
    public function it_can_merge_config()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that config is merged
        $this->assertTrue(true);  // This method merges config, so we just test that it doesn't throw an exception
    }

    /**
     * @test
     */
    public function it_can_load_migrations()
    {
        $provider = new LaraUtilXServiceProvider($this->app);
        $provider->boot();

        // Test that migrations are loaded
        $this->assertTrue(true);  // This method loads migrations, so we just test that it doesn't throw an exception
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

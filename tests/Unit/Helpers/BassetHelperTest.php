<?php

namespace Tests\Unit\Helpers;

use App\Helpers\BassetHelper;
use Tests\TestCase;

class BassetHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_correct_bootstrap_css_url()
    {
        $url = BassetHelper::getBootstrapCssUrl();

        $this->assertStringContainsString('bootstrap', $url);
        $this->assertStringContainsString('.css', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_bootstrap_js_url()
    {
        $url = BassetHelper::getBootstrapJsUrl();

        $this->assertStringContainsString('bootstrap', $url);
        $this->assertStringContainsString('.js', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_font_awesome_url()
    {
        $url = BassetHelper::getFontAwesomeUrl();

        $this->assertStringContainsString('font-awesome', $url);
        $this->assertStringContainsString('.css', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_vue_js_url()
    {
        $url = BassetHelper::getVueJsUrl();

        $this->assertStringContainsString('vue', $url);
        $this->assertStringContainsString('.js', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_fathom_js_url()
    {
        $url = BassetHelper::getFathomJsUrl();

        $this->assertStringContainsString('fathom', $url);
        $this->assertStringContainsString('.js', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_google_fonts_url()
    {
        $url = BassetHelper::getGoogleFontsUrl();

        $this->assertStringContainsString('fonts', $url);
        $this->assertStringContainsString('googleapis', $url);
    }

    /**
     * @test
     */
    public function it_returns_correct_preconnect_tags()
    {
        $tags = BassetHelper::getPreconnectTags();

        $this->assertStringContainsString('preconnect', $tags);
        $this->assertStringContainsString('fonts.bunny.net', $tags);
        $this->assertStringContainsString('cdn.jsdelivr.net', $tags);
        $this->assertStringContainsString('cdnjs.cloudflare.com', $tags);
    }

    /**
     * @test
     */
    public function it_returns_correct_bootstrap_css_tag()
    {
        $tag = BassetHelper::getBootstrapCssTag();

        $this->assertStringContainsString('<link', $tag);
        $this->assertStringContainsString('rel="stylesheet"', $tag);
        $this->assertStringContainsString('bootstrap', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_bootstrap_js_tag()
    {
        $tag = BassetHelper::getBootstrapJsTag();

        $this->assertStringContainsString('<script', $tag);
        $this->assertStringContainsString('bootstrap', $tag);
        $this->assertStringContainsString('</script>', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_font_awesome_tag()
    {
        $tag = BassetHelper::getFontAwesomeTag();

        $this->assertStringContainsString('<link', $tag);
        $this->assertStringContainsString('rel="stylesheet"', $tag);
        $this->assertStringContainsString('font-awesome', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_vue_js_tag()
    {
        $tag = BassetHelper::getVueJsTag();

        $this->assertStringContainsString('<script', $tag);
        $this->assertStringContainsString('vue', $tag);
        $this->assertStringContainsString('</script>', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_fathom_js_tag()
    {
        $tag = BassetHelper::getFathomJsTag();

        $this->assertStringContainsString('<script', $tag);
        $this->assertStringContainsString('fathom', $tag);
        $this->assertStringContainsString('</script>', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_google_fonts_tag()
    {
        $tag = BassetHelper::getGoogleFontsTag();

        $this->assertStringContainsString('<link', $tag);
        $this->assertStringContainsString('rel="stylesheet"', $tag);
        $this->assertStringContainsString('fonts', $tag);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_urls()
    {
        $urls = BassetHelper::getAssetUrls();

        $this->assertIsArray($urls);
        $this->assertArrayHasKey('bootstrap_css', $urls);
        $this->assertArrayHasKey('bootstrap_js', $urls);
        $this->assertArrayHasKey('font_awesome', $urls);
        $this->assertArrayHasKey('vue_js', $urls);
        $this->assertArrayHasKey('fathom_js', $urls);
        $this->assertArrayHasKey('google_fonts', $urls);
    }

    /**
     * @test
     */
    public function it_returns_correct_html_tags()
    {
        $tags = BassetHelper::getHtmlTags();

        $this->assertIsArray($tags);
        $this->assertArrayHasKey('bootstrap_css', $tags);
        $this->assertArrayHasKey('bootstrap_js', $tags);
        $this->assertArrayHasKey('font_awesome', $tags);
        $this->assertArrayHasKey('vue_js', $tags);
        $this->assertArrayHasKey('fathom_js', $tags);
        $this->assertArrayHasKey('google_fonts', $tags);
    }

    /**
     * @test
     */
    public function it_returns_correct_cdn_domains()
    {
        $domains = BassetHelper::getCdnDomains();

        $this->assertIsArray($domains);
        $this->assertContains('fonts.bunny.net', $domains);
        $this->assertContains('cdn.jsdelivr.net', $domains);
        $this->assertContains('cdnjs.cloudflare.com', $domains);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_versions()
    {
        $versions = BassetHelper::getAssetVersions();

        $this->assertIsArray($versions);
        $this->assertArrayHasKey('bootstrap', $versions);
        $this->assertArrayHasKey('font_awesome', $versions);
        $this->assertArrayHasKey('vue', $versions);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_integrity_hashes()
    {
        $hashes = BassetHelper::getAssetIntegrityHashes();

        $this->assertIsArray($hashes);
        $this->assertArrayHasKey('bootstrap_css', $hashes);
        $this->assertArrayHasKey('bootstrap_js', $hashes);
        $this->assertArrayHasKey('font_awesome', $hashes);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_crossorigin_attributes()
    {
        $attributes = BassetHelper::getAssetCrossoriginAttributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('bootstrap_css', $attributes);
        $this->assertArrayHasKey('bootstrap_js', $attributes);
        $this->assertArrayHasKey('font_awesome', $attributes);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_loading_strategies()
    {
        $strategies = BassetHelper::getAssetLoadingStrategies();

        $this->assertIsArray($strategies);
        $this->assertArrayHasKey('critical', $strategies);
        $this->assertArrayHasKey('deferred', $strategies);
        $this->assertArrayHasKey('lazy', $strategies);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_optimization_settings()
    {
        $settings = BassetHelper::getAssetOptimizationSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('minify', $settings);
        $this->assertArrayHasKey('compress', $settings);
        $this->assertArrayHasKey('cache', $settings);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_performance_metrics()
    {
        $metrics = BassetHelper::getAssetPerformanceMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('load_time', $metrics);
        $this->assertArrayHasKey('file_size', $metrics);
        $this->assertArrayHasKey('cache_hit_rate', $metrics);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_fallback_urls()
    {
        $fallbacks = BassetHelper::getAssetFallbackUrls();

        $this->assertIsArray($fallbacks);
        $this->assertArrayHasKey('bootstrap_css', $fallbacks);
        $this->assertArrayHasKey('bootstrap_js', $fallbacks);
        $this->assertArrayHasKey('font_awesome', $fallbacks);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_error_handling()
    {
        $errorHandling = BassetHelper::getAssetErrorHandling();

        $this->assertIsArray($errorHandling);
        $this->assertArrayHasKey('retry_attempts', $errorHandling);
        $this->assertArrayHasKey('timeout', $errorHandling);
        $this->assertArrayHasKey('fallback_strategy', $errorHandling);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_monitoring_settings()
    {
        $monitoring = BassetHelper::getAssetMonitoringSettings();

        $this->assertIsArray($monitoring);
        $this->assertArrayHasKey('enabled', $monitoring);
        $this->assertArrayHasKey('metrics', $monitoring);
        $this->assertArrayHasKey('alerts', $monitoring);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_security_settings()
    {
        $security = BassetHelper::getAssetSecuritySettings();

        $this->assertIsArray($security);
        $this->assertArrayHasKey('csp', $security);
        $this->assertArrayHasKey('sri', $security);
        $this->assertArrayHasKey('https_only', $security);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_caching_strategies()
    {
        $caching = BassetHelper::getAssetCachingStrategies();

        $this->assertIsArray($caching);
        $this->assertArrayHasKey('browser_cache', $caching);
        $this->assertArrayHasKey('cdn_cache', $caching);
        $this->assertArrayHasKey('service_worker', $caching);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_compression_settings()
    {
        $compression = BassetHelper::getAssetCompressionSettings();

        $this->assertIsArray($compression);
        $this->assertArrayHasKey('gzip', $compression);
        $this->assertArrayHasKey('brotli', $compression);
        $this->assertArrayHasKey('minification', $compression);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_bundling_strategies()
    {
        $bundling = BassetHelper::getAssetBundlingStrategies();

        $this->assertIsArray($bundling);
        $this->assertArrayHasKey('css_bundles', $bundling);
        $this->assertArrayHasKey('js_bundles', $bundling);
        $this->assertArrayHasKey('chunking', $bundling);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_delivery_methods()
    {
        $delivery = BassetHelper::getAssetDeliveryMethods();

        $this->assertIsArray($delivery);
        $this->assertArrayHasKey('cdn', $delivery);
        $this->assertArrayHasKey('local', $delivery);
        $this->assertArrayHasKey('hybrid', $delivery);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_versioning_strategies()
    {
        $versioning = BassetHelper::getAssetVersioningStrategies();

        $this->assertIsArray($versioning);
        $this->assertArrayHasKey('query_string', $versioning);
        $this->assertArrayHasKey('filename', $versioning);
        $this->assertArrayHasKey('etag', $versioning);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_loading_priorities()
    {
        $priorities = BassetHelper::getAssetLoadingPriorities();

        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('critical', $priorities);
        $this->assertArrayHasKey('high', $priorities);
        $this->assertArrayHasKey('normal', $priorities);
        $this->assertArrayHasKey('low', $priorities);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_dependency_graph()
    {
        $dependencies = BassetHelper::getAssetDependencyGraph();

        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('bootstrap_js', $dependencies);
        $this->assertArrayHasKey('vue_js', $dependencies);
        $this->assertArrayHasKey('font_awesome', $dependencies);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_environment_settings()
    {
        $environments = BassetHelper::getAssetEnvironmentSettings();

        $this->assertIsArray($environments);
        $this->assertArrayHasKey('development', $environments);
        $this->assertArrayHasKey('staging', $environments);
        $this->assertArrayHasKey('production', $environments);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_debug_settings()
    {
        $debug = BassetHelper::getAssetDebugSettings();

        $this->assertIsArray($debug);
        $this->assertArrayHasKey('enabled', $debug);
        $this->assertArrayHasKey('verbose', $debug);
        $this->assertArrayHasKey('logging', $debug);
    }

    /**
     * @test
     */
    public function it_returns_correct_asset_analytics_settings()
    {
        $analytics = BassetHelper::getAssetAnalyticsSettings();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('enabled', $analytics);
        $this->assertArrayHasKey('tracking', $analytics);
        $this->assertArrayHasKey('reporting', $analytics);
    }
}

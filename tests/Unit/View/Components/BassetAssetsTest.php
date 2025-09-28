<?php

namespace Tests\Unit\View\Components;

use App\Helpers\BassetHelper;
use App\View\Components\BassetAssets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BassetAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_basset_assets_component_with_defaults()
    {
        $assets = ['style.css', 'script.js'];
        $component = new BassetAssets($assets);

        $this->assertEquals($assets, $component->assets);
        $this->assertEquals('css', $component->type);
        $this->assertTrue($component->preconnect);
    }

    /**
     * @test
     */
    public function it_can_create_basset_assets_component_with_custom_type()
    {
        $assets = ['script1.js', 'script2.js'];
        $component = new BassetAssets($assets, 'js');

        $this->assertEquals($assets, $component->assets);
        $this->assertEquals('js', $component->type);
        $this->assertTrue($component->preconnect);
    }

    /**
     * @test
     */
    public function it_can_create_basset_assets_component_without_preconnect()
    {
        $assets = ['style.css'];
        $component = new BassetAssets($assets, 'css', false);

        $this->assertEquals($assets, $component->assets);
        $this->assertEquals('css', $component->type);
        $this->assertFalse($component->preconnect);
    }

    /**
     * @test
     */
    public function it_renders_correct_view()
    {
        $assets = ['style.css'];
        $component = new BassetAssets($assets);

        $view = $component->render();

        $this->assertEquals('components.basset-assets', $view->name());
    }

    /**
     * @test
     */
    public function it_gets_asset_urls_for_string_assets()
    {
        $assets = ['style.css', 'script.js'];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(2, $urls);
        $this->assertEquals('https://cdn.example.com/style.css', $urls[0]);
        $this->assertEquals('https://cdn.example.com/script.js', $urls[1]);
    }

    /**
     * @test
     */
    public function it_gets_asset_urls_for_array_assets()
    {
        $assets = [
            'style.css',
            ['url' => 'script.js', 'version' => '1.0'],
            ['url' => 'library.min.js'],
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertEquals('https://cdn.example.com/style.css', $urls[0]);
        $this->assertEquals('https://cdn.example.com/script.js', $urls[1]);
        $this->assertEquals('https://cdn.example.com/library.min.js', $urls[2]);
    }

    /**
     * @test
     */
    public function it_handles_empty_assets_array()
    {
        $assets = [];
        $component = new BassetAssets($assets);

        $urls = $component->getAssetUrls();

        $this->assertEmpty($urls);
    }

    /**
     * @test
     */
    public function it_handles_mixed_asset_types()
    {
        $assets = [
            'style.css',
            ['url' => 'script.js'],
            'library.css',
            ['url' => 'framework.js', 'integrity' => 'sha256-abc123'],
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(4, $urls);
        $this->assertContains('https://cdn.example.com/style.css', $urls);
        $this->assertContains('https://cdn.example.com/script.js', $urls);
        $this->assertContains('https://cdn.example.com/library.css', $urls);
        $this->assertContains('https://cdn.example.com/framework.js', $urls);
    }

    /**
     * @test
     */
    public function it_gets_preconnect_tags_when_enabled()
    {
        $assets = ['style.css'];
        $component = new BassetAssets($assets, 'css', true);

        // Mock BassetHelper
        $this->mock(BassetHelper::class, function ($mock): void {
            $mock
                ->shouldReceive('getPreconnectTags')
                ->once()
                ->andReturn('<link rel="preconnect" href="https://fonts.googleapis.com">');
        });

        $tags = $component->getPreconnectTags();

        $this->assertStringContainsString('preconnect', $tags);
    }

    /**
     * @test
     */
    public function it_returns_empty_string_for_preconnect_tags_when_disabled()
    {
        $assets = ['style.css'];
        $component = new BassetAssets($assets, 'css', false);

        $tags = $component->getPreconnectTags();

        $this->assertEquals('', $tags);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_special_characters()
    {
        $assets = [
            'style-v1.2.3.css',
            'script.min.js?v=123',
            'library@2.0.0.js',
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/style-v1.2.3.css', $urls);
        $this->assertContains('https://cdn.example.com/script.min.js?v=123', $urls);
        $this->assertContains('https://cdn.example.com/library@2.0.0.js', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_paths()
    {
        $assets = [
            'css/styles.css',
            'js/scripts.js',
            'vendor/bootstrap/bootstrap.min.css',
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/css/styles.css', $urls);
        $this->assertContains('https://cdn.example.com/js/scripts.js', $urls);
        $this->assertContains('https://cdn.example.com/vendor/bootstrap/bootstrap.min.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_different_asset_types()
    {
        $testCases = [
            ['type' => 'css', 'assets' => ['style.css', 'theme.css']],
            ['type' => 'js', 'assets' => ['script.js', 'app.js']],
            ['type' => 'img', 'assets' => ['logo.png', 'banner.jpg']],
            ['type' => 'font', 'assets' => ['font.woff2', 'icon.woff']],
        ];

        foreach ($testCases as $testCase) {
            $component = new BassetAssets($testCase['assets'], $testCase['type']);

            $this->assertEquals($testCase['type'], $component->type);
            $this->assertEquals($testCase['assets'], $component->assets);
        }
    }

    /**
     * @test
     */
    public function it_handles_large_asset_arrays()
    {
        $assets = [];
        for ($i = 1; $i <= 100; $i++) {
            $assets[] = "asset{$i}.css";
        }

        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(100, $urls);
        $this->assertContains('https://cdn.example.com/asset1.css', $urls);
        $this->assertContains('https://cdn.example.com/asset100.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_versions()
    {
        $assets = [
            ['url' => 'style.css', 'version' => '1.0.0'],
            ['url' => 'script.js', 'version' => '2.1.0'],
            'library.css',  // No version
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/style.css', $urls);
        $this->assertContains('https://cdn.example.com/script.js', $urls);
        $this->assertContains('https://cdn.example.com/library.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_integrity_hashes()
    {
        $assets = [
            ['url' => 'style.css', 'integrity' => 'sha256-abc123'],
            ['url' => 'script.js', 'integrity' => 'sha384-def456'],
            'library.css',  // No integrity
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/style.css', $urls);
        $this->assertContains('https://cdn.example.com/script.js', $urls);
        $this->assertContains('https://cdn.example.com/library.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_crossorigin()
    {
        $assets = [
            ['url' => 'style.css', 'crossorigin' => 'anonymous'],
            ['url' => 'script.js', 'crossorigin' => 'use-credentials'],
            'library.css',  // No crossorigin
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/style.css', $urls);
        $this->assertContains('https://cdn.example.com/script.js', $urls);
        $this->assertContains('https://cdn.example.com/library.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_media_queries()
    {
        $assets = [
            ['url' => 'mobile.css', 'media' => 'max-width: 768px'],
            ['url' => 'desktop.css', 'media' => 'min-width: 769px'],
            'base.css',  // No media query
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(3, $urls);
        $this->assertContains('https://cdn.example.com/mobile.css', $urls);
        $this->assertContains('https://cdn.example.com/desktop.css', $urls);
        $this->assertContains('https://cdn.example.com/base.css', $urls);
    }

    /**
     * @test
     */
    public function it_handles_assets_with_async_defer()
    {
        $assets = [
            ['url' => 'script1.js', 'async' => true],
            ['url' => 'script2.js', 'defer' => true],
            ['url' => 'script3.js', 'async' => true, 'defer' => true],
            'script4.js',  // No async/defer
        ];
        $component = new BassetAssets($assets);

        // Mock the basset helper function
        $this->app->bind('basset', function () {
            return function ($asset) {
                return "https://cdn.example.com/{$asset}";
            };
        });

        $urls = $component->getAssetUrls();

        $this->assertCount(4, $urls);
        $this->assertContains('https://cdn.example.com/script1.js', $urls);
        $this->assertContains('https://cdn.example.com/script2.js', $urls);
        $this->assertContains('https://cdn.example.com/script3.js', $urls);
        $this->assertContains('https://cdn.example.com/script4.js', $urls);
    }
}

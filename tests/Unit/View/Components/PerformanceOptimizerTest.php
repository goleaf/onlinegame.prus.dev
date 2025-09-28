<?php

namespace Tests\Unit\View\Components;

use App\View\Components\PerformanceOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Tests\TestCase;

class PerformanceOptimizerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_performance_optimizer_with_defaults()
    {
        $component = new PerformanceOptimizer();

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_can_create_performance_optimizer_with_custom_settings()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_can_create_performance_optimizer_with_partial_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_renders_correct_view()
    {
        $component = new PerformanceOptimizer();

        $view = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.performance-optimizer', $view->name());
    }

    /**
     * @test
     */
    public function it_handles_all_optimization_features_enabled()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_all_optimization_features_disabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_mixed_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_preconnect_only_enabled()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_dns_prefetch_only_enabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            true,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_performance_monitoring_only_enabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_service_worker_only_enabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_preconnect_and_dns_prefetch_enabled()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_performance_monitoring_and_service_worker_enabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_network_optimizations_enabled()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_advanced_features_enabled()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_balanced_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_aggressive_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_conservative_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_network_focused_optimization()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_monitoring_focused_optimization()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_hybrid_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_alternative_hybrid_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            true,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_three_way_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            false  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertFalse($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_alternative_three_way_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            false,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertFalse($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_another_alternative_three_way_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            false,  // enablePreconnect
            true,  // enableDnsPrefetch
            true,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertFalse($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertTrue($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }

    /**
     * @test
     */
    public function it_handles_final_alternative_three_way_optimization_settings()
    {
        $component = new PerformanceOptimizer(
            true,  // enablePreconnect
            true,  // enableDnsPrefetch
            false,  // enablePerformanceMonitoring
            true  // enableServiceWorker
        );

        $this->assertTrue($component->enablePreconnect);
        $this->assertTrue($component->enableDnsPrefetch);
        $this->assertFalse($component->enablePerformanceMonitoring);
        $this->assertTrue($component->enableServiceWorker);
    }
}

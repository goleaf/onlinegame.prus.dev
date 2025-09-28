<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PerformanceHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PerformanceHelperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_performance_helper()
    {
        $helper = new PerformanceHelper();

        $this->assertInstanceOf(PerformanceHelper::class, $helper);
    }

    /**
     * @test
     */
    public function it_can_measure_performance()
    {
        $helper = new PerformanceHelper();
        $result = $helper->measurePerformance(function () {
            return 'test';
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    /**
     * @test
     */
    public function it_can_measure_performance_with_callback()
    {
        $helper = new PerformanceHelper();
        $result = $helper->measurePerformance(function () {
            return 'test';
        }, function ($time) {
            return $time * 1000;  // Convert to milliseconds
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    /**
     * @test
     */
    public function it_can_measure_performance_with_exception()
    {
        $helper = new PerformanceHelper();
        $result = $helper->measurePerformance(function (): void {
            throw new \Exception('Test exception');
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exception', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    /**
     * @test
     */
    public function it_can_measure_performance_with_exception_callback()
    {
        $helper = new PerformanceHelper();
        $result = $helper->measurePerformance(function (): void {
            throw new \Exception('Test exception');
        }, null, function ($exception) {
            return $exception->getMessage();
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exception', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    /**
     * @test
     */
    public function it_can_measure_performance_with_both_callbacks()
    {
        $helper = new PerformanceHelper();
        $result = $helper->measurePerformance(function () {
            return 'test';
        }, function ($time) {
            return $time * 1000;
        }, function ($exception) {
            return $exception->getMessage();
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\AllianceSortService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceSortServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_sort()
    {
        $service = new AllianceSortService();
        $result = $service->getAllianceSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_sort_details()
    {
        $service = new AllianceSortService();
        $result = $service->getAllianceSortDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sort', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_sort_statistics()
    {
        $service = new AllianceSortService();
        $result = $service->getAllianceSortStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_sorts', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_sort_pagination()
    {
        $service = new AllianceSortService();
        $result = $service->getAllianceSortPagination(1, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

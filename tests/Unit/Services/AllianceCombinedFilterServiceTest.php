<?php

namespace Tests\Unit\Services;

use App\Services\AllianceCombinedFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceCombinedFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filters()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilters(1, [
            'status' => 'active',
            'type' => 'alliance',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filter_details()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilterDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('filter', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filter_statistics()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilterStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_filters', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filter_search()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilterSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filter_sort()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilterSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_combined_filter_pagination()
    {
        $service = new AllianceCombinedFilterService();
        $result = $service->getAllianceCombinedFilterPagination(1, 1, 10);

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

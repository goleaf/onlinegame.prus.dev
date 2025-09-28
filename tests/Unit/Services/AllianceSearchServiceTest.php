<?php

namespace Tests\Unit\Services;

use App\Services\AllianceSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_search()
    {
        $service = new AllianceSearchService();
        $result = $service->getAllianceSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_search_details()
    {
        $service = new AllianceSearchService();
        $result = $service->getAllianceSearchDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('search', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_search_statistics()
    {
        $service = new AllianceSearchService();
        $result = $service->getAllianceSearchStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_searches', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_search_sort()
    {
        $service = new AllianceSearchService();
        $result = $service->getAllianceSearchSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_search_pagination()
    {
        $service = new AllianceSearchService();
        $result = $service->getAllianceSearchPagination(1, 1, 10);

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

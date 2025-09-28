<?php

namespace Tests\Unit\Services;

use App\Services\AlliancePaginationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AlliancePaginationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_pagination()
    {
        $service = new AlliancePaginationService();
        $result = $service->getAlliancePagination(1, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_pagination_details()
    {
        $service = new AlliancePaginationService();
        $result = $service->getAlliancePaginationDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_pagination_statistics()
    {
        $service = new AlliancePaginationService();
        $result = $service->getAlliancePaginationStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_paginations', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

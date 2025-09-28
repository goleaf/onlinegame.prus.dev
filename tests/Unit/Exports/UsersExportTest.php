<?php

namespace Tests\Unit\Exports;

use App\Exports\UsersExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UsersExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_users_export()
    {
        $export = new UsersExport();

        $this->assertInstanceOf(UsersExport::class, $export);
    }

    /**
     * @test
     */
    public function it_can_export_users()
    {
        $export = new UsersExport();
        $result = $export->export();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_filters()
    {
        $export = new UsersExport();
        $result = $export->export(['status' => 'active']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_date_range()
    {
        $export = new UsersExport();
        $result = $export->export([
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_alliance_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['alliance_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_world_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['world_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_rank_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['rank' => 'admin']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_points_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['min_points' => 1000]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_last_activity_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['last_activity' => '7_days']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_users_with_created_at_filter()
    {
        $export = new UsersExport();
        $result = $export->export(['created_at' => '2024-01-01']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

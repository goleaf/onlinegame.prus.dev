<?php

namespace Tests\Unit\Exports;

use App\Exports\ReportsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_reports_export()
    {
        $export = new ReportsExport();

        $this->assertInstanceOf(ReportsExport::class, $export);
    }

    /**
     * @test
     */
    public function it_can_export_reports()
    {
        $export = new ReportsExport();
        $result = $export->export();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_filters()
    {
        $export = new ReportsExport();
        $result = $export->export(['type' => 'battle']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_date_range()
    {
        $export = new ReportsExport();
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
    public function it_can_export_reports_with_player_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['player_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_alliance_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['alliance_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_village_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['village_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_battle_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['battle_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_quest_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['quest_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_achievement_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['achievement_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_export_reports_with_artifact_filter()
    {
        $export = new ReportsExport();
        $result = $export->export(['artifact_id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

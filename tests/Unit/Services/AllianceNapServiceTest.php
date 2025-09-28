<?php

namespace Tests\Unit\Services;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceNap;
use App\Models\Game\Player;
use App\Services\AllianceNapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceNapServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_propose_nap()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $targetAlliance = Alliance::factory()->create();
        $data = [
            'alliance_id' => $alliance->id,
            'target_alliance_id' => $targetAlliance->id,
            'message' => 'Test nap proposal',
        ];

        $service = new AllianceNapService();
        $result = $service->proposeNap($player, $alliance, $targetAlliance, $data);

        $this->assertInstanceOf(AllianceNap::class, $result);
        $this->assertEquals($data['alliance_id'], $result->alliance_id);
        $this->assertEquals($data['target_alliance_id'], $result->target_alliance_id);
        $this->assertEquals($data['message'], $result->message);
    }

    /**
     * @test
     */
    public function it_can_accept_nap()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $nap = AllianceNap::factory()->create([
            'alliance_id' => $alliance->id,
            'status' => 'pending',
        ]);

        $service = new AllianceNapService();
        $result = $service->acceptNap($player, $alliance, $nap);

        $this->assertTrue($result);
        $this->assertEquals('accepted', $nap->status);
    }

    /**
     * @test
     */
    public function it_can_reject_nap()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $nap = AllianceNap::factory()->create([
            'alliance_id' => $alliance->id,
            'status' => 'pending',
        ]);

        $service = new AllianceNapService();
        $result = $service->rejectNap($player, $alliance, $nap);

        $this->assertTrue($result);
        $this->assertEquals('rejected', $nap->status);
    }

    /**
     * @test
     */
    public function it_can_end_nap()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $nap = AllianceNap::factory()->create([
            'alliance_id' => $alliance->id,
            'status' => 'active',
        ]);

        $service = new AllianceNapService();
        $result = $service->endNap($player, $alliance, $nap);

        $this->assertTrue($result);
        $this->assertEquals('ended', $nap->status);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_nap()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getAllianceNap($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_status()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'status' => 'active']),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'status' => 'ended']),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByStatus($alliance, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_creation_date()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'created_at' => now()]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'created_at' => now()->subDays(1)]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByCreationDate($alliance, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_end_date()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'ended_at' => now()]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'ended_at' => now()->subDays(1)]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByEndDate($alliance, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_duration()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'duration' => 3600]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'duration' => 7200]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByDuration($alliance, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_combined_filters()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'status' => 'active', 'duration' => 3600]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'status' => 'ended', 'duration' => 7200]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByCombinedFilters($alliance, [
            'status' => 'active',
            'duration' => 3600,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_search()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'message' => 'Test Nap']),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'message' => 'Another Nap']),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapBySearch($alliance, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_sort()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'duration' => 3600]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id, 'duration' => 7200]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapBySort($alliance, 'duration', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_by_pagination()
    {
        $alliance = Alliance::factory()->create();
        $nap = collect([
            AllianceNap::factory()->create(['alliance_id' => $alliance->id]),
            AllianceNap::factory()->create(['alliance_id' => $alliance->id]),
        ]);

        $alliance->shouldReceive('nap')->andReturn($nap);

        $service = new AllianceNapService();
        $result = $service->getNapByPagination($alliance, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_statistics()
    {
        $service = new AllianceNapService();
        $result = $service->getNapStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_nap', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_duration', $result);
    }

    /**
     * @test
     */
    public function it_can_get_nap_leaderboard()
    {
        $service = new AllianceNapService();
        $result = $service->getNapLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

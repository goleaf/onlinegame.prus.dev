<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Spy;
use App\Models\Game\Village;
use App\Services\SpyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SpyServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_send_spy()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'village_id' => $village->id,
            'target_x' => 100,
            'target_y' => 200,
            'spy_count' => 5,
        ];

        $service = new SpyService();
        $result = $service->sendSpy($player, $village, $data);

        $this->assertInstanceOf(Spy::class, $result);
        $this->assertEquals($data['village_id'], $result->village_id);
        $this->assertEquals($data['target_x'], $result->target_x);
        $this->assertEquals($data['target_y'], $result->target_y);
        $this->assertEquals($data['spy_count'], $result->spy_count);
    }

    /**
     * @test
     */
    public function it_can_cancel_spy()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $spy = Spy::factory()->create([
            'village_id' => $village->id,
            'status' => 'active',
        ]);

        $service = new SpyService();
        $result = $service->cancelSpy($player, $spy);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $spy->status);
    }

    /**
     * @test
     */
    public function it_can_complete_spy()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $spy = Spy::factory()->create([
            'village_id' => $village->id,
            'status' => 'active',
        ]);

        $service = new SpyService();
        $result = $service->completeSpy($player, $spy);

        $this->assertTrue($result);
        $this->assertEquals('completed', $spy->status);
    }

    /**
     * @test
     */
    public function it_can_get_village_spies()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id]),
            Spy::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getVillageSpies($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_status()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'status' => 'active']),
            Spy::factory()->create(['village_id' => $village->id, 'status' => 'completed']),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByStatus($village, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_creation_date()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'created_at' => now()]),
            Spy::factory()->create(['village_id' => $village->id, 'created_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByCreationDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_completion_date()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'completed_at' => now()]),
            Spy::factory()->create(['village_id' => $village->id, 'completed_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByCompletionDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_target_coordinates()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'target_x' => 100, 'target_y' => 200]),
            Spy::factory()->create(['village_id' => $village->id, 'target_x' => 300, 'target_y' => 400]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByTargetCoordinates($village, 100, 200);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_spy_count()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'spy_count' => 5]),
            Spy::factory()->create(['village_id' => $village->id, 'spy_count' => 10]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyBySpyCount($village, 5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_duration()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'duration' => 3600]),
            Spy::factory()->create(['village_id' => $village->id, 'duration' => 7200]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByDuration($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_distance()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'distance' => 100.0]),
            Spy::factory()->create(['village_id' => $village->id, 'distance' => 200.0]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByDistance($village, 100.0);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_speed()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'speed' => 10.0]),
            Spy::factory()->create(['village_id' => $village->id, 'speed' => 20.0]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyBySpeed($village, 10.0);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_combined_filters()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'status' => 'active', 'spy_count' => 5]),
            Spy::factory()->create(['village_id' => $village->id, 'status' => 'completed', 'spy_count' => 10]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByCombinedFilters($village, [
            'status' => 'active',
            'spy_count' => 5,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_search()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'description' => 'Test Spy']),
            Spy::factory()->create(['village_id' => $village->id, 'description' => 'Another Spy']),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyBySearch($village, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_sort()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id, 'duration' => 3600]),
            Spy::factory()->create(['village_id' => $village->id, 'duration' => 7200]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyBySort($village, 'duration', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_by_pagination()
    {
        $village = Village::factory()->create();
        $spies = collect([
            Spy::factory()->create(['village_id' => $village->id]),
            Spy::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('spies')->andReturn($spies);

        $service = new SpyService();
        $result = $service->getSpyByPagination($village, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_statistics()
    {
        $service = new SpyService();
        $result = $service->getSpyStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_spies', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_spy_count', $result);
    }

    /**
     * @test
     */
    public function it_can_get_spy_leaderboard()
    {
        $service = new SpyService();
        $result = $service->getSpyLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

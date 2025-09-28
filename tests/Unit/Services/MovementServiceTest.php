<?php

namespace Tests\Unit\Services;

use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MovementServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_movement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'village_id' => $village->id,
            'type' => 'attack',
            'target_x' => 100,
            'target_y' => 200,
            'units' => ['infantry' => 100],
        ];

        $service = new MovementService();
        $result = $service->createMovement($player, $village, $data);

        $this->assertInstanceOf(Movement::class, $result);
        $this->assertEquals($data['village_id'], $result->village_id);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['target_x'], $result->target_x);
        $this->assertEquals($data['target_y'], $result->target_y);
    }

    /**
     * @test
     */
    public function it_can_cancel_movement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $movement = Movement::factory()->create([
            'village_id' => $village->id,
            'status' => 'active',
        ]);

        $service = new MovementService();
        $result = $service->cancelMovement($player, $movement);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $movement->status);
    }

    /**
     * @test
     */
    public function it_can_complete_movement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $movement = Movement::factory()->create([
            'village_id' => $village->id,
            'status' => 'active',
        ]);

        $service = new MovementService();
        $result = $service->completeMovement($player, $movement);

        $this->assertTrue($result);
        $this->assertEquals('completed', $movement->status);
    }

    /**
     * @test
     */
    public function it_can_get_village_movements()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id]),
            Movement::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getVillageMovements($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_type()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'type' => 'attack']),
            Movement::factory()->create(['village_id' => $village->id, 'type' => 'raid']),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByType($village, 'attack');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_status()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'status' => 'active']),
            Movement::factory()->create(['village_id' => $village->id, 'status' => 'completed']),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByStatus($village, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_creation_date()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'created_at' => now()]),
            Movement::factory()->create(['village_id' => $village->id, 'created_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByCreationDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_completion_date()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'completed_at' => now()]),
            Movement::factory()->create(['village_id' => $village->id, 'completed_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByCompletionDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_target_coordinates()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'target_x' => 100, 'target_y' => 200]),
            Movement::factory()->create(['village_id' => $village->id, 'target_x' => 300, 'target_y' => 400]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByTargetCoordinates($village, 100, 200);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_units()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'units' => ['infantry' => 100]]),
            Movement::factory()->create(['village_id' => $village->id, 'units' => ['archer' => 50]]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByUnits($village, 'infantry', 100);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_duration()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'duration' => 3600]),
            Movement::factory()->create(['village_id' => $village->id, 'duration' => 7200]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByDuration($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_distance()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'distance' => 100.0]),
            Movement::factory()->create(['village_id' => $village->id, 'distance' => 200.0]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByDistance($village, 100.0);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_speed()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'speed' => 10.0]),
            Movement::factory()->create(['village_id' => $village->id, 'speed' => 20.0]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementBySpeed($village, 10.0);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_combined_filters()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'type' => 'attack', 'status' => 'active']),
            Movement::factory()->create(['village_id' => $village->id, 'type' => 'raid', 'status' => 'completed']),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByCombinedFilters($village, [
            'type' => 'attack',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_search()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'description' => 'Test Movement']),
            Movement::factory()->create(['village_id' => $village->id, 'description' => 'Another Movement']),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementBySearch($village, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_sort()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id, 'duration' => 3600]),
            Movement::factory()->create(['village_id' => $village->id, 'duration' => 7200]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementBySort($village, 'duration', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_by_pagination()
    {
        $village = Village::factory()->create();
        $movements = collect([
            Movement::factory()->create(['village_id' => $village->id]),
            Movement::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('movements')->andReturn($movements);

        $service = new MovementService();
        $result = $service->getMovementByPagination($village, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_statistics()
    {
        $service = new MovementService();
        $result = $service->getMovementStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_movements', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_movement_leaderboard()
    {
        $service = new MovementService();
        $result = $service->getMovementLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

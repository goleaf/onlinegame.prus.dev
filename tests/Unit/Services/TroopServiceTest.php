<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use App\Services\TroopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TroopServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_train_troop()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'village_id' => $village->id,
            'type' => 'infantry',
            'amount' => 100,
        ];

        $service = new TroopService();
        $result = $service->trainTroop($player, $village, $data);

        $this->assertInstanceOf(Troop::class, $result);
        $this->assertEquals($data['village_id'], $result->village_id);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['amount'], $result->amount);
    }

    /**
     * @test
     */
    public function it_can_upgrade_troop()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $troop = Troop::factory()->create([
            'village_id' => $village->id,
            'level' => 1,
        ]);

        $service = new TroopService();
        $result = $service->upgradeTroop($player, $troop);

        $this->assertTrue($result);
        $this->assertEquals(2, $troop->level);
    }

    /**
     * @test
     */
    public function it_can_dismiss_troop()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $troop = Troop::factory()->create([
            'village_id' => $village->id,
        ]);

        $service = new TroopService();
        $result = $service->dismissTroop($player, $troop);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_village_troops()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id]),
            Troop::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getVillageTroops($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_type()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'type' => 'infantry']),
            Troop::factory()->create(['village_id' => $village->id, 'type' => 'archer']),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByType($village, 'infantry');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_level()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'level' => 5]),
            Troop::factory()->create(['village_id' => $village->id, 'level' => 10]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByLevel($village, 5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_status()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'status' => 'active']),
            Troop::factory()->create(['village_id' => $village->id, 'status' => 'inactive']),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByStatus($village, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_training_date()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'trained_at' => now()]),
            Troop::factory()->create(['village_id' => $village->id, 'trained_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByTrainingDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_upgrade_date()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'upgraded_at' => now()]),
            Troop::factory()->create(['village_id' => $village->id, 'upgraded_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByUpgradeDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_dismissal_date()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'dismissed_at' => now()]),
            Troop::factory()->create(['village_id' => $village->id, 'dismissed_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByDismissalDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_training_cost()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'training_cost' => 1000]),
            Troop::factory()->create(['village_id' => $village->id, 'training_cost' => 2000]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByTrainingCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_upgrade_cost()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_cost' => 1000]),
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_cost' => 2000]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByUpgradeCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_dismissal_cost()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_cost' => 1000]),
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_cost' => 2000]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByDismissalCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_training_time()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'training_time' => 3600]),
            Troop::factory()->create(['village_id' => $village->id, 'training_time' => 7200]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByTrainingTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_upgrade_time()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_time' => 3600]),
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_time' => 7200]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByUpgradeTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_dismissal_time()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_time' => 3600]),
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_time' => 7200]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByDismissalTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_training_resources()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'training_resources' => ['wood' => 1000]]),
            Troop::factory()->create(['village_id' => $village->id, 'training_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByTrainingResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_upgrade_resources()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['wood' => 1000]]),
            Troop::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByUpgradeResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_dismissal_resources()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_resources' => ['wood' => 1000]]),
            Troop::factory()->create(['village_id' => $village->id, 'dismissal_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByDismissalResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_combined_filters()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'type' => 'infantry', 'level' => 5]),
            Troop::factory()->create(['village_id' => $village->id, 'type' => 'archer', 'level' => 10]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByCombinedFilters($village, [
            'type' => 'infantry',
            'level' => 5,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_search()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'name' => 'Test Troop']),
            Troop::factory()->create(['village_id' => $village->id, 'name' => 'Another Troop']),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopBySearch($village, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_sort()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id, 'level' => 5]),
            Troop::factory()->create(['village_id' => $village->id, 'level' => 10]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopBySort($village, 'level', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_by_pagination()
    {
        $village = Village::factory()->create();
        $troops = collect([
            Troop::factory()->create(['village_id' => $village->id]),
            Troop::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('troops')->andReturn($troops);

        $service = new TroopService();
        $result = $service->getTroopByPagination($village, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_statistics()
    {
        $service = new TroopService();
        $result = $service->getTroopStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_troops', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_level', $result);
    }

    /**
     * @test
     */
    public function it_can_get_troop_leaderboard()
    {
        $service = new TroopService();
        $result = $service->getTroopLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

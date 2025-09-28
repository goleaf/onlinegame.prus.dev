<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\VillageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VillageServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_village()
    {
        $player = Player::factory()->create();
        $data = [
            'name' => 'Test Village',
            'x_coordinate' => 100,
            'y_coordinate' => 200,
        ];

        $service = new VillageService();
        $result = $service->createVillage($player, $data);

        $this->assertInstanceOf(Village::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['x_coordinate'], $result->x_coordinate);
        $this->assertEquals($data['y_coordinate'], $result->y_coordinate);
        $this->assertEquals($player->id, $result->player_id);
    }

    /**
     * @test
     */
    public function it_can_upgrade_village()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $service = new VillageService();
        $result = $service->upgradeVillage($player, $village);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_destroy_village()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $service = new VillageService();
        $result = $service->destroyVillage($player, $village);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_player_villages()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id]),
            Village::factory()->create(['player_id' => $player->id]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getPlayerVillages($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_name()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Test Village']),
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Another Village']),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByName($player, 'Test Village');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_coordinates()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'x_coordinate' => 100, 'y_coordinate' => 200]),
            Village::factory()->create(['player_id' => $player->id, 'x_coordinate' => 300, 'y_coordinate' => 400]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByCoordinates($player, 100, 200);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_status()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'status' => 'active']),
            Village::factory()->create(['player_id' => $player->id, 'status' => 'inactive']),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByStatus($player, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_creation_date()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'created_at' => now()]),
            Village::factory()->create(['player_id' => $player->id, 'created_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByCreationDate($player, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_upgrade_date()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'upgraded_at' => now()]),
            Village::factory()->create(['player_id' => $player->id, 'upgraded_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByUpgradeDate($player, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_destruction_date()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'destroyed_at' => now()]),
            Village::factory()->create(['player_id' => $player->id, 'destroyed_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByDestructionDate($player, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_upgrade_cost()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'upgrade_cost' => 1000]),
            Village::factory()->create(['player_id' => $player->id, 'upgrade_cost' => 2000]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByUpgradeCost($player, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_destruction_cost()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'destruction_cost' => 1000]),
            Village::factory()->create(['player_id' => $player->id, 'destruction_cost' => 2000]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByDestructionCost($player, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_upgrade_time()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'upgrade_time' => 3600]),
            Village::factory()->create(['player_id' => $player->id, 'upgrade_time' => 7200]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByUpgradeTime($player, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_destruction_time()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'destruction_time' => 3600]),
            Village::factory()->create(['player_id' => $player->id, 'destruction_time' => 7200]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByDestructionTime($player, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_upgrade_resources()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'upgrade_resources' => ['wood' => 1000]]),
            Village::factory()->create(['player_id' => $player->id, 'upgrade_resources' => ['clay' => 500]]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByUpgradeResources($player, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_destruction_resources()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'destruction_resources' => ['wood' => 1000]]),
            Village::factory()->create(['player_id' => $player->id, 'destruction_resources' => ['clay' => 500]]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByDestructionResources($player, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_combined_filters()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Test Village', 'status' => 'active']),
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Another Village', 'status' => 'inactive']),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByCombinedFilters($player, [
            'name' => 'Test Village',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_search()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Test Village']),
            Village::factory()->create(['player_id' => $player->id, 'name' => 'Another Village']),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageBySearch($player, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_sort()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id, 'level' => 5]),
            Village::factory()->create(['player_id' => $player->id, 'level' => 10]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageBySort($player, 'level', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_pagination()
    {
        $player = Player::factory()->create();
        $villages = collect([
            Village::factory()->create(['player_id' => $player->id]),
            Village::factory()->create(['player_id' => $player->id]),
        ]);

        $player->shouldReceive('villages')->andReturn($villages);

        $service = new VillageService();
        $result = $service->getVillageByPagination($player, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_statistics()
    {
        $service = new VillageService();
        $result = $service->getVillageStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_villages', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_level', $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_leaderboard()
    {
        $service = new VillageService();
        $result = $service->getVillageLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

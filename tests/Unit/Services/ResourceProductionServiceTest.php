<?php

namespace Tests\Unit\Services;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\ResourceProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceProductionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_resource_production()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $buildingType = BuildingType::factory()->create([
            'production' => ['wood' => 10]
        ]);

        $building = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 2
        ]);

        $service = new ResourceProductionService();
        $production = $service->calculateResourceProduction($village);

        $this->assertEquals(10, $production['wood']);
    }

    public function test_updates_village_resources()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10
        ]);

        $service = new ResourceProductionService();
        $service->updateVillageResources($village);

        $resource->refresh();
        $this->assertGreaterThan(1000, $resource->amount);
    }

    public function test_calculates_storage_capacity()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $warehouseType = BuildingType::factory()->create(['key' => 'warehouse']);
        $warehouse = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $warehouseType->id,
            'level' => 2
        ]);

        $service = new ResourceProductionService();
        $capacities = $service->calculateStorageCapacity($village);

        $this->assertGreaterThan(1000, $capacities['wood']);
    }

    public function test_can_afford_resources()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $service = new ResourceProductionService();

        $this->assertTrue($service->canAfford($village, ['wood' => 500]));
        $this->assertFalse($service->canAfford($village, ['wood' => 1500]));
    }

    public function test_spends_resources()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $service = new ResourceProductionService();

        $this->assertTrue($service->spendResources($village, ['wood' => 500]));

        $resource->refresh();
        $this->assertEquals(500, $resource->amount);
    }

    public function test_adds_resources()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'storage_capacity' => 2000
        ]);

        $service = new ResourceProductionService();
        $service->addResources($village, ['wood' => 500]);

        $resource->refresh();
        $this->assertEquals(1500, $resource->amount);
    }
}


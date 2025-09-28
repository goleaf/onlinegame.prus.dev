<?php

namespace Tests\Unit\Services;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Services\ResourceProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceProductionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResourceProductionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResourceProductionService();
    }

    /**
     * @test
     */
    public function it_can_calculate_resource_production()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create building types with production
        $woodcutter = BuildingType::factory()->create([
            'key' => 'woodcutter',
            'production' => json_encode(['wood' => 10]),
        ]);
        $clayPit = BuildingType::factory()->create([
            'key' => 'clay_pit',
            'production' => json_encode(['clay' => 8]),
        ]);

        // Create buildings
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $woodcutter->id,
            'level' => 3,
        ]);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $clayPit->id,
            'level' => 2,
        ]);

        $productionRates = $this->service->calculateResourceProduction($village);

        $this->assertIsArray($productionRates);
        $this->assertArrayHasKey('wood', $productionRates);
        $this->assertArrayHasKey('clay', $productionRates);
        $this->assertArrayHasKey('iron', $productionRates);
        $this->assertArrayHasKey('crop', $productionRates);

        // Wood: 10 * 1.1^2 = 12.1
        $this->assertEquals(12.1, $productionRates['wood']);
        // Clay: 8 * 1.1^1 = 8.8
        $this->assertEquals(8.8, $productionRates['clay']);
        $this->assertEquals(0, $productionRates['iron']);
        $this->assertEquals(0, $productionRates['crop']);
    }

    /**
     * @test
     */
    public function it_calculates_production_rate_correctly()
    {
        $baseRate = 100;
        $level = 5;

        $rate = $this->service->calculateProductionRate($baseRate, $level);

        // 100 * 1.1^4 = 146.41
        $this->assertEquals(146.41, $rate);
    }

    /**
     * @test
     */
    public function it_can_update_village_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'last_updated' => now()->subSeconds(3600),  // 1 hour ago
        ]);

        // Create building with wood production
        $woodcutter = BuildingType::factory()->create([
            'key' => 'woodcutter',
            'production' => json_encode(['wood' => 10]),
        ]);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $woodcutter->id,
            'level' => 1,
        ]);

        $this->service->updateVillageResources($village);

        // Should have produced 10 wood (10 * 3600 seconds / 3600 seconds per hour)
        $this->assertEquals(1010, $woodResource->fresh()->amount);
    }

    /**
     * @test
     */
    public function it_respects_storage_capacity_when_updating_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resource at capacity
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 2000,
            'storage_capacity' => 2000,
            'last_updated' => now()->subSeconds(3600),
        ]);

        // Create building with high wood production
        $woodcutter = BuildingType::factory()->create([
            'key' => 'woodcutter',
            'production' => json_encode(['wood' => 1000]),
        ]);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $woodcutter->id,
            'level' => 1,
        ]);

        $this->service->updateVillageResources($village);

        // Should not exceed storage capacity
        $this->assertEquals(2000, $woodResource->fresh()->amount);
    }

    /**
     * @test
     */
    public function it_can_calculate_storage_capacity()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create warehouse
        $warehouse = BuildingType::factory()->create(['key' => 'warehouse']);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $warehouse->id,
            'level' => 3,
        ]);

        // Create granary
        $granary = BuildingType::factory()->create(['key' => 'granary']);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $granary->id,
            'level' => 2,
        ]);

        $capacities = $this->service->calculateStorageCapacity($village);

        $this->assertIsArray($capacities);
        $this->assertArrayHasKey('wood', $capacities);
        $this->assertArrayHasKey('clay', $capacities);
        $this->assertArrayHasKey('iron', $capacities);
        $this->assertArrayHasKey('crop', $capacities);

        // Base capacity: 1000, Warehouse level 3: +3000 each for wood/clay/iron
        $this->assertEquals(4000, $capacities['wood']);
        $this->assertEquals(4000, $capacities['clay']);
        $this->assertEquals(4000, $capacities['iron']);
        // Base capacity: 1000, Granary level 2: +2000 for crop
        $this->assertEquals(3000, $capacities['crop']);
    }

    /**
     * @test
     */
    public function it_can_update_storage_capacities()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'storage_capacity' => 1000,
        ]);

        // Create warehouse
        $warehouse = BuildingType::factory()->create(['key' => 'warehouse']);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $warehouse->id,
            'level' => 2,
        ]);

        $this->service->updateStorageCapacities($village);

        // Should update to 3000 (1000 base + 2000 from level 2 warehouse)
        $this->assertEquals(3000, $woodResource->fresh()->storage_capacity);
    }

    /**
     * @test
     */
    public function it_can_check_if_village_can_afford_costs()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 500,
        ]);

        $costs = ['wood' => 800, 'clay' => 400];

        $canAfford = $this->service->canAfford($village, $costs);

        $this->assertTrue($canAfford);
    }

    /**
     * @test
     */
    public function it_returns_false_when_village_cannot_afford_costs()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 500,
        ]);

        $costs = ['wood' => 1200, 'clay' => 400];  // Not enough wood

        $canAfford = $this->service->canAfford($village, $costs);

        $this->assertFalse($canAfford);
    }

    /**
     * @test
     */
    public function it_can_spend_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);
        $clayResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 500,
        ]);

        $costs = ['wood' => 300, 'clay' => 200];

        $result = $this->service->spendResources($village, $costs);

        $this->assertTrue($result);
        $this->assertEquals(700, $woodResource->fresh()->amount);
        $this->assertEquals(300, $clayResource->fresh()->amount);
    }

    /**
     * @test
     */
    public function it_returns_false_when_spending_resources_that_cannot_be_afforded()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);

        $costs = ['wood' => 1200];  // More than available

        $result = $this->service->spendResources($village, $costs);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_add_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resources
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'storage_capacity' => 2000,
        ]);

        $amounts = ['wood' => 500];

        $this->service->addResources($village, $amounts);

        $this->assertEquals(1500, $woodResource->fresh()->amount);
    }

    /**
     * @test
     */
    public function it_respects_storage_capacity_when_adding_resources()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create resource near capacity
        $woodResource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1800,
            'storage_capacity' => 2000,
        ]);

        $amounts = ['wood' => 500];  // Would exceed capacity

        $this->service->addResources($village, $amounts);

        // Should cap at storage capacity
        $this->assertEquals(2000, $woodResource->fresh()->amount);
    }

    /**
     * @test
     */
    public function it_handles_missing_resource_types_gracefully()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create only wood resource
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);

        $costs = ['wood' => 500, 'clay' => 300];  // Clay doesn't exist

        $canAfford = $this->service->canAfford($village, $costs);

        $this->assertFalse($canAfford);
    }

    /**
     * @test
     */
    public function it_handles_empty_buildings_gracefully()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // No buildings created

        $productionRates = $this->service->calculateResourceProduction($village);

        $this->assertEquals(0, $productionRates['wood']);
        $this->assertEquals(0, $productionRates['clay']);
        $this->assertEquals(0, $productionRates['iron']);
        $this->assertEquals(0, $productionRates['crop']);
    }

    /**
     * @test
     */
    public function it_handles_buildings_without_production_gracefully()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Create building without production
        $buildingType = BuildingType::factory()->create([
            'key' => 'main_building',
            'production' => null,
        ]);
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ]);

        $productionRates = $this->service->calculateResourceProduction($village);

        $this->assertEquals(0, $productionRates['wood']);
        $this->assertEquals(0, $productionRates['clay']);
        $this->assertEquals(0, $productionRates['iron']);
        $this->assertEquals(0, $productionRates['crop']);
    }
}

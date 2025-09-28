<?php

namespace Tests\Feature;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Services\GameTickService;
use App\Services\ResourceProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;

    protected $player;

    protected $world;

    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and player
        $this->user = User::factory()->create();
        $this->world = World::factory()->create();
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
        ]);
        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'is_capital' => true,
        ]);

        // Create resources for the village
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
        foreach ($resourceTypes as $type) {
            Resource::create([
                'village_id' => $this->village->id,
                'type' => $type,
                'amount' => 1000,
                'production_rate' => 100,
                'storage_capacity' => 10000,
                'level' => 1,
                'last_updated' => now(),
            ]);
        }
    }

    public function test_user_can_access_game_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/game');

        $response->assertStatus(200);
        $response->assertSee('Game Dashboard');
    }

    public function test_user_cannot_access_game_without_player()
    {
        $this->player->delete();

        $response = $this->actingAs($this->user)->get('/game');

        $response->assertRedirect('/game/no-player');
    }

    public function test_village_has_resources()
    {
        $this->assertCount(4, $this->village->resources);

        $resourceTypes = $this->village->resources->pluck('type')->toArray();
        $this->assertContains('wood', $resourceTypes);
        $this->assertContains('clay', $resourceTypes);
        $this->assertContains('iron', $resourceTypes);
        $this->assertContains('crop', $resourceTypes);
    }

    public function test_resource_production_calculation()
    {
        $resourceService = app(ResourceProductionService::class);
        $productionRates = $resourceService->calculateResourceProduction($this->village);

        $this->assertIsArray($productionRates);
        $this->assertArrayHasKey('wood', $productionRates);
        $this->assertArrayHasKey('clay', $productionRates);
        $this->assertArrayHasKey('iron', $productionRates);
        $this->assertArrayHasKey('crop', $productionRates);
    }

    public function test_building_creation()
    {
        $buildingType = BuildingType::factory()->create();

        $building = Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'name' => $buildingType->name,
            'level' => 1,
            'x' => 5,
            'y' => 5,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('buildings', [
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ]);
    }

    public function test_building_upgrade()
    {
        $buildingType = BuildingType::factory()->create();
        $building = Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'name' => $buildingType->name,
            'level' => 1,
            'x' => 5,
            'y' => 5,
            'is_active' => true,
        ]);

        $building->update(['level' => 2]);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'level' => 2,
        ]);
    }

    public function test_game_tick_service()
    {
        $gameTickService = app(GameTickService::class);

        // Test that game tick can be processed without errors
        $this->expectNotToPerformAssertions();
        $gameTickService->processGameTick();
    }

    public function test_resource_production_service()
    {
        $resourceService = app(ResourceProductionService::class);

        // Test resource production calculation
        $productionRates = $resourceService->calculateResourceProduction($this->village);
        $this->assertIsArray($productionRates);

        // Test storage capacity calculation
        $capacities = $resourceService->calculateStorageCapacity($this->village);
        $this->assertIsArray($capacities);

        // Test resource spending
        $costs = ['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 10];
        $canAfford = $resourceService->canAfford($this->village, $costs);
        $this->assertIsBool($canAfford);
    }

    public function test_village_coordinates()
    {
        $this->assertEquals("({$this->village->x_coordinate}|{$this->village->y_coordinate})", $this->village->coordinates);
    }

    public function test_player_statistics()
    {
        $this->assertGreaterThanOrEqual(0, $this->player->points);
        $this->assertGreaterThanOrEqual(0, $this->player->population);
        $this->assertGreaterThanOrEqual(0, $this->player->villages_count);
    }

    public function test_world_creation()
    {
        $world = World::factory()->create([
            'name' => 'Test World',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('worlds', [
            'name' => 'Test World',
            'is_active' => true,
        ]);
    }

    public function test_building_type_creation()
    {
        $buildingType = BuildingType::factory()->create([
            'name' => 'Test Building',
            'key' => 'test_building',
            'max_level' => 20,
        ]);

        $this->assertDatabaseHas('building_types', [
            'name' => 'Test Building',
            'key' => 'test_building',
            'max_level' => 20,
        ]);
    }

    public function test_resource_storage_capacity()
    {
        $resource = $this->village->resources->first();
        $originalCapacity = $resource->storage_capacity;

        $resource->update(['storage_capacity' => $originalCapacity + 1000]);

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'storage_capacity' => $originalCapacity + 1000,
        ]);
    }

    public function test_village_population()
    {
        $originalPopulation = $this->village->population;

        $this->village->update(['population' => $originalPopulation + 100]);

        $this->assertDatabaseHas('villages', [
            'id' => $this->village->id,
            'population' => $originalPopulation + 100,
        ]);
    }

    public function test_player_alliance_relationship()
    {
        $this->assertNull($this->player->alliance);

        // Test that player can have an alliance
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $this->player->alliance());
    }

    public function test_village_buildings_relationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $this->village->buildings());
    }

    public function test_village_resources_relationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $this->village->resources());
    }

    public function test_player_villages_relationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $this->player->villages());
    }

    public function test_game_models_have_factories()
    {
        $this->assertTrue(class_exists('Database\Factories\PlayerFactory'));
        $this->assertTrue(class_exists('Database\Factories\VillageFactory'));
        $this->assertTrue(class_exists('Database\Factories\WorldFactory'));
        $this->assertTrue(class_exists('Database\Factories\BuildingFactory'));
        $this->assertTrue(class_exists('Database\Factories\ResourceFactory'));
    }

    public function test_database_seeding()
    {
        $this->artisan('db:seed', ['--class' => 'GameSeeder']);

        $this->assertDatabaseHas('worlds', ['name' => 'Travian World 1']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseHas('building_types', ['key' => 'main_building']);
        $this->assertDatabaseHas('unit_types', ['key' => 'legionnaire']);
    }
}

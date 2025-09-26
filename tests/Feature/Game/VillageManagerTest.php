<?php

namespace Tests\Feature\Game;

use App\Livewire\Game\VillageManager;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VillageManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_village_manager_loads_village_data()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(VillageManager::class, ['village' => $village->id])
            ->assertStatus(200)
            ->assertSee($village->name);
    }

    public function test_village_manager_shows_buildings()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $buildingType = BuildingType::factory()->create();
        $building = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
        ]);

        $this->actingAs($user);

        Livewire::test(VillageManager::class, ['village' => $village->id])
            ->assertSee($building->name);
    }

    public function test_village_manager_selects_building()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $buildingType = BuildingType::factory()->create();
        $building = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
        ]);

        $this->actingAs($user);

        Livewire::test(VillageManager::class, ['village' => $village->id])
            ->call('selectBuilding', $building->id)
            ->assertSet('selectedBuilding.id', $building->id);
    }

    public function test_village_manager_calculates_upgrade_cost()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $buildingType = BuildingType::factory()->create([
            'costs' => ['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100],
        ]);

        $building = Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ]);

        $this->actingAs($user);

        Livewire::test(VillageManager::class, ['village' => $village->id])
            ->call('selectBuilding', $building->id)
            ->assertSet('upgradeLevel', 2);
    }

    public function test_village_manager_refreshes_village()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(VillageManager::class, ['village' => $village->id])
            ->call('refreshVillage')
            ->assertSee('Village data refreshed');
    }
}

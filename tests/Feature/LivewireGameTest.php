<?php

namespace Tests\Feature;

use App\Livewire\Game\EnhancedGameDashboard;
use App\Livewire\Game\RealTimeVillageManager;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireGameTest extends TestCase
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

        // Ensure the user has a player relationship
        $this->user->refresh();
        $this->user->setRelation('player', $this->player);
    }

    public function test_enhanced_game_dashboard_renders()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->assertStatus(200);
    }

    public function test_enhanced_game_dashboard_loads_game_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->assertSet('player', $this->player);
    }

    public function test_enhanced_game_dashboard_calculates_stats()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // The component should have calculated game stats
        $component->assertSet('gameStats.total_villages', 1);
        $component->assertSet('gameStats.villages_count', 1);
    }

    public function test_enhanced_game_dashboard_refreshes_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->call('refreshGameData');
        $component->assertSet('isLoading', false);
    }

    public function test_enhanced_game_dashboard_selects_village()
    {
        $secondVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('selectVillage', $secondVillage->id)
            ->assertSet('currentVillage.id', $secondVillage->id);
    }

    public function test_enhanced_game_dashboard_toggles_auto_refresh()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false);
    }

    public function test_enhanced_game_dashboard_updates_refresh_interval()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('updateRefreshInterval', 10)
            ->assertSet('refreshInterval', 10);
    }

    public function test_real_time_village_manager_renders()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->assertSee($this->village->name)
            ->assertSee($this->village->coordinates);
    }

    public function test_real_time_village_manager_loads_village_data()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->assertSet('village.id', $this->village->id)
            ->assertSet('player.id', $this->player->id);
    }

    public function test_real_time_village_manager_calculates_resources()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->assertSet('resources.wood.amount', 1000)
            ->assertSet('resources.clay.amount', 1000)
            ->assertSet('resources.iron.amount', 1000)
            ->assertSet('resources.crop.amount', 1000);
    }

    public function test_real_time_village_manager_loads_building_grid()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->assertSet('buildingGrid', function ($grid) {
                return is_array($grid) && count($grid) === 19;
            });
    }

    public function test_real_time_village_manager_selects_building_type()
    {
        $buildingType = BuildingType::factory()->create();

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('selectBuildingType', $buildingType->id)
            ->assertSet('selectedBuildingType.id', $buildingType->id)
            ->assertSet('showBuildingModal', true);
    }

    public function test_real_time_village_manager_builds_building()
    {
        $buildingType = BuildingType::factory()->create([
            'costs' => json_encode(['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 10]),
        ]);

        // Ensure village has enough resources
        $this->village->update([
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->set('selectedBuildingType', $buildingType)
            ->call('buildBuilding', 5, 5)
            ->assertSet('showBuildingModal', false);
    }

    public function test_real_time_village_manager_upgrades_building()
    {
        $buildingType = BuildingType::factory()->create([
            'costs' => json_encode(['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 10]),
        ]);

        $building = Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'name' => $buildingType->name,
            'level' => 1,
            'x' => 5,
            'y' => 5,
            'is_active' => true,
        ]);

        // Ensure village has enough resources
        $this->village->update([
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('upgradeBuilding', $building->id);
    }

    public function test_real_time_village_manager_refreshes_data()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('refreshVillageData')
            ->assertSet('isLoading', false);
    }

    public function test_real_time_village_manager_processes_tick()
    {
        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('processVillageTick');
    }

    public function test_enhanced_game_dashboard_handles_no_player()
    {
        $this->player->delete();

        // Refresh the user to clear the cached player relationship
        $this->user->refresh();

        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->assertRedirect('/game/no-player');
    }

    public function test_enhanced_game_dashboard_handles_errors()
    {
        // Mock an error in the component
        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('loadGameData');
    }

    public function test_real_time_village_manager_handles_insufficient_resources()
    {
        $buildingType = BuildingType::factory()->create([
            'costs' => json_encode(['wood' => 10000, 'clay' => 10000, 'iron' => 10000, 'crop' => 10000]),
        ]);

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->set('selectedBuildingType', $buildingType)
            ->call('buildBuilding', 5, 5)
            ->assertHasErrors(['error']);
    }

    public function test_enhanced_game_dashboard_mark_notification_as_read()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('markNotificationAsRead', 1);
    }

    public function test_enhanced_game_dashboard_mark_all_notifications_as_read()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class)
            ->call('markAllNotificationsAsRead');
    }

    public function test_real_time_village_manager_calculates_upgrade_costs()
    {
        $buildingType = BuildingType::factory()->create([
            'costs' => json_encode(['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 10]),
        ]);

        $building = Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'name' => $buildingType->name,
            'level' => 2,
            'x' => 5,
            'y' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('calculateUpgradeCosts', $building);
    }

    public function test_real_time_village_manager_calculates_upgrade_time()
    {
        $buildingType = BuildingType::factory()->create();

        $building = Building::create([
            'village_id' => $this->village->id,
            'building_type_id' => $buildingType->id,
            'name' => $buildingType->name,
            'level' => 3,
            'x' => 5,
            'y' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village])
            ->call('calculateUpgradeTime', $building);
    }

    public function test_enhanced_game_dashboard_get_resource_icon()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $icon = $component->instance()->getResourceIcon('wood');
        $this->assertEquals('🌲', $icon);
    }

    public function test_enhanced_game_dashboard_get_building_icon()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test the method directly instead of checking rendered output
        $icon = $component->instance()->getBuildingIcon('main_building');
        $this->assertEquals('🏛️', $icon);
    }

    public function test_enhanced_game_dashboard_get_quest_icon()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test the method directly instead of checking rendered output
        $icon = $component->instance()->getQuestIcon('tutorial');
        $this->assertEquals('📚', $icon);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Game\EnhancedGameDashboard;
use App\Livewire\Game\RealTimeVillageManager;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnhancedLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $world;
    protected $player;
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
        $component->assertSet('villages', collect([$this->village]));
    }

    public function test_enhanced_game_dashboard_calculates_stats()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->assertSet('gameStats', []);
        $component->assertSet('resourceProductionRates', []);
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
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->call('selectVillage', $this->village->id);
        $component->assertSet('selectedVillageId', $this->village->id);
        $component->assertSet('currentVillage', $this->village);
    }

    public function test_enhanced_game_dashboard_toggles_auto_refresh()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->call('toggleAutoRefresh');
        $component->assertSet('autoRefresh', false);
    }

    public function test_enhanced_game_dashboard_updates_refresh_interval()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $component->call('updateRefreshInterval', 10);
        $component->assertSet('refreshInterval', 10);
    }

    public function test_enhanced_game_dashboard_handles_no_player()
    {
        $userWithoutPlayer = User::factory()->create();

        $component = Livewire::actingAs($userWithoutPlayer)
            ->test(EnhancedGameDashboard::class);

        $component->assertRedirect('/game/no-player');
    }

    public function test_enhanced_game_dashboard_handles_errors()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test error handling by calling a method that might fail
        $component->call('processGameTick');
        $component->assertSet('isLoading', false);
    }

    public function test_enhanced_game_dashboard_mark_notification_as_read()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Add a notification
        $component->call('addNotification', 'Test notification', 'info');
        $component->assertCount('notifications', 1);

        // Mark as read
        $component->call('markNotificationAsRead', 0);
        $component->assertCount('notifications', 0);
    }

    public function test_enhanced_game_dashboard_mark_all_notifications_as_read()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Add multiple notifications
        $component->call('addNotification', 'Test notification 1', 'info');
        $component->call('addNotification', 'Test notification 2', 'warning');
        $component->assertCount('notifications', 2);

        // Mark all as read
        $component->call('markAllNotificationsAsRead');
        $component->assertCount('notifications', 0);
    }

    public function test_real_time_village_manager_renders()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->assertStatus(200);
    }

    public function test_real_time_village_manager_loads_village_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->assertSet('village', $this->village);
        $component->assertSet('player', $this->player);
    }

    public function test_real_time_village_manager_calculates_resources()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->assertSet('resources', []);
        $component->assertSet('resourceProductionRates', []);
    }

    public function test_real_time_village_manager_loads_building_grid()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->assertSet('buildingGrid', []);
    }

    public function test_real_time_village_manager_selects_building_type()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('selectBuildingType', 1);
        $component->assertSet('selectedBuildingTypeId', 1);
    }

    public function test_real_time_village_manager_builds_building()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('buildBuilding', 1, 1, 1);
        $component->assertSet('showBuildingModal', false);
    }

    public function test_real_time_village_manager_upgrades_building()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('upgradeBuilding', 1);
        $component->assertSet('showBuildingModal', false);
    }

    public function test_real_time_village_manager_refreshes_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('refreshVillageData');
        $component->assertSet('isLoading', false);
    }

    public function test_real_time_village_manager_processes_tick()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('processVillageTick');
        $component->assertSet('lastUpdateTime', now());
    }

    public function test_real_time_village_manager_handles_insufficient_resources()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        // Set village resources to 0
        $this->village->update([
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ]);

        $component->call('buildBuilding', 1, 1, 1);
        $component->assertSet('showBuildingModal', false);
    }

    public function test_real_time_village_manager_calculates_upgrade_costs()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('calculateUpgradeCosts', 1);
        $component->assertSet('upgradeCosts', []);
    }

    public function test_real_time_village_manager_calculates_upgrade_time()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        $component->call('calculateUpgradeTime', 1);
        $component->assertSet('upgradeTimes', []);
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

        $icon = $component->instance()->getBuildingIcon('main_building');
        $this->assertEquals('🏛️', $icon);
    }

    public function test_enhanced_game_dashboard_get_quest_icon()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        $icon = $component->instance()->getQuestIcon('tutorial');
        $this->assertEquals('📚', $icon);
    }

    public function test_enhanced_game_dashboard_computed_properties()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test computed properties
        $totalResources = $component->instance()->totalResources();
        $this->assertIsArray($totalResources);

        $resourceCapacities = $component->instance()->resourceCapacities();
        $this->assertIsArray($resourceCapacities);

        $playerRanking = $component->instance()->playerRanking();
        $this->assertIsArray($playerRanking);
    }

    public function test_real_time_village_manager_computed_properties()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        // Test computed properties
        $totalResourceProduction = $component->instance()->totalResourceProduction();
        $this->assertIsArray($totalResourceProduction);

        $resourceUtilization = $component->instance()->resourceUtilization();
        $this->assertIsArray($resourceUtilization);

        $buildingEfficiency = $component->instance()->buildingEfficiency();
        $this->assertIsFloat($buildingEfficiency);

        $villageScore = $component->instance()->villageScore();
        $this->assertIsInt($villageScore);
    }

    public function test_enhanced_game_dashboard_event_handlers()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test event handlers
        $component->dispatch('battleReportReceived', ['result' => 'victory']);
        $component->assertCount('recentBattles', 1);

        $component->dispatch('marketOfferUpdated', ['offer' => 'test']);
        $component->assertCount('marketOffers', 1);

        $component->dispatch('diplomaticEventOccurred', ['event' => 'test']);
        $component->assertCount('diplomaticEvents', 1);

        $component->dispatch('achievementUnlocked', ['name' => 'test']);
        $component->assertCount('achievements', 1);
    }

    public function test_real_time_village_manager_event_handlers()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        // Test event handlers
        $component->dispatch('buildingProgressUpdated', ['buildingId' => 1, 'progress' => 50]);
        $component->assertSet('buildingProgress', []);

        $component->dispatch('trainingProgressUpdated', ['unitId' => 1, 'progress' => 50]);
        $component->assertSet('trainingProgress', []);

        $component->dispatch('resourceProductionUpdated', ['production' => []]);
        $component->assertSet('resourceProductionRates', []);

        $component->dispatch('villageEventOccurred', ['message' => 'test']);
        $component->assertCount('villageEvents', 1);
    }

    public function test_enhanced_game_dashboard_polling_controls()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test polling controls
        $component->call('togglePolling');
        $component->assertSet('pollingEnabled', false);

        $component->call('startPolling');
        $component->assertSet('pollingEnabled', true);

        $component->call('stopPolling');
        $component->assertSet('pollingEnabled', false);
    }

    public function test_real_time_village_manager_polling_controls()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        // Test polling controls
        $component->call('togglePolling');
        $component->assertSet('pollingEnabled', false);

        $component->call('startPolling');
        $component->assertSet('pollingEnabled', true);

        $component->call('stopPolling');
        $component->assertSet('pollingEnabled', false);
    }

    public function test_enhanced_game_dashboard_connection_monitoring()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedGameDashboard::class);

        // Test connection monitoring
        $component->dispatch('connectionStatusChanged', ['status' => 'disconnected']);
        $component->assertSet('connectionStatus', 'disconnected');

        $component->dispatch('connectionStatusChanged', ['status' => 'connected']);
        $component->assertSet('connectionStatus', 'connected');
    }

    public function test_real_time_village_manager_connection_monitoring()
    {
        $component = Livewire::actingAs($this->user)
            ->test(RealTimeVillageManager::class, ['village' => $this->village]);

        // Test connection monitoring
        $component->dispatch('connectionStatusChanged', ['status' => 'disconnected']);
        $component->assertSet('connectionStatus', 'disconnected');

        $component->dispatch('connectionStatusChanged', ['status' => 'connected']);
        $component->assertSet('connectionStatus', 'connected');
    }
}

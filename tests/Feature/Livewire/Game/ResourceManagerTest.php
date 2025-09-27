<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\ResourceManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourceManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
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
    }

    public function test_can_render_resource_manager()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertStatus(200)
            ->assertSee('Resource Manager');
    }

    public function test_loads_resources_on_mount()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('village', $village)
            ->assertSet('resources.wood', 0)
            ->assertSet('resources.clay', 0)
            ->assertSet('resources.iron', 0)
            ->assertSet('resources.crop', 0);
    }

    public function test_calculates_production_rates()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        $component
            ->assertSet('productionRates.wood', 10)
            ->assertSet('productionRates.clay', 10)
            ->assertSet('productionRates.iron', 10)
            ->assertSet('productionRates.crop', 10);
    }

    public function test_can_toggle_real_time_updates()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_update()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('autoUpdate', true)
            ->call('toggleAutoUpdate')
            ->assertSet('autoUpdate', false)
            ->call('toggleAutoUpdate')
            ->assertSet('autoUpdate', true);
    }

    public function test_can_set_update_interval()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('updateInterval', 5)
            ->call('setUpdateInterval', 10)
            ->assertSet('updateInterval', 10)
            ->call('setUpdateInterval', 0)
            ->assertSet('updateInterval', 1)
            ->call('setUpdateInterval', 100)
            ->assertSet('updateInterval', 60);
    }

    public function test_can_set_game_speed()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_resource()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('selectedResource', null)
            ->assertSet('showDetails', false)
            ->call('selectResource', 'wood')
            ->assertSet('selectedResource', 'wood')
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_get_resource_icon()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        $this->assertEquals('🌲', $component->instance()->getResourceIcon('wood'));
        $this->assertEquals('🏺', $component->instance()->getResourceIcon('clay'));
        $this->assertEquals('⚒️', $component->instance()->getResourceIcon('iron'));
        $this->assertEquals('🌾', $component->instance()->getResourceIcon('crop'));
        $this->assertEquals('📦', $component->instance()->getResourceIcon('unknown'));
    }

    public function test_get_resource_color()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        $this->assertEquals('green', $component->instance()->getResourceColor('wood'));
        $this->assertEquals('orange', $component->instance()->getResourceColor('clay'));
        $this->assertEquals('gray', $component->instance()->getResourceColor('iron'));
        $this->assertEquals('yellow', $component->instance()->getResourceColor('crop'));
        $this->assertEquals('blue', $component->instance()->getResourceColor('unknown'));
    }

    public function test_get_resource_percentage()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        // Test with default values (0 amount, 800 capacity)
        $this->assertEquals(0, $component->instance()->getResourcePercentage('wood'));

        // Test with custom values
        $component->set('resources.wood', 400);
        $component->set('capacities.wood', 800);
        $this->assertEquals(50, $component->instance()->getResourcePercentage('wood'));

        // Test with full capacity
        $component->set('resources.wood', 800);
        $this->assertEquals(100, $component->instance()->getResourcePercentage('wood'));

        // Test with over capacity (should be capped at 100)
        $component->set('resources.wood', 1000);
        $this->assertEquals(100, $component->instance()->getResourcePercentage('wood'));
    }

    public function test_get_time_to_full()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        // Test with zero production
        $component->set('productionRates.wood', 0);
        $this->assertEquals('∞', $component->instance()->getTimeToFull('wood'));

        // Test with normal production
        $component->set('resources.wood', 0);
        $component->set('capacities.wood', 800);
        $component->set('productionRates.wood', 10);
        $this->assertEquals('00:01:20', $component->instance()->getTimeToFull('wood'));
    }

    public function test_notification_system()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_calculates_storage_warnings()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        // Test with normal levels (no warnings)
        $component->set('resources.wood', 100);
        $component->set('capacities.wood', 800);
        $component->call('calculateStorageWarnings');
        $component->assertSet('storageWarnings', []);

        // Test with warning level (75%)
        $component->set('resources.wood', 600);
        $component->call('calculateStorageWarnings');
        $component->assertSet('storageWarnings.wood.level', 'warning');

        // Test with critical level (90%)
        $component->set('resources.wood', 720);
        $component->call('calculateStorageWarnings');
        $component->assertSet('storageWarnings.wood.level', 'critical');
    }

    public function test_initializes_resource_history()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('resourceHistory.wood', [])
            ->assertSet('resourceHistory.clay', [])
            ->assertSet('resourceHistory.iron', [])
            ->assertSet('resourceHistory.crop', [])
            ->assertSet('productionHistory.wood', [])
            ->assertSet('productionHistory.clay', [])
            ->assertSet('productionHistory.iron', [])
            ->assertSet('productionHistory.crop', []);
    }

    public function test_updates_resource_history()
    {
        $village = Village::first();

        $component = Livewire::test(ResourceManager::class, ['village' => $village]);

        $component->call('updateResourceHistory');

        $component
            ->assertCount('resourceHistory.wood', 1)
            ->assertCount('resourceHistory.clay', 1)
            ->assertCount('resourceHistory.iron', 1)
            ->assertCount('resourceHistory.crop', 1)
            ->assertCount('productionHistory.wood', 1)
            ->assertCount('productionHistory.clay', 1)
            ->assertCount('productionHistory.iron', 1)
            ->assertCount('productionHistory.crop', 1);
    }

    public function test_handles_game_tick_processed()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_building_upgraded()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->dispatch('buildingUpgraded', ['building_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Building upgraded - resource production updated')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_village_selected()
    {
        $village = Village::first();
        $newVillage = Village::factory()->create(['player_id' => $village->player_id]);

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->dispatch('villageSelected', $newVillage->id)
            ->assertSet('village.id', $newVillage->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - resources updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $village = Village::first();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSee('Resource Manager')
            ->assertSee('Wood')
            ->assertSee('Clay')
            ->assertSee('Iron')
            ->assertSee('Crop');
    }

    public function test_handles_missing_village()
    {
        Livewire::test(ResourceManager::class, ['village' => null])
            ->assertSet('village', null);
    }

    public function test_handles_missing_resources()
    {
        $village = Village::first();
        $village->resources()->delete();

        Livewire::test(ResourceManager::class, ['village' => $village])
            ->assertSet('resources.wood', 0)
            ->assertSet('resources.clay', 0)
            ->assertSet('resources.iron', 0)
            ->assertSet('resources.crop', 0);
    }
}

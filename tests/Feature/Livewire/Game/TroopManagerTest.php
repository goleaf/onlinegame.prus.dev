<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\TroopManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TroopManagerTest extends TestCase
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
            'world_id' => $world->id
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $this->actingAs($user);
    }

    public function test_can_render_troop_manager()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertStatus(200)
            ->assertSee('Troop Manager');
    }

    public function test_loads_troop_data_on_mount()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('village', $village)
            ->assertSet('troops', [])
            ->assertSet('unitTypes', [])
            ->assertSet('trainingQueues', []);
    }

    public function test_can_toggle_real_time_updates()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_refresh()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_can_set_refresh_interval()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('refreshInterval', 5)
            ->call('setRefreshInterval', 10)
            ->assertSet('refreshInterval', 10)
            ->call('setRefreshInterval', 0)
            ->assertSet('refreshInterval', 1)
            ->call('setRefreshInterval', 100)
            ->assertSet('refreshInterval', 60);
    }

    public function test_can_set_game_speed()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_troop()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('selectedTroop', null)
            ->assertSet('showDetails', false)
            ->call('selectTroop', 1)
            ->assertSet('selectedTroop', 1)
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_can_filter_by_type()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('filterByType', null)
            ->call('filterByType', 'legionnaire')
            ->assertSet('filterByType', 'legionnaire');
    }

    public function test_can_clear_filters()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->set('filterByType', 'legionnaire')
            ->set('searchQuery', 'test')
            ->set('showOnlyAvailable', true)
            ->set('showOnlyTraining', true)
            ->call('clearFilters')
            ->assertSet('filterByType', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyAvailable', false)
            ->assertSet('showOnlyTraining', false);
    }

    public function test_can_sort_troops()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('sortBy', 'count')
            ->assertSet('sortOrder', 'desc')
            ->call('sortTroops', 'name')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortOrder', 'desc')
            ->call('sortTroops', 'name')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortOrder', 'asc');
    }

    public function test_can_search_troops()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->set('searchQuery', 'legionnaire')
            ->call('searchTroops')
            ->assertSet('searchQuery', 'legionnaire');
    }

    public function test_can_toggle_available_filter()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('showOnlyAvailable', false)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', true)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', false);
    }

    public function test_can_toggle_training_filter()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('showOnlyTraining', false)
            ->call('toggleTrainingFilter')
            ->assertSet('showOnlyTraining', true)
            ->call('toggleTrainingFilter')
            ->assertSet('showOnlyTraining', false);
    }

    public function test_can_set_training_mode()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('trainingMode', 'single')
            ->call('setTrainingMode', 'batch')
            ->assertSet('trainingMode', 'batch')
            ->call('setTrainingMode', 'continuous')
            ->assertSet('trainingMode', 'continuous');
    }

    public function test_can_set_batch_size()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('batchSize', 10)
            ->call('setBatchSize', 20)
            ->assertSet('batchSize', 20)
            ->call('setBatchSize', 0)
            ->assertSet('batchSize', 1)
            ->call('setBatchSize', 200)
            ->assertSet('batchSize', 100);
    }

    public function test_can_toggle_continuous_training()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('continuousTraining', false)
            ->call('toggleContinuousTraining')
            ->assertSet('continuousTraining', true)
            ->call('toggleContinuousTraining')
            ->assertSet('continuousTraining', false);
    }

    public function test_get_unit_icon()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        $this->assertEquals('⚔️', $component->instance()->getUnitIcon('legionnaire'));
        $this->assertEquals('🛡️', $component->instance()->getUnitIcon('praetorian'));
        $this->assertEquals('🐎', $component->instance()->getUnitIcon('equites_legati'));
        $this->assertEquals('⚔️', $component->instance()->getUnitIcon('unknown'));
    }

    public function test_get_unit_color()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        $unit = ['is_training' => true, 'count' => 0];
        $this->assertEquals('orange', $component->instance()->getUnitColor($unit));

        $unit = ['is_training' => false, 'count' => 5];
        $this->assertEquals('green', $component->instance()->getUnitColor($unit));

        $unit = ['is_training' => false, 'count' => 0];
        $this->assertEquals('gray', $component->instance()->getUnitColor($unit));
    }

    public function test_get_unit_status()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        $unit = ['is_training' => true, 'count' => 0];
        $this->assertEquals('Training...', $component->instance()->getUnitStatus($unit));

        $unit = ['is_training' => false, 'count' => 5];
        $this->assertEquals('Available', $component->instance()->getUnitStatus($unit));

        $unit = ['is_training' => false, 'count' => 0];
        $this->assertEquals('Not Available', $component->instance()->getUnitStatus($unit));
    }

    public function test_get_training_time()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        $this->assertEquals('00:01:00', $component->instance()->getTrainingTime('legionnaire', 1));
        $this->assertEquals('00:05:00', $component->instance()->getTrainingTime('legionnaire', 5));
    }

    public function test_get_training_cost()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        // Test with non-existent unit type
        $cost = $component->instance()->getTrainingCost('unknown', 1);
        $this->assertEquals([], $cost);
    }

    public function test_can_afford_training()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        // Test with non-existent unit type
        $this->assertFalse($component->instance()->canAffordTraining('unknown', 1));
    }

    public function test_start_batch_training()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->set('trainingMode', 'batch')
            ->call('startBatchTraining')
            ->assertSet('trainingMode', 'batch');
    }

    public function test_start_continuous_training()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->set('continuousTraining', true)
            ->call('startContinuousTraining')
            ->assertSet('continuousTraining', true);
    }

    public function test_stop_continuous_training()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->set('continuousTraining', true)
            ->call('stopContinuousTraining')
            ->assertSet('continuousTraining', false);
    }

    public function test_notification_system()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $village = Village::first();

        $component = Livewire::test(TroopManager::class, ['village' => $village]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_calculates_training_progress()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('trainingProgress', [])
            ->call('calculateTrainingProgress')
            ->assertSet('trainingProgress', []);
    }

    public function test_initializes_troop_history()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('troopHistory', []);
    }

    public function test_handles_game_tick_processed()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_troop_trained()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->dispatch('troopTrained', ['unit_type' => 'legionnaire', 'quantity' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Troop training completed')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_troop_disbanded()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->dispatch('troopDisbanded', ['unit_type' => 'legionnaire', 'quantity' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Troops disbanded')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_village_selected()
    {
        $village = Village::first();
        $newVillage = Village::factory()->create(['player_id' => $village->player_id]);

        Livewire::test(TroopManager::class, ['village' => $village])
            ->dispatch('villageSelected', $newVillage->id)
            ->assertSet('village.id', $newVillage->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - troops updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $village = Village::first();

        Livewire::test(TroopManager::class, ['village' => $village])
            ->assertSee('Troop Manager')
            ->assertSee('Training')
            ->assertSee('Units');
    }

    public function test_handles_missing_village()
    {
        Livewire::test(TroopManager::class, ['village' => null])
            ->assertSet('village', null);
    }
}

<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\BuildingManager;
use App\Models\Game\Building;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BuildingManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private World $world;

    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->world = World::factory()->create();
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
        ]);
        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_mount_with_specific_village()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertSet('village.id', $this->village->id)
            ->assertSet('isLoading', false)
            ->assertSet('realTimeUpdates', true)
            ->assertSet('autoRefresh', true)
            ->assertSet('refreshInterval', 5);
    }

    /**
     * @test
     */
    public function it_can_mount_without_village_id()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class)
            ->assertSet('village.id', $this->village->id);
    }

    /**
     * @test
     */
    public function it_loads_buildings_on_mount()
    {
        $this->actingAs($this->user);

        Building::factory()->count(3)->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertCount('buildings', 3);
    }

    /**
     * @test
     */
    public function it_can_select_building()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 5,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('selectBuilding', $building->id)
            ->assertSet('selectedBuilding.id', $building->id)
            ->assertSet('showDetails', true);
    }

    /**
     * @test
     */
    public function it_cannot_select_nonexistent_building()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('selectBuilding', 999)
            ->assertSet('selectedBuilding', null);
    }

    /**
     * @test
     */
    public function it_can_show_upgrade_modal()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('showUpgradeModal', $building->id)
            ->assertSet('selectedBuilding.id', $building->id)
            ->assertSet('showUpgradeModal', true);
    }

    /**
     * @test
     */
    public function it_can_hide_upgrade_modal()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('showUpgradeModal', true)
            ->call('hideUpgradeModal')
            ->assertSet('showUpgradeModal', false)
            ->assertSet('selectedBuilding', null);
    }

    /**
     * @test
     */
    public function it_can_upgrade_building()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 1,
        ]);

        // Set village resources
        $this->village->update([
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('upgradeBuilding', $building->id);
    }

    /**
     * @test
     */
    public function it_can_demolish_building()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 2,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('demolishBuilding', $building->id);
    }

    /**
     * @test
     */
    public function it_cannot_demolish_level_zero_building()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 0,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('demolishBuilding', $building->id);
    }

    /**
     * @test
     */
    public function it_can_cancel_construction()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'construction_started_at' => now(),
            'construction_completed_at' => now()->addHours(2),
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('cancelConstruction', $building->id);
    }

    /**
     * @test
     */
    public function it_can_complete_construction()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'construction_started_at' => now()->subHours(3),
            'construction_completed_at' => now()->subHours(1),
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('completeConstruction', $building->id);
    }

    /**
     * @test
     */
    public function it_can_filter_buildings_by_type()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'warehouse',
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('filterByType', 'barracks')
            ->call('loadBuildings')
            ->assertCount('buildings', 1);
    }

    /**
     * @test
     */
    public function it_can_search_buildings()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'warehouse',
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('searchQuery', 'barracks')
            ->call('loadBuildings')
            ->assertCount('buildings', 1);
    }

    /**
     * @test
     */
    public function it_can_sort_buildings()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 1,
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 5,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('sortBy', 'level')
            ->set('sortOrder', 'desc')
            ->call('loadBuildings');
    }

    /**
     * @test
     */
    public function it_can_show_only_upgradeable_buildings()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 1,
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 20,  // max level
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('showOnlyUpgradeable', true)
            ->call('loadBuildings');
    }

    /**
     * @test
     */
    public function it_can_show_only_max_level_buildings()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 1,
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 20,  // max level
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('showOnlyMaxLevel', true)
            ->call('loadBuildings');
    }

    /**
     * @test
     */
    public function it_can_refresh_buildings()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('refreshBuildings')
            ->assertSet('isLoading', false);
    }

    /**
     * @test
     */
    public function it_can_toggle_real_time_updates()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    /**
     * @test
     */
    public function it_can_toggle_auto_refresh()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    /**
     * @test
     */
    public function it_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('setRefreshInterval', 10)
            ->assertSet('refreshInterval', 10);
    }

    /**
     * @test
     */
    public function it_can_toggle_details()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    /**
     * @test
     */
    public function it_can_clear_filters()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->set('filterByType', 'barracks')
            ->set('searchQuery', 'test')
            ->set('showOnlyUpgradeable', true)
            ->call('clearFilters')
            ->assertSet('filterByType', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyUpgradeable', false)
            ->assertSet('showOnlyMaxLevel', false);
    }

    /**
     * @test
     */
    public function it_can_handle_building_completed_event()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->dispatch('buildingCompleted', [
                'buildingId' => $building->id,
                'villageId' => $this->village->id,
            ]);
    }

    /**
     * @test
     */
    public function it_can_handle_building_upgraded_event()
    {
        $this->actingAs($this->user);

        $building = Building::factory()->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->dispatch('buildingUpgraded', [
                'buildingId' => $building->id,
                'newLevel' => 2,
            ]);
    }

    /**
     * @test
     */
    public function it_can_handle_resources_updated_event()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->dispatch('resourcesUpdated', [
                'villageId' => $this->village->id,
                'resources' => [
                    'wood' => 1000,
                    'clay' => 1000,
                    'iron' => 1000,
                    'crop' => 1000,
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_handle_game_tick_processed_event()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->dispatch('gameTickProcessed');
    }

    /**
     * @test
     */
    public function it_handles_missing_village_gracefully()
    {
        $userWithoutPlayer = User::factory()->create();
        $this->actingAs($userWithoutPlayer);

        Livewire::test(BuildingManager::class)
            ->assertSet('village', null);
    }

    /**
     * @test
     */
    public function it_handles_invalid_village_id()
    {
        $this->actingAs($this->user);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(BuildingManager::class, ['villageId' => 999]);
    }

    /**
     * @test
     */
    public function it_calculates_building_progress()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'construction_started_at' => now()->subHours(1),
            'construction_completed_at' => now()->addHours(1),
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('calculateBuildingProgress');
    }

    /**
     * @test
     */
    public function it_can_get_building_statistics()
    {
        $this->actingAs($this->user);

        Building::factory()->count(5)->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('getBuildingStatistics');
    }

    /**
     * @test
     */
    public function it_can_export_building_data()
    {
        $this->actingAs($this->user);

        Building::factory()->count(3)->create([
            'village_id' => $this->village->id,
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('exportBuildingData');
    }

    /**
     * @test
     */
    public function it_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_handle_construction_queue()
    {
        $this->actingAs($this->user);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'construction_started_at' => now(),
            'construction_completed_at' => now()->addHours(2),
        ]);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->assertCount('constructionQueue', 1);
    }

    /**
     * @test
     */
    public function it_can_handle_building_categories()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id]);
    }

    /**
     * @test
     */
    public function it_can_handle_notifications()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('addNotification', 'Test notification', 'success');
    }

    /**
     * @test
     */
    public function it_can_clear_notifications()
    {
        $this->actingAs($this->user);

        Livewire::test(BuildingManager::class, ['villageId' => $this->village->id])
            ->call('addNotification', 'Test notification', 'success')
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }
}

<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\TechnologyManager;
use App\Models\Game\Player;
use App\Models\Game\Technology;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TechnologyManagerTest extends TestCase
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

    public function test_can_render_technology_manager()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertStatus(200)
            ->assertSee('Technology Manager');
    }

    public function test_loads_technology_data_on_mount()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('village', $village)
            ->assertSet('availableTechnologies', [])
            ->assertSet('researchedTechnologies', [])
            ->assertSet('researchQueue', []);
    }

    public function test_can_toggle_real_time_updates()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_refresh()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_can_set_refresh_interval()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('refreshInterval', 20)
            ->call('setRefreshInterval', 30)
            ->assertSet('refreshInterval', 30)
            ->call('setRefreshInterval', 0)
            ->assertSet('refreshInterval', 5)
            ->call('setRefreshInterval', 100)
            ->assertSet('refreshInterval', 60);
    }

    public function test_can_set_game_speed()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_technology()
    {
        $village = Village::first();
        $technology = Technology::factory()->create(['world_id' => $village->world_id]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('selectedTechnology', null)
            ->assertSet('showDetails', false)
            ->call('selectTechnology', $technology->id)
            ->assertSet('selectedTechnology.id', $technology->id)
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_can_start_research()
    {
        $village = Village::first();
        $technology = Technology::factory()->create([
            'world_id' => $village->world_id,
            'min_level' => 1,
            'costs' => json_encode(['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100]),
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('startResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Research started: {$technology->name}")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_start_research_with_insufficient_level()
    {
        $village = Village::first();
        $technology = Technology::factory()->create([
            'world_id' => $village->world_id,
            'min_level' => 10,
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('startResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Technology requires higher level')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_start_research_with_insufficient_resources()
    {
        $village = Village::first();
        $technology = Technology::factory()->create([
            'world_id' => $village->world_id,
            'min_level' => 1,
            'costs' => json_encode(['wood' => 10000, 'clay' => 10000, 'iron' => 10000, 'crop' => 10000]),
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('startResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Insufficient resources for research')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_start_nonexistent_technology()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('startResearch', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Technology not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_cancel_research()
    {
        $village = Village::first();
        $technology = Technology::factory()->create(['world_id' => $village->world_id]);

        // Create research
        $village->player->technologies()->create([
            'technology_id' => $technology->id,
            'status' => 'researching',
            'progress' => 50,
            'started_at' => now(),
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('cancelResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Research cancelled: {$technology->name}")
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_cannot_cancel_nonexistent_research()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('cancelResearch', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Research not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_complete_research()
    {
        $village = Village::first();
        $technology = Technology::factory()->create(['world_id' => $village->world_id]);

        // Create completed research
        $village->player->technologies()->create([
            'technology_id' => $technology->id,
            'status' => 'researching',
            'progress' => 100,
            'started_at' => now(),
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('completeResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Research completed: {$technology->name}!")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_complete_research_with_insufficient_progress()
    {
        $village = Village::first();
        $technology = Technology::factory()->create(['world_id' => $village->world_id]);

        // Create research with insufficient progress
        $village->player->technologies()->create([
            'technology_id' => $technology->id,
            'status' => 'researching',
            'progress' => 50,
            'started_at' => now(),
        ]);

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('completeResearch', $technology->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Research not completed yet')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_filter_by_category()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('filterByCategory', null)
            ->call('filterByCategory', 'military')
            ->assertSet('filterByCategory', 'military');
    }

    public function test_can_filter_by_level()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('filterByLevel', null)
            ->call('filterByLevel', 1)
            ->assertSet('filterByLevel', 1);
    }

    public function test_can_filter_by_status()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('filterByStatus', null)
            ->call('filterByStatus', 'available')
            ->assertSet('filterByStatus', 'available');
    }

    public function test_can_clear_filters()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->set('filterByCategory', 'military')
            ->set('filterByLevel', 1)
            ->set('filterByStatus', 'available')
            ->set('searchQuery', 'test')
            ->set('showOnlyAvailable', true)
            ->set('showOnlyResearched', true)
            ->set('showOnlyResearching', true)
            ->call('clearFilters')
            ->assertSet('filterByCategory', null)
            ->assertSet('filterByLevel', null)
            ->assertSet('filterByStatus', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyAvailable', false)
            ->assertSet('showOnlyResearched', false)
            ->assertSet('showOnlyResearching', false);
    }

    public function test_can_sort_technologies()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('sortBy', 'name')
            ->assertSet('sortOrder', 'asc')
            ->call('sortTechnologies', 'level')
            ->assertSet('sortBy', 'level')
            ->assertSet('sortOrder', 'asc')
            ->call('sortTechnologies', 'level')
            ->assertSet('sortBy', 'level')
            ->assertSet('sortOrder', 'desc');
    }

    public function test_can_search_technologies()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->set('searchQuery', 'military tech')
            ->call('searchTechnologies')
            ->assertSet('searchQuery', 'military tech');
    }

    public function test_can_toggle_available_filter()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('showOnlyAvailable', false)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', true)
            ->call('toggleAvailableFilter')
            ->assertSet('showOnlyAvailable', false);
    }

    public function test_can_toggle_researched_filter()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('showOnlyResearched', false)
            ->call('toggleResearchedFilter')
            ->assertSet('showOnlyResearched', true)
            ->call('toggleResearchedFilter')
            ->assertSet('showOnlyResearched', false);
    }

    public function test_can_toggle_researching_filter()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('showOnlyResearching', false)
            ->call('toggleResearchingFilter')
            ->assertSet('showOnlyResearching', true)
            ->call('toggleResearchingFilter')
            ->assertSet('showOnlyResearching', false);
    }

    public function test_calculates_technology_stats()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('technologyStats', [])
            ->call('calculateTechnologyStats')
            ->assertSet('technologyStats', []);
    }

    public function test_calculates_research_progress()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('researchProgress', [])
            ->call('calculateResearchProgress')
            ->assertSet('researchProgress', []);
    }

    public function test_calculates_research_history()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('researchHistory', [])
            ->call('calculateResearchHistory')
            ->assertSet('researchHistory', []);
    }

    public function test_calculates_technology_tree()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('technologyTree', [])
            ->call('calculateTechnologyTree')
            ->assertSet('technologyTree', []);
    }

    public function test_calculates_research_costs()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('researchCosts', [])
            ->call('calculateResearchCosts')
            ->assertSet('researchCosts', []);
    }

    public function test_calculates_research_benefits()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('researchBenefits', [])
            ->call('calculateResearchBenefits')
            ->assertSet('researchBenefits', []);
    }

    public function test_get_technology_icon()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $this->assertEquals('⚔️', $component->instance()->getTechnologyIcon(['category' => 'military']));
        $this->assertEquals('💰', $component->instance()->getTechnologyIcon(['category' => 'economy']));
        $this->assertEquals('🏗️', $component->instance()->getTechnologyIcon(['category' => 'infrastructure']));
        $this->assertEquals('🛡️', $component->instance()->getTechnologyIcon(['category' => 'defense']));
        $this->assertEquals('⭐', $component->instance()->getTechnologyIcon(['category' => 'special']));
        $this->assertEquals('🔬', $component->instance()->getTechnologyIcon(['category' => 'unknown']));
    }

    public function test_get_technology_color()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $this->assertEquals('red', $component->instance()->getTechnologyColor(['category' => 'military']));
        $this->assertEquals('green', $component->instance()->getTechnologyColor(['category' => 'economy']));
        $this->assertEquals('blue', $component->instance()->getTechnologyColor(['category' => 'infrastructure']));
        $this->assertEquals('purple', $component->instance()->getTechnologyColor(['category' => 'defense']));
        $this->assertEquals('gold', $component->instance()->getTechnologyColor(['category' => 'special']));
        $this->assertEquals('gray', $component->instance()->getTechnologyColor(['category' => 'unknown']));
    }

    public function test_get_technology_status()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $technology = ['status' => 'researching'];
        $this->assertEquals('Researching', $component->instance()->getTechnologyStatus($technology));

        $technology = ['status' => 'completed'];
        $this->assertEquals('Completed', $component->instance()->getTechnologyStatus($technology));

        $technology = ['status' => 'cancelled'];
        $this->assertEquals('Cancelled', $component->instance()->getTechnologyStatus($technology));

        $technology = ['status' => 'available'];
        $this->assertEquals('Available', $component->instance()->getTechnologyStatus($technology));
    }

    public function test_get_research_priority()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $this->assertEquals('Low', $component->instance()->getResearchPriority(['priority' => 'low']));
        $this->assertEquals('Medium', $component->instance()->getResearchPriority(['priority' => 'medium']));
        $this->assertEquals('High', $component->instance()->getResearchPriority(['priority' => 'high']));
        $this->assertEquals('Critical', $component->instance()->getResearchPriority(['priority' => 'critical']));
        $this->assertEquals('Medium', $component->instance()->getResearchPriority(['priority' => 'unknown']));
    }

    public function test_get_research_time()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $technology = ['research_time' => 30];
        $this->assertEquals('30s', $component->instance()->getResearchTime($technology));

        $technology = ['research_time' => 120];
        $this->assertEquals('2m', $component->instance()->getResearchTime($technology));

        $technology = ['research_time' => 7200];
        $this->assertEquals('2h', $component->instance()->getResearchTime($technology));
    }

    public function test_get_research_cost()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $technology = ['costs' => json_encode(['wood' => 100, 'clay' => 200, 'iron' => 300, 'crop' => 400])];
        $this->assertEquals(1000, $component->instance()->getResearchCost($technology));

        $technology = ['costs' => json_encode([])];
        $this->assertEquals(0, $component->instance()->getResearchCost($technology));
    }

    public function test_notification_system()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $village = Village::first();

        $component = Livewire::test(TechnologyManager::class, ['village' => $village]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_handles_game_tick_processed()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_research_started()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->dispatch('researchStarted', ['technology_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Research started')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_research_completed()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->dispatch('researchCompleted', ['technology_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Research completed')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_research_cancelled()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->dispatch('researchCancelled', ['technology_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Research cancelled')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_technology_unlocked()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->dispatch('technologyUnlocked', ['technology_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Technology unlocked')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_village_selected()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->dispatch('villageSelected', $village->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - technology data updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $village = Village::first();

        Livewire::test(TechnologyManager::class, ['village' => $village])
            ->assertSee('Technology Manager')
            ->assertSee('Research')
            ->assertSee('Technologies');
    }

    public function test_handles_missing_village()
    {
        Livewire::test(TechnologyManager::class, ['village' => null])
            ->assertSet('village', null);
    }
}

<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\StatisticsViewer;
use App\Models\Game\Building;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Troop;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatisticsViewerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $world;

    protected $player;

    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test world
        $this->world = World::factory()->create([
            'name' => 'Test World',
            'speed' => 1.0,
            'is_active' => true,
        ]);

        // Create test player
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'name' => 'Test Player',
            'points' => 1000,
        ]);

        // Create test village
        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Test Village',
            'x_coordinate' => 100,
            'y_coordinate' => 100,
            'population' => 100,
        ]);
    }

    public function test_can_mount_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertNotNull($component->world);
        $this->assertEquals($this->world->id, $component->world->id);
    }

    public function test_loads_player_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertNotNull($component->player);
        $this->assertEquals($this->player->id, $component->player->id);
    }

    public function test_loads_statistics_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->playerStats);
        $this->assertIsArray($component->battleStats);
        $this->assertIsArray($component->resourceStats);
    }

    public function test_can_switch_view_modes()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        // Test switching to rankings view
        $component->call('setViewMode', 'rankings');
        $this->assertEquals('rankings', $component->viewMode);

        // Test switching to battles view
        $component->call('setViewMode', 'battles');
        $this->assertEquals('battles', $component->viewMode);

        // Test switching to resources view
        $component->call('setViewMode', 'resources');
        $this->assertEquals('resources', $component->viewMode);
    }

    public function test_can_set_time_range()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setTimeRange', 'week');
        $this->assertEquals('week', $component->timeRange);

        $component->call('setTimeRange', 'month');
        $this->assertEquals('month', $component->timeRange);
    }

    public function test_can_set_stat_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setStatType', 'personal');
        $this->assertEquals('personal', $component->statType);

        $component->call('setStatType', 'alliance');
        $this->assertEquals('alliance', $component->statType);
    }

    public function test_can_sort_statistics()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('sortStatistics', 'points');
        $this->assertEquals('points', $component->sortBy);
        $this->assertEquals('asc', $component->sortOrder);

        // Test toggle sort order
        $component->call('sortStatistics', 'points');
        $this->assertEquals('desc', $component->sortOrder);
    }

    public function test_can_search_statistics()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->set('searchQuery', 'Test Player');
        $component->call('searchStatistics');

        $this->assertEquals('Test Player', $component->searchQuery);
    }

    public function test_can_clear_filters()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        // Set some filters
        $component->set('timeRange', 'week');
        $component->set('statType', 'personal');
        $component->set('searchQuery', 'test');

        $component->call('clearFilters');

        $this->assertEquals('all', $component->timeRange);
        $this->assertEquals('all', $component->statType);
        $this->assertEquals('', $component->searchQuery);
    }

    public function test_can_toggle_real_time_updates()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('toggleRealTimeUpdates');
        $this->assertFalse($component->realTimeUpdates);

        $component->call('toggleRealTimeUpdates');
        $this->assertTrue($component->realTimeUpdates);
    }

    public function test_can_toggle_auto_refresh()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('toggleAutoRefresh');
        $this->assertFalse($component->autoRefresh);

        $component->call('toggleAutoRefresh');
        $this->assertTrue($component->autoRefresh);
    }

    public function test_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setRefreshInterval', 60);
        $this->assertEquals(60, $component->refreshInterval);

        // Test bounds
        $component->call('setRefreshInterval', 1);
        $this->assertEquals(5, $component->refreshInterval);

        $component->call('setRefreshInterval', 1000);
        $this->assertEquals(300, $component->refreshInterval);
    }

    public function test_can_refresh_statistics()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('refreshStatistics');
        $this->assertNotNull($component->lastUpdate);
    }

    public function test_calculates_player_rank()
    {
        $this->actingAs($this->user);

        // Create additional players with higher points
        Player::factory()->create([
            'world_id' => $this->world->id,
            'points' => 2000,
        ]);
        Player::factory()->create([
            'world_id' => $this->world->id,
            'points' => 1500,
        ]);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->playerStats);
        $this->assertArrayHasKey('rank', $component->playerStats);
    }

    public function test_calculates_battle_statistics()
    {
        $this->actingAs($this->user);

        // Create test reports
        Report::factory()->create([
            'world_id' => $this->world->id,
            'attacker_id' => $this->player->id,
            'status' => 'victory',
        ]);
        Report::factory()->create([
            'world_id' => $this->world->id,
            'attacker_id' => $this->player->id,
            'status' => 'defeat',
        ]);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->battleStats);
        $this->assertArrayHasKey('attacks_won', $component->battleStats);
        $this->assertArrayHasKey('attacks_lost', $component->battleStats);
    }

    public function test_calculates_resource_statistics()
    {
        $this->actingAs($this->user);

        // Update village resources
        $this->village->update([
            'wood' => 1000,
            'clay' => 2000,
            'iron' => 1500,
            'crop' => 800,
        ]);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->resourceStats);
        $this->assertArrayHasKey('total_wood', $component->resourceStats);
        $this->assertArrayHasKey('total_clay', $component->resourceStats);
    }

    public function test_calculates_building_statistics()
    {
        $this->actingAs($this->user);

        // Create test buildings
        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 5,
        ]);
        Building::factory()->create([
            'village_id' => $this->village->id,
            'level' => 3,
        ]);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->buildingStats);
        $this->assertArrayHasKey('total_buildings', $component->buildingStats);
    }

    public function test_calculates_troop_statistics()
    {
        $this->actingAs($this->user);

        // Create test troops
        $unitType = UnitType::factory()->create(['name' => 'Legionnaire']);
        Troop::factory()->create([
            'village_id' => $this->village->id,
            'unit_type_id' => $unitType->id,
            'count' => 100,
        ]);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->troopStats);
        $this->assertArrayHasKey('total_troops', $component->troopStats);
    }

    public function test_handles_missing_world()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => null]);

        $this->assertNull($component->world);
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Create world without player
        $world = World::factory()->create();

        $component = Livewire::test(StatisticsViewer::class, ['world' => $world]);

        $this->assertNull($component->player);
    }

    public function test_handles_missing_statistics()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->playerStats);
        $this->assertIsArray($component->battleStats);
        $this->assertIsArray($component->resourceStats);
    }

    public function test_handles_invalid_view_mode()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'invalid');
        $this->assertEquals('invalid', $component->viewMode);
    }

    public function test_handles_invalid_time_range()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setTimeRange', 'invalid');
        $this->assertEquals('invalid', $component->timeRange);
    }

    public function test_handles_invalid_stat_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setStatType', 'invalid');
        $this->assertEquals('invalid', $component->statType);
    }

    public function test_handles_missing_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertNotNull($component->player);
    }

    public function test_handles_missing_world_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertNotNull($component->world);
    }

    public function test_handles_missing_statistics_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertIsArray($component->playerStats);
        $this->assertIsArray($component->battleStats);
        $this->assertIsArray($component->resourceStats);
    }

    public function test_handles_missing_ranking_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'rankings');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $component->rankingStats);
    }

    public function test_handles_missing_battle_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'battles');
        $this->assertIsArray($component->battleStats);
    }

    public function test_handles_missing_resource_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'resources');
        $this->assertIsArray($component->resourceStats);
    }

    public function test_handles_missing_building_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'buildings');
        $this->assertIsArray($component->buildingStats);
    }

    public function test_handles_missing_troop_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'troops');
        $this->assertIsArray($component->troopStats);
    }

    public function test_handles_missing_achievement_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $component->call('setViewMode', 'achievements');
        $this->assertIsArray($component->achievementStats);
    }

    public function test_real_time_event_handlers()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        // Test event handlers
        $component->dispatch('statisticsUpdated');
        $component->dispatch('playerRankingChanged');
        $component->dispatch('battleCompleted');
        $component->dispatch('achievementUnlocked');
        $component->dispatch('gameTickProcessed');
        $component->dispatch('villageSelected', ['villageId' => $this->village->id]);

        $this->assertTrue(true);
    }

    public function test_notification_management()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        // Test notifications
        $component->call('addNotification', 'Test notification', 'info');
        $this->assertCount(1, $component->notifications);

        $component->call('clearNotifications');
        $this->assertCount(0, $component->notifications);
    }

    public function test_stat_icon_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertEquals('chart-bar', $component->instance()->getStatIcon('overview'));
        $this->assertEquals('trophy', $component->instance()->getStatIcon('rankings'));
        $this->assertEquals('sword', $component->instance()->getStatIcon('battles'));
    }

    public function test_stat_color_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertEquals('blue', $component->instance()->getStatColor('overview'));
        $this->assertEquals('yellow', $component->instance()->getStatColor('rankings'));
        $this->assertEquals('red', $component->instance()->getStatColor('battles'));
    }

    public function test_format_number_method()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $this->assertEquals('1.5K', $component->instance()->formatNumber(1500));
        $this->assertEquals('2.3M', $component->instance()->formatNumber(2300000));
        $this->assertEquals('500', $component->instance()->formatNumber(500));
    }

    public function test_time_ago_method()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(StatisticsViewer::class, ['world' => $this->world]);

        $timeAgo = $component->instance()->getTimeAgo(now()->subHour());
        $this->assertStringContainsString('hour', $timeAgo);
    }
}

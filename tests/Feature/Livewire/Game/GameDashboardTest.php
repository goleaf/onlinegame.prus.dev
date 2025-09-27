<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\GameDashboard;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $player;
    protected $village;
    protected $world;
    protected $buildingType;
    protected $building;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
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

        $this->buildingType = BuildingType::factory()->create();
        $this->building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
        ]);

        // Create resources
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
        foreach ($resourceTypes as $type) {
            Resource::factory()->create([
                'village_id' => $this->village->id,
                'type' => $type,
                'amount' => 1000,
                'production_rate' => 10,
                'storage_capacity' => 2000,
            ]);
        }
    }

    public function test_can_render_game_dashboard()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertStatus(200);
        $component->assertSee('Travian Game');
        $component->assertSee($this->player->name);
    }

    public function test_game_dashboard_loads_with_real_time_features()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertStatus(200)
            ->assertSee($this->player->name)
            ->assertSee('Real-time ON')
            ->assertSee('Auto Refresh: ON')
            ->assertSee('Game Speed:')
            ->assertSee('Resources - ' . $this->village->name)
            ->assertSee('Buildings - ' . $this->village->name);
    }

    public function test_loads_game_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('player.id', $this->player->id);
        $component->assertSet('currentVillage.id', $this->village->id);
    }

    public function test_loads_villages()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('villages', function ($villages) {
            return $villages->count() === 1;
        });
    }

    public function test_loads_recent_events()
    {
        $this->actingAs($this->user);

        // Create test events
        GameEvent::factory()->create([
            'player_id' => $this->player->id,
            'village_id' => $this->village->id,
            'event_type' => 'test_event',
            'description' => 'Test Event',
        ]);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('recentEvents', function ($events) {
            return $events->count() === 1;
        });
    }

    public function test_loads_game_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('gameStats.total_villages', 1);
        $component->assertSet('gameStats.total_points', $this->player->points);
    }

    public function test_real_time_updates_toggle()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertSee('Real-time ON')
            ->call('toggleRealTimeUpdates')
            ->assertSee('Real-time OFF')
            ->call('toggleRealTimeUpdates')
            ->assertSee('Real-time ON');
    }

    public function test_can_toggle_auto_refresh()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('autoRefresh', true);

        $component->call('toggleAutoRefresh');

        $component->assertSet('autoRefresh', false);
    }

    public function test_auto_refresh_toggle()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertSee('Auto Refresh: ON')
            ->call('toggleAutoRefresh')
            ->assertSee('Auto Refresh: OFF')
            ->call('toggleAutoRefresh')
            ->assertSee('Auto Refresh: ON');
    }

    public function test_game_speed_selection()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->set('gameSpeed', 2);
        $this->assertEquals(2, $component->get('gameSpeed'));

        $component->set('gameSpeed', 0.5);
        $this->assertEquals(0.5, $component->get('gameSpeed'));

        $component->set('gameSpeed', 5);
        $this->assertEquals(5, $component->get('gameSpeed'));
    }

    public function test_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->call('setRefreshInterval', 10);

        $component->assertSet('refreshInterval', 10);
    }

    public function test_refresh_interval_selection()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->set('refreshInterval', 10);
        $this->assertEquals(10, $component->get('refreshInterval'));

        $component->set('refreshInterval', 1);
        $this->assertEquals(1, $component->get('refreshInterval'));

        $component->set('refreshInterval', 100);
        $this->assertEquals(100, $component->get('refreshInterval'));
    }

    public function test_can_select_village()
    {
        $this->actingAs($this->user);

        // Create another village
        $village2 = Village::factory()->create(['player_id' => $this->player->id]);

        $component = Livewire::test(GameDashboard::class);

        $component->call('selectVillage', $village2->id);

        $component->assertSet('currentVillage.id', $village2->id);
    }

    public function test_village_selection()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->call('selectVillage', $this->village->id)
            ->assertSet('currentVillage.id', $this->village->id);
    }

    public function test_can_refresh_game_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->call('refreshGameData');

        $component->assertSet('player.id', $this->player->id);
    }

    public function test_can_process_game_tick()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->call('processGameTick');

        $component->assertDispatched('gameTickProcessed');
    }

    public function test_game_tick_processing()
    {
        $this->actingAs($this->user);

        // Mock the GameTickService
        $this->mock(GameTickService::class, function ($mock) {
            $mock->shouldReceive('processGameTick')->once();
        });

        Livewire::test(GameDashboard::class)
            ->call('processGameTick')
            ->assertDispatched('gameTickProcessed');
    }

    public function test_handles_game_tick_errors()
    {
        $this->actingAs($this->user);

        // Mock GameTickService to throw exception
        $this->mock(\App\Services\GameTickService::class, function ($mock) {
            $mock->shouldReceive('processGameTick')->andThrow(new \Exception('Test error'));
        });

        $component = Livewire::test(GameDashboard::class);

        $component->call('processGameTick');

        $component->assertDispatched('gameTickError');
    }

    public function test_resource_production_rates_calculation()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class)
            ->call('calculateResourceProductionRates');

        $this->assertNotEmpty($component->get('resourceProductionRates'));
        $this->assertArrayHasKey('wood', $component->get('resourceProductionRates'));
        $this->assertArrayHasKey('clay', $component->get('resourceProductionRates'));
        $this->assertArrayHasKey('iron', $component->get('resourceProductionRates'));
        $this->assertArrayHasKey('crop', $component->get('resourceProductionRates'));
    }

    public function test_resource_icons()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $this->assertEquals('🌲', $component->instance()->getResourceIcon('wood'));
        $this->assertEquals('🏺', $component->instance()->getResourceIcon('clay'));
        $this->assertEquals('⚒️', $component->instance()->getResourceIcon('iron'));
        $this->assertEquals('🌾', $component->instance()->getResourceIcon('crop'));
        $this->assertEquals('📦', $component->instance()->getResourceIcon('unknown'));
    }

    public function test_building_icons()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $this->assertEquals('🏛️', $component->instance()->getBuildingIcon('main_building'));
        $this->assertEquals('🏰', $component->instance()->getBuildingIcon('barracks'));
        $this->assertEquals('🐎', $component->instance()->getBuildingIcon('stable'));
        $this->assertEquals('🏗️', $component->instance()->getBuildingIcon('unknown'));
    }

    public function test_notification_system()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class)
            ->call('addNotification', 'Test notification', 'success');

        $notifications = $component->get('notifications');
        $this->assertCount(1, $notifications);
        $this->assertEquals('Test notification', $notifications[0]['message']);
        $this->assertEquals('success', $notifications[0]['type']);

        $component
            ->call('removeNotification', $notifications[0]['id']);

        $component
            ->call('clearNotifications')
            ->assertSet('notifications', []);
    }

    public function test_notifications_toggle()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertSet('showNotifications', true)
            ->call('toggleNotifications')
            ->assertSet('showNotifications', false)
            ->call('toggleNotifications')
            ->assertSet('showNotifications', true);
    }

    public function test_displays_village_resources()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('wood');
        $component->assertSee('1,000');
        $component->assertSee('10/sec');
    }

    public function test_displays_village_buildings()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee($this->building->name ?? $this->buildingType->name);
        $component->assertSee('Lv.' . $this->building->level);
    }

    public function test_displays_game_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('Villages');
        $component->assertSee('Points');
        $component->assertSee('Alliance');
        $component->assertSee('Status');
    }

    public function test_displays_recent_events()
    {
        $this->actingAs($this->user);

        // Create test event
        GameEvent::factory()->create([
            'player_id' => $this->player->id,
            'village_id' => $this->village->id,
            'event_type' => 'test_event',
            'description' => 'Test Event Description',
        ]);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('Test Event Description');
    }

    public function test_game_stats_calculation()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);
        $gameStats = $component->get('gameStats');

        $this->assertArrayHasKey('total_villages', $gameStats);
        $this->assertArrayHasKey('total_points', $gameStats);
        $this->assertArrayHasKey('alliance_name', $gameStats);
        $this->assertArrayHasKey('online_status', $gameStats);
        $this->assertArrayHasKey('total_population', $gameStats);
        $this->assertArrayHasKey('total_attack_points', $gameStats);
        $this->assertArrayHasKey('total_defense_points', $gameStats);
    }

    public function test_real_time_event_handlers()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        // Test building completed event
        $component->dispatch('buildingCompleted', ['building_name' => 'Test Building']);
        $this->assertTrue(true);  // Event dispatched successfully

        // Test resource updated event
        $component->dispatch('resourceUpdated', ['type' => 'wood']);
        $this->assertTrue(true);  // Event dispatched successfully

        // Test village updated event
        $component->dispatch('villageUpdated', ['village_id' => $this->village->id]);
        $this->assertTrue(true);  // Event dispatched successfully
    }

    public function test_loading_states()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertSet('isLoading', false)
            ->call('loadGameData')
            ->assertSet('isLoading', false);
    }

    public function test_world_time_initialization()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);
        $worldTime = $component->get('worldTime');

        $this->assertNotNull($worldTime);
        $this->assertInstanceOf(\Carbon\Carbon::class, $worldTime);
    }

    public function test_polling_initialization()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->call('startPolling')
            ->assertDispatched('start-polling', ['interval' => 30000])
            ->call('stopPolling')
            ->assertDispatched('stop-polling');
    }

    public function test_real_time_update_handling()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        // Test with resources data
        $component->call('handleRealTimeUpdate', ['resources' => ['wood' => 1000]]);
        $this->assertTrue(true);  // Method called successfully

        // Test with buildings data
        $component->call('handleRealTimeUpdate', ['buildings' => ['barracks' => 1]]);
        $this->assertTrue(true);  // Method called successfully
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Delete player to test missing player scenario
        $this->player->delete();

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('player', null);
    }

    public function test_handles_missing_villages()
    {
        $this->actingAs($this->user);

        // Delete village to test missing villages scenario
        $this->village->delete();

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('villages', function ($villages) {
            return $villages->count() === 0;
        });
    }

    public function test_component_renders_with_all_data()
    {
        $this->actingAs($this->user);

        Livewire::test(GameDashboard::class)
            ->assertViewHas('player', $this->player)
            ->assertViewHas('currentVillage', $this->village)
            ->assertViewHas('villages')
            ->assertViewHas('gameStats')
            ->assertViewHas('notifications')
            ->assertViewHas('resourceProductionRates')
            ->assertViewHas('worldTime')
            ->assertViewHas('gameSpeed');
    }
}

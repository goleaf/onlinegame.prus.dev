<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\GameDashboard;
use App\Models\Game\Building;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $player;
    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
    }

    public function test_can_render_game_dashboard()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertStatus(200);
        $component->assertSee('Travian Game');
        $component->assertSee($this->player->name);
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
            'description' => 'Test Event'
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

    public function test_can_toggle_auto_refresh()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSet('autoRefresh', true);

        $component->call('toggleAutoRefresh');

        $component->assertSet('autoRefresh', false);
    }

    public function test_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(GameDashboard::class);

        $component->call('setRefreshInterval', 10);

        $component->assertSet('refreshInterval', 10);
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

    public function test_displays_village_resources()
    {
        $this->actingAs($this->user);

        // Create test resources
        Resource::factory()->create([
            'village_id' => $this->village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10
        ]);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('wood');
        $component->assertSee('1,000');
        $component->assertSee('10/sec');
    }

    public function test_displays_village_buildings()
    {
        $this->actingAs($this->user);

        // Create test building
        Building::factory()->create([
            'village_id' => $this->village->id,
            'name' => 'Test Building',
            'level' => 5
        ]);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('Test Building');
        $component->assertSee('Lv.5');
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
            'description' => 'Test Event Description'
        ]);

        $component = Livewire::test(GameDashboard::class);

        $component->assertSee('Test Event Description');
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
}

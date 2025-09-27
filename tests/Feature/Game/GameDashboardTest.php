<?php

namespace Tests\Feature\Game;

use App\Livewire\Game\GameDashboard;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_dashboard_loads_for_authenticated_user()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(GameDashboard::class)
            ->assertStatus(200)
            ->assertSee($player->name);
    }

    public function test_game_dashboard_redirects_unauthenticated_user()
    {
        Livewire::test(GameDashboard::class)
            ->assertRedirect('/login');
    }

    public function test_game_dashboard_shows_villages()
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

        Livewire::test(GameDashboard::class)
            ->assertSee($village->name);
    }

    public function test_game_dashboard_refreshes_data()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(GameDashboard::class)
            ->call('refreshGameData')
            ->assertDispatched('gameTickProcessed');
    }

    public function test_game_dashboard_toggles_auto_refresh()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(GameDashboard::class)
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false);
    }

    public function test_game_dashboard_sets_refresh_interval()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);

        Livewire::test(GameDashboard::class)
            ->call('setRefreshInterval', 10)
            ->assertSet('refreshInterval', 10);
    }

    public function test_game_dashboard_selects_village()
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

        Livewire::test(GameDashboard::class)
            ->call('selectVillage', $village->id)
            ->assertDispatched('villageSelected');
    }
}

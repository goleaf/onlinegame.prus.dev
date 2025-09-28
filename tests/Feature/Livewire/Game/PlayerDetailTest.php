<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\PlayerDetail;
use App\Models\Game\Battle;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_render_player_detail_component()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertStatus(200);
        $component->assertSee('Player Details');
    }

    /**
     * @test
     */
    public function it_loads_player_data_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('player.id', $player->id);
    }

    /**
     * @test
     */
    public function it_loads_player_villages_on_mount()
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

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('villages', function ($villages) use ($village) {
            return $villages->contains('id', $village->id);
        });
    }

    /**
     * @test
     */
    public function it_loads_recent_battles_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $battle = Battle::factory()->create([
            'attacker_player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('recentBattles', function ($battles) use ($battle) {
            return $battles->contains('id', $battle->id);
        });
    }

    /**
     * @test
     */
    public function it_loads_recent_movements_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $movement = Movement::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('recentMovements', function ($movements) use ($movement) {
            return $movements->contains('id', $movement->id);
        });
    }

    /**
     * @test
     */
    public function it_can_select_village()
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

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectVillage', $village->id);

        $component->assertSet('selectedVillage.id', $village->id);
        $component->assertSet('showVillageModal', true);
        $component->assertDispatched('fathom-track', name: 'village selected', value: $village->id);
    }

    /**
     * @test
     */
    public function it_can_select_battle()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $battle = Battle::factory()->create([
            'attacker_player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectBattle', $battle->id);

        $component->assertSet('selectedBattle.id', $battle->id);
        $component->assertSet('showBattleModal', true);
        $component->assertDispatched('fathom-track', name: 'battle selected', value: $battle->id);
    }

    /**
     * @test
     */
    public function it_can_select_movement()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $movement = Movement::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectMovement', $movement->id);

        $component->assertSet('selectedMovement.id', $movement->id);
        $component->assertSet('showMovementModal', true);
        $component->assertDispatched('fathom-track', name: 'movement selected', value: $movement->id);
    }

    /**
     * @test
     */
    public function it_can_close_village_modal()
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

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectVillage', $village->id);
        $component->call('closeVillageModal');

        $component->assertSet('showVillageModal', false);
        $component->assertSet('selectedVillage', null);
    }

    /**
     * @test
     */
    public function it_can_close_battle_modal()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $battle = Battle::factory()->create([
            'attacker_player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectBattle', $battle->id);
        $component->call('closeBattleModal');

        $component->assertSet('showBattleModal', false);
        $component->assertSet('selectedBattle', null);
    }

    /**
     * @test
     */
    public function it_can_close_movement_modal()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $movement = Movement::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('selectMovement', $movement->id);
        $component->call('closeMovementModal');

        $component->assertSet('showMovementModal', false);
        $component->assertSet('selectedMovement', null);
    }

    /**
     * @test
     */
    public function it_can_add_notification()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('addNotification', 'Test message', 'success');

        $component->assertSet('notifications', function ($notifications) {
            return count($notifications) === 1 && $notifications[0]['message'] === 'Test message';
        });
    }

    /**
     * @test
     */
    public function it_can_remove_notification()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('addNotification', 'Test message', 'success');
        $notificationId = $component->get('notifications')[0]['id'];
        $component->call('removeNotification', $notificationId);

        $component->assertSet('notifications', []);
    }

    /**
     * @test
     */
    public function it_can_clear_notifications()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('addNotification', 'Test message 1', 'success');
        $component->call('addNotification', 'Test message 2', 'info');
        $component->call('clearNotifications');

        $component->assertSet('notifications', []);
    }

    /**
     * @test
     */
    public function it_limits_notifications_to_last_10()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        // Add 15 notifications
        for ($i = 1; $i <= 15; $i++) {
            $component->call('addNotification', "Test message $i", 'info');
        }

        $component->assertSet('notifications', function ($notifications) {
            return count($notifications) === 10;
        });
    }

    /**
     * @test
     */
    public function it_can_toggle_real_time_updates()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('realTimeUpdates', true);
        $component->call('toggleRealTimeUpdates');
        $component->assertSet('realTimeUpdates', false);
    }

    /**
     * @test
     */
    public function it_can_toggle_auto_refresh()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertSet('autoRefresh', true);
        $component->call('toggleAutoRefresh');
        $component->assertSet('autoRefresh', false);
    }

    /**
     * @test
     */
    public function it_can_set_refresh_interval()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('setRefreshInterval', 10);
        $component->assertSet('refreshInterval', 10);
    }

    /**
     * @test
     */
    public function it_limits_refresh_interval_between_1_and_60()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('setRefreshInterval', 0);
        $component->assertSet('refreshInterval', 1);

        $component->call('setRefreshInterval', 100);
        $component->assertSet('refreshInterval', 60);
    }

    /**
     * @test
     */
    public function it_can_set_game_speed()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('setGameSpeed', 2.0);
        $component->assertSet('gameSpeed', 2.0);
    }

    /**
     * @test
     */
    public function it_limits_game_speed_between_0_5_and_3_0()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->call('setGameSpeed', 0.1);
        $component->assertSet('gameSpeed', 0.5);

        $component->call('setGameSpeed', 5.0);
        $component->assertSet('gameSpeed', 3.0);
    }

    /**
     * @test
     */
    public function it_returns_correct_battle_result_icon()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $this->assertEquals('🏆', $component->instance()->getBattleResultIcon('victory'));
        $this->assertEquals('💀', $component->instance()->getBattleResultIcon('defeat'));
        $this->assertEquals('🤝', $component->instance()->getBattleResultIcon('draw'));
        $this->assertEquals('❓', $component->instance()->getBattleResultIcon('unknown'));
    }

    /**
     * @test
     */
    public function it_returns_correct_movement_type_icon()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $this->assertEquals('⚔️', $component->instance()->getMovementTypeIcon('attack'));
        $this->assertEquals('🛡️', $component->instance()->getMovementTypeIcon('support'));
        $this->assertEquals('🏃', $component->instance()->getMovementTypeIcon('return'));
        $this->assertEquals('❓', $component->instance()->getMovementTypeIcon('unknown'));
    }

    /**
     * @test
     */
    public function it_handles_player_selected_event()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->dispatch('playerSelected', ['playerId' => $player->id]);

        // Should reload player data
        $component->assertSet('player.id', $player->id);
    }

    /**
     * @test
     */
    public function it_handles_battle_completed_event()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->dispatch('battleCompleted', [
            'player_id' => $player->id,
            'result' => 'victory',
            'loot' => 1000,
        ]);

        $component->assertSet('notifications', function ($notifications) {
            return count($notifications) === 1 &&
                str_contains($notifications[0]['message'], 'Battle completed: Victory! Loot: 1000');
        });
    }

    /**
     * @test
     */
    public function it_handles_movement_completed_event()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->dispatch('movementCompleted', [
            'player_id' => $player->id,
            'type' => 'attack',
            'target' => 'Village (100|100)',
        ]);

        $component->assertSet('notifications', function ($notifications) {
            return count($notifications) === 1 &&
                str_contains($notifications[0]['message'], 'Movement completed: Attack to Village (100|100)');
        });
    }

    /**
     * @test
     */
    public function it_renders_correct_view()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(PlayerDetail::class, ['player' => $player->id]);

        $component->assertViewIs('livewire.game.player-detail');
    }
}

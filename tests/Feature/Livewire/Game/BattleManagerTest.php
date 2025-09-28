<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\BattleManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BattleManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private World $world;

    private Village $village;

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
            'x_coordinate' => 100,
            'y_coordinate' => 100,
        ]);
    }

    public function test_can_render_battle_manager()
    {
        $this->actingAs($this->user);

        Livewire::test(BattleManager::class)
            ->assertStatus(200);
    }

    public function test_can_select_target_village()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
        ]);

        $this->actingAs($this->user);

        Livewire::test(BattleManager::class)
            ->call('selectTarget', $targetVillage->id)
            ->assertSet('selectedTarget.id', $targetVillage->id)
            ->assertSet('showBattleModal', true);
    }

    public function test_cannot_select_own_village_as_target()
    {
        $this->actingAs($this->user);

        Livewire::test(BattleManager::class)
            ->call('selectTarget', $this->village->id)
            ->assertNotSet('selectedTarget.id', $this->village->id)
            ->assertSet('showBattleModal', false);
    }

    public function test_cannot_select_invalid_village_as_target()
    {
        $this->actingAs($this->user);

        Livewire::test(BattleManager::class)
            ->call('selectTarget', 99999)
            ->assertNotSet('selectedTarget.id', 99999)
            ->assertSet('showBattleModal', false);
    }

    public function test_can_calculate_distance_between_villages()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('selectedTarget', $targetVillage);

        $distance = $component->call('calculateDistance');

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }

    public function test_can_calculate_real_world_distance()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $this->village->update([
            'latitude' => 52.520008,
            'longitude' => 13.404954,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('selectedTarget', $targetVillage);

        $distance = $component->call('calculateRealWorldDistance');

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }

    public function test_can_calculate_travel_time()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('selectedTarget', $targetVillage);
        $component->set('attackingTroops', [
            ['speed' => 10],
            ['speed' => 15],
        ]);

        $travelTime = $component->call('calculateTravelTime', 100);

        $this->assertIsInt($travelTime);
        $this->assertGreaterThan(0, $travelTime);
    }

    public function test_cannot_launch_attack_without_target()
    {
        $this->actingAs($this->user);

        Livewire::test(BattleManager::class)
            ->call('launchAttack')
            ->assertSet('selectedTarget', null);
    }

    public function test_cannot_launch_attack_without_troops()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('selectedTarget', $targetVillage);
        $component->set('attackingTroops', []);

        $component->call('launchAttack');

        // Should not create a movement
        $this->assertDatabaseMissing('movements', [
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
        ]);
    }
}

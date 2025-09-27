<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\MovementManager;
use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\Game\Movement;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MovementManagerTest extends TestCase
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
            'world_id' => $this->world->id
        ]);

        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 100,
            'y_coordinate' => 100
        ]);
    }

    public function test_can_render_movement_manager()
    {
        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->assertStatus(200);
    }

    public function test_can_create_movement()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->set('targetVillageId', $targetVillage->id)
            ->set('movementType', 'attack')
            ->set('selectedTroops', [1, 2])
            ->set('troopQuantities', [1 => 10, 2 => 5])
            ->call('createMovement')
            ->assertSet('targetVillageId', null);

        $this->assertDatabaseHas('movements', [
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'travelling'
        ]);
    }

    public function test_cannot_create_movement_to_same_village()
    {
        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->set('targetVillageId', $this->village->id)
            ->set('movementType', 'attack')
            ->set('selectedTroops', [1])
            ->set('troopQuantities', [1 => 10])
            ->call('createMovement');

        $this->assertDatabaseMissing('movements', [
            'from_village_id' => $this->village->id,
            'to_village_id' => $this->village->id
        ]);
    }

    public function test_cannot_create_movement_without_target()
    {
        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->set('movementType', 'attack')
            ->set('selectedTroops', [1])
            ->set('troopQuantities', [1 => 10])
            ->call('createMovement')
            ->assertHasErrors(['targetVillageId']);
    }

    public function test_cannot_create_movement_without_troops()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->set('targetVillageId', $targetVillage->id)
            ->set('movementType', 'attack')
            ->set('selectedTroops', [])
            ->set('troopQuantities', [])
            ->call('createMovement')
            ->assertHasErrors(['selectedTroops']);
    }

    public function test_can_cancel_movement()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        $movement = Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'travelling'
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->call('cancelMovement', $movement->id);

        $movement->refresh();
        $this->assertEquals('cancelled', $movement->status);
    }

    public function test_cannot_cancel_completed_movement()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        $movement = Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'completed'
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->call('cancelMovement', $movement->id);

        $movement->refresh();
        $this->assertEquals('completed', $movement->status);
    }

    public function test_can_filter_movements_by_type()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'travelling'
        ]);

        Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'reinforce',
            'status' => 'travelling'
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->call('filterByType', 'attack')
            ->assertSet('filterByType', 'attack');
    }

    public function test_can_filter_movements_by_status()
    {
        $targetVillage = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200
        ]);

        Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'travelling'
        ]);

        Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
            'status' => 'completed'
        ]);

        $this->actingAs($this->user);

        Livewire::test(MovementManager::class, ['village' => $this->village])
            ->call('filterByStatus', 'travelling')
            ->assertSet('filterByStatus', 'travelling');
    }
}
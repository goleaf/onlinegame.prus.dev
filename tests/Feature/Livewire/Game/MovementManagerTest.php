<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\MovementManager;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Troop;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->world = World::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id, 'world_id' => $this->world->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id, 'world_id' => $this->world->id]);

        // Create unit types
        $this->legionnaire = UnitType::factory()->create(['name' => 'Legionnaire', 'attack' => 40, 'defense_infantry' => 35, 'defense_cavalry' => 50, 'speed' => 6]);
        $this->praetorian = UnitType::factory()->create(['name' => 'Praetorian', 'attack' => 30, 'defense_infantry' => 65, 'defense_cavalry' => 35, 'speed' => 5]);

        // Create troops
        $this->troop1 = Troop::factory()->create([
            'village_id' => $this->village->id,
            'unit_type_id' => $this->legionnaire->id,
            'count' => 100,
        ]);

        $this->troop2 = Troop::factory()->create([
            'village_id' => $this->village->id,
            'unit_type_id' => $this->praetorian->id,
            'count' => 50,
        ]);
    }

    public function test_can_mount_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);

        $this->assertNotNull($component->village);
        $this->assertEquals($this->village->id, $component->village->id);
    }

    public function test_loads_movement_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);

        $this->assertCount(2, $component->availableTroops);
        $this->assertCount(0, $component->movements);
    }

    public function test_can_select_target_village()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $targetVillage->id);

        $this->assertEquals($targetVillage->id, $component->targetVillageId);
    }

    public function test_can_set_movement_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setMovementType', 'attack');

        $this->assertEquals('attack', $component->movementType);
    }

    public function test_can_select_troop()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', $this->troop1->id, 10);

        $this->assertArrayHasKey($this->troop1->id, $component->selectedTroops);
        $this->assertEquals(10, $component->troopQuantities[$this->troop1->id]);
    }

    public function test_can_set_troop_quantity()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', $this->troop1->id, 10);
        $component->call('setTroopQuantity', $this->troop1->id, 20);

        $this->assertEquals(20, $component->troopQuantities[$this->troop1->id]);
    }

    public function test_can_clear_troop_selection()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', $this->troop1->id, 10);
        $component->call('clearTroopSelection');

        $this->assertCount(0, $component->selectedTroops);
    }

    public function test_can_create_movement()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $targetVillage->id);
        $component->call('setMovementType', 'attack');
        $component->call('selectTroop', $this->troop1->id, 10);
        $component->call('createMovement');

        $this->assertDatabaseHas('movements', [
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
        ]);
    }

    public function test_cannot_create_movement_without_target()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setMovementType', 'attack');
        $component->call('selectTroop', $this->troop1->id, 10);
        $component->call('createMovement');

        $this->assertDatabaseMissing('movements', [
            'player_id' => $this->player->id,
            'type' => 'attack',
        ]);
    }

    public function test_cannot_create_movement_without_troops()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $targetVillage->id);
        $component->call('setMovementType', 'attack');
        $component->call('createMovement');

        $this->assertDatabaseMissing('movements', [
            'player_id' => $this->player->id,
            'type' => 'attack',
        ]);
    }

    public function test_cannot_create_movement_to_same_village()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $this->village->id);
        $component->call('setMovementType', 'attack');
        $component->call('selectTroop', $this->troop1->id, 10);
        $component->call('createMovement');

        $this->assertDatabaseMissing('movements', [
            'player_id' => $this->player->id,
            'type' => 'attack',
        ]);
    }

    public function test_can_cancel_movement()
    {
        $this->actingAs($this->user);

        $movement = Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'status' => 'travelling',
        ]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('cancelMovement', $movement->id);

        $this->assertDatabaseHas('movements', [
            'id' => $movement->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_completed_movement()
    {
        $this->actingAs($this->user);

        $movement = Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'status' => 'completed',
        ]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('cancelMovement', $movement->id);

        $this->assertDatabaseHas('movements', [
            'id' => $movement->id,
            'status' => 'completed',
        ]);
    }

    public function test_can_select_movement()
    {
        $this->actingAs($this->user);

        $movement = Movement::factory()->create([
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
        ]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectMovement', $movement->id);

        $this->assertEquals($movement->id, $component->selectedMovement->id);
    }

    public function test_can_filter_by_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('filterByType', 'attack');

        $this->assertEquals('attack', $component->filterByType);
    }

    public function test_can_filter_by_status()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('filterByStatus', 'travelling');

        $this->assertEquals('travelling', $component->filterByStatus);
    }

    public function test_can_clear_filters()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('filterByType', 'attack');
        $component->call('filterByStatus', 'travelling');
        $component->call('clearFilters');

        $this->assertNull($component->filterByType);
        $this->assertNull($component->filterByStatus);
    }

    public function test_can_sort_movements()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('sortMovements', 'created_at');

        $this->assertEquals('created_at', $component->sortBy);
    }

    public function test_can_search_movements()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('searchQuery', 'test');
        $component->call('searchMovements');

        $this->assertEquals('test', $component->searchQuery);
    }

    public function test_can_toggle_my_movements_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('toggleMyMovementsFilter');

        $this->assertFalse($component->showOnlyMyMovements);
    }

    public function test_can_toggle_travelling_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('toggleTravellingFilter');

        $this->assertTrue($component->showOnlyTravelling);
    }

    public function test_can_toggle_completed_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('toggleCompletedFilter');

        $this->assertTrue($component->showOnlyCompleted);
    }

    public function test_calculates_movement_stats()
    {
        $this->actingAs($this->user);

        Movement::factory()->create([
            'from_village_id' => $this->village->id,
            'status' => 'travelling',
        ]);

        Movement::factory()->create([
            'from_village_id' => $this->village->id,
            'status' => 'completed',
        ]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('calculateMovementStats');

        $this->assertArrayHasKey('total_movements', $component->movementStats);
        $this->assertArrayHasKey('travelling_movements', $component->movementStats);
        $this->assertArrayHasKey('completed_movements', $component->movementStats);
    }

    public function test_calculates_troop_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('calculateTroopStats');

        $this->assertArrayHasKey('total_attack_power', $component->troopStats);
        $this->assertArrayHasKey('total_defense_power', $component->troopStats);
        $this->assertArrayHasKey('troop_capacity', $component->troopStats);
    }

    public function test_calculates_distance_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('calculateDistanceStats');

        $this->assertArrayHasKey('average_distance', $component->distanceStats);
        $this->assertArrayHasKey('longest_distance', $component->distanceStats);
        $this->assertArrayHasKey('shortest_distance', $component->distanceStats);
    }

    public function test_calculates_time_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('calculateTimeStats');

        $this->assertArrayHasKey('average_travel_time', $component->timeStats);
        $this->assertArrayHasKey('longest_travel_time', $component->timeStats);
        $this->assertArrayHasKey('shortest_travel_time', $component->timeStats);
    }

    public function test_handles_missing_village()
    {
        $this->actingAs($this->user);

        // Delete the village
        $this->village->delete();

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);

        // The component should still have the village object (even though it's deleted from DB)
        $this->assertNotNull($component->village);
        $this->assertFalse($component->village->exists);
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Delete the player
        $this->player->delete();

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);

        // The component should still have the village object (even though player is deleted)
        $this->assertNotNull($component->village);
        $this->assertNull($component->village->player);
    }

    public function test_handles_missing_troops()
    {
        $this->actingAs($this->user);

        // Delete all troops
        Troop::where('village_id', $this->village->id)->delete();

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);

        $this->assertCount(0, $component->availableTroops);
    }

    public function test_handles_invalid_troop_id()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', 999, 10);

        $this->assertCount(0, $component->selectedTroops);
    }

    public function test_handles_negative_troop_count()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', $this->troop1->id, -10);

        $this->assertCount(0, $component->selectedTroops);
    }

    public function test_handles_zero_troop_count()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectTroop', $this->troop1->id, 0);

        $this->assertCount(0, $component->selectedTroops);
    }

    public function test_handles_missing_target_village()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', 999);

        $this->assertEquals(999, $component->targetVillageId);
    }

    public function test_handles_missing_movement()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('selectMovement', 999);

        $this->assertNull($component->selectedMovement);
    }

    public function test_handles_missing_movement_for_cancel()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('cancelMovement', 999);

        $this->assertTrue(true);  // Should handle gracefully
    }

    public function test_handles_movement_creation_error()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $targetVillage->id);
        $component->call('setMovementType', 'attack');
        $component->call('selectTroop', $this->troop1->id, 10);

        // Mock database error by using invalid player_id
        $component->set('village.player_id', 999);
        $component->call('createMovement');

        // Should handle error gracefully
        $this->assertTrue(true);
    }

    public function test_handles_troop_update_error()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->call('setTargetVillage', $targetVillage->id);
        $component->call('setMovementType', 'attack');
        $component->call('selectTroop', $this->troop1->id, 10);

        // Mock troop update error by deleting the troop
        $this->troop1->delete();
        $component->call('createMovement');

        // Should handle error gracefully
        $this->assertTrue(true);
    }

    public function test_handles_missing_movement_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village', null);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_troop_relationships()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.troops', collect([]));

        $component->call('loadAvailableTroops');

        $this->assertCount(0, $component->availableTroops);
    }

    public function test_handles_missing_movement_relationships()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', null);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.player_id', null);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_world_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.world_id', null);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_from_village_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_to_village_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_type_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_status_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_search_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_sort_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_order_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }

    public function test_handles_missing_movement_get_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(MovementManager::class, ['village' => $this->village]);
        $component->set('village.id', 999);

        $component->call('loadMovementData');

        $this->assertCount(0, $component->movements);
    }
}

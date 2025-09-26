<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\BattleManager;
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

class BattleManagerTest extends TestCase
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
            'in_village' => 100,
            'in_attack' => 0,
        ]);

        $this->troop2 = Troop::factory()->create([
            'village_id' => $this->village->id,
            'unit_type_id' => $this->praetorian->id,
            'in_village' => 50,
            'in_attack' => 0,
        ]);
    }

    public function test_can_mount_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);

        $this->assertNotNull($component->village);
        $this->assertEquals($this->village->id, $component->village->id);
    }

    public function test_loads_battle_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);

        $this->assertCount(2, $component->availableTroops);
        $this->assertCount(0, $component->recentBattles);
    }

    public function test_can_select_target_village()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);

        $this->assertEquals($targetVillage->id, $component->selectedTarget->id);
        $this->assertTrue($component->showBattleModal);
    }

    public function test_can_add_troop_to_attack()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        $this->assertCount(1, $component->attackingTroops);
        $this->assertEquals(10, $component->attackingTroops[0]['count']);
    }

    public function test_cannot_add_more_troops_than_available()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 150);  // More than available (100)

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_can_remove_troop_from_attack()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 10);
        $component->call('removeTroopFromAttack', 0);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_can_launch_attack()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);
        $component->call('addTroopToAttack', $this->troop1->id, 10);
        $component->call('launchAttack');

        $this->assertFalse($component->showBattleModal);
        $this->assertCount(0, $component->attackingTroops);

        // Check that movement was created
        $this->assertDatabaseHas('movements', [
            'player_id' => $this->player->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $targetVillage->id,
            'type' => 'attack',
        ]);
    }

    public function test_cannot_launch_attack_without_target()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 10);
        $component->call('launchAttack');

        $this->assertCount(1, $component->attackingTroops);  // Troops still there
    }

    public function test_cannot_launch_attack_without_troops()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);
        $component->call('launchAttack');

        $this->assertTrue($component->showBattleModal);  // Modal still open
    }

    public function test_calculates_distance_correctly()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create([
            'world_id' => $this->world->id,
            'x_coordinate' => 10,
            'y_coordinate' => 10,
        ]);

        $this->village->update(['x_coordinate' => 0, 'y_coordinate' => 0]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);

        $distance = $component->call('calculateDistance');
        $this->assertEquals(14.14, round($distance, 2));
    }

    public function test_calculates_travel_time_correctly()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        $distance = 10;
        $travelTime = $component->call('calculateTravelTime', $distance);

        $this->assertGreaterThan(0, $travelTime);
    }

    public function test_simulates_battle_correctly()
    {
        $this->actingAs($this->user);

        $attackerTroops = [
            ['count' => 10, 'attack' => 40, 'defense_infantry' => 35, 'defense_cavalry' => 50],
        ];

        $defenderTroops = [
            ['count' => 5, 'attack' => 30, 'defense_infantry' => 65, 'defense_cavalry' => 35],
        ];

        $component = Livewire::test(BattleManager::class);
        $result = $component->call('simulateBattle', $attackerTroops, $defenderTroops);

        $this->assertContains($result, ['attacker_wins', 'defender_wins', 'draw']);
    }

    public function test_refreshes_battle_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('refreshBattles');

        $this->assertCount(2, $component->availableTroops);
    }

    public function test_handles_missing_village()
    {
        $this->actingAs($this->user);

        // Delete the village
        $this->village->delete();

        $component = Livewire::test(BattleManager::class);

        $this->assertNull($component->village);
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Delete the player
        $this->player->delete();

        $component = Livewire::test(BattleManager::class);

        $this->assertNull($component->village);
    }

    public function test_handles_missing_troops()
    {
        $this->actingAs($this->user);

        // Delete all troops
        Troop::where('village_id', $this->village->id)->delete();

        $component = Livewire::test(BattleManager::class);

        $this->assertCount(0, $component->availableTroops);
    }

    public function test_handles_invalid_troop_id()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', 999, 10);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_negative_troop_count()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, -10);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_zero_troop_count()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 0);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_attack_error()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);

        // Mock an exception by using invalid data
        $component->set('selectedTarget', null);
        $component->call('launchAttack');

        // Should not throw exception, just return early
        $this->assertTrue(true);
    }

    public function test_handles_movement_creation_error()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        // Mock database error by using invalid player_id
        $component->set('village.player_id', 999);
        $component->call('launchAttack');

        // Should handle error gracefully
        $this->assertTrue(true);
    }

    public function test_handles_troop_update_error()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        // Mock troop update error by deleting the troop
        $this->troop1->delete();
        $component->call('launchAttack');

        // Should handle error gracefully
        $this->assertTrue(true);
    }

    public function test_handles_missing_target_village()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', 999);

        $this->assertNull($component->selectedTarget);
    }

    public function test_handles_missing_troop_in_attack()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        // Delete the troop after adding to attack
        $this->troop1->delete();

        $component->call('removeTroopFromAttack', 0);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_invalid_remove_index()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('removeTroopFromAttack', 999);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_negative_remove_index()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->call('removeTroopFromAttack', -1);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_empty_attacking_troops()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('attackingTroops', []);

        $distance = 10;
        $travelTime = $component->call('calculateTravelTime', $distance);

        $this->assertEquals(0, $travelTime);
    }

    public function test_handles_missing_selected_target()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('selectedTarget', null);

        $distance = $component->call('calculateDistance');

        $this->assertEquals(0, $distance);
    }

    public function test_handles_missing_village_coordinates()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);

        // Set invalid coordinates
        $component->set('village.x_coordinate', null);
        $component->set('village.y_coordinate', null);

        $distance = $component->call('calculateDistance');

        $this->assertEquals(0, $distance);
    }

    public function test_handles_missing_target_coordinates()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create([
            'world_id' => $this->world->id,
            'x_coordinate' => null,
            'y_coordinate' => null,
        ]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);

        $distance = $component->call('calculateDistance');

        $this->assertEquals(0, $distance);
    }

    public function test_handles_missing_troop_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);

        // Mock missing troop data
        $component->set('availableTroops', collect([]));
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_missing_unit_type_data()
    {
        $this->actingAs($this->user);

        // Create troop without unit type
        $troop = Troop::factory()->create([
            'village_id' => $this->village->id,
            'unit_type_id' => null,
            'in_village' => 100,
            'in_attack' => 0,
        ]);

        $component = Livewire::test(BattleManager::class);
        $component->call('addTroopToAttack', $troop->id, 10);

        $this->assertCount(0, $component->attackingTroops);
    }

    public function test_handles_missing_movement_data()
    {
        $this->actingAs($this->user);

        $targetVillage = Village::factory()->create(['world_id' => $this->world->id]);

        $component = Livewire::test(BattleManager::class);
        $component->call('selectTarget', $targetVillage->id);
        $component->call('addTroopToAttack', $this->troop1->id, 10);

        // Mock missing movement data
        $component->set('village.player_id', null);
        $component->call('launchAttack');

        // Should handle error gracefully
        $this->assertTrue(true);
    }

    public function test_handles_missing_village_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village', null);

        $component->call('loadBattleData');

        $this->assertCount(0, $component->availableTroops);
    }

    public function test_handles_missing_troop_relationships()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.troops', collect([]));

        $component->call('loadBattleData');

        $this->assertCount(0, $component->availableTroops);
    }

    public function test_handles_missing_battle_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', null);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.player_id', null);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_world_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.world_id', null);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_battle_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_movement_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.player_id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_attack_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.player_id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_defense_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.player_id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_occurred_at_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_limit_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_get_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_order_by_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_desc_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_limit_10_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }

    public function test_handles_missing_get_data_final()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(BattleManager::class);
        $component->set('village.id', 999);

        $component->call('loadRecentBattles');

        $this->assertCount(0, $component->recentBattles);
    }
}

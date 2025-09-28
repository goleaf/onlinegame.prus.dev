<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\BattleSimulator;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BattleSimulatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
    }

    /**
     * @test
     */
    public function it_can_render_battle_simulator()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_battle_simulator_interface()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->assertSee('Battle Simulator')
            ->assertSee('Attacker Configuration')
            ->assertSee('Defender Configuration')
            ->assertSee('Battle Settings');
    }

    /**
     * @test
     */
    public function it_can_configure_attacker_army()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('attackerArmy', [
                'infantry' => 100,
                'cavalry' => 50,
                'archers' => 75,
            ])
            ->call('updateAttackerArmy')
            ->assertSee('Attacker army updated')
            ->assertEmitted('attackerArmyUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_defender_army()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('defenderArmy', [
                'infantry' => 80,
                'cavalry' => 40,
                'archers' => 60,
            ])
            ->call('updateDefenderArmy')
            ->assertSee('Defender army updated')
            ->assertEmitted('defenderArmyUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_battle_settings()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('battleSettings', [
                'terrain' => 'plains',
                'weather' => 'clear',
                'time_of_day' => 'day',
                'fortification_level' => 0,
            ])
            ->call('updateBattleSettings')
            ->assertSee('Battle settings updated')
            ->assertEmitted('battleSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_run_battle_simulation()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('attackerArmy', [
                'infantry' => 100,
                'cavalry' => 50,
                'archers' => 75,
            ])
            ->set('defenderArmy', [
                'infantry' => 80,
                'cavalry' => 40,
                'archers' => 60,
            ])
            ->call('runSimulation')
            ->assertSee('Battle simulation completed')
            ->assertEmitted('battleSimulationCompleted');
    }

    /**
     * @test
     */
    public function it_can_display_battle_results()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->assertSee('Battle Results')
            ->assertSee('Winner')
            ->assertSee('Casualties')
            ->assertSee('Battle Duration');
    }

    /**
     * @test
     */
    public function it_can_display_detailed_battle_report()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->call('showDetailedReport')
            ->assertSee('Detailed Battle Report')
            ->assertSee('Round by Round Analysis')
            ->assertSee('Unit Performance')
            ->assertSee('Tactical Analysis');
    }

    /**
     * @test
     */
    public function it_can_save_battle_simulation()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->call('saveSimulation', 'Test Battle')
            ->assertSee('Battle simulation saved')
            ->assertEmitted('battleSimulationSaved');
    }

    /**
     * @test
     */
    public function it_can_load_saved_simulation()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('loadSimulation', 1)
            ->assertSee('Battle simulation loaded')
            ->assertEmitted('battleSimulationLoaded');
    }

    /**
     * @test
     */
    public function it_can_export_battle_results()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->call('exportResults')
            ->assertEmitted('battleResultsExported');
    }

    /**
     * @test
     */
    public function it_can_configure_attacker_bonuses()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('attackerBonuses', [
                'attack_bonus' => 0.1,
                'defense_bonus' => 0.05,
                'speed_bonus' => 0.15,
            ])
            ->call('updateAttackerBonuses')
            ->assertSee('Attacker bonuses updated')
            ->assertEmitted('attackerBonusesUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_defender_bonuses()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('defenderBonuses', [
                'attack_bonus' => 0.05,
                'defense_bonus' => 0.2,
                'speed_bonus' => 0.1,
            ])
            ->call('updateDefenderBonuses')
            ->assertSee('Defender bonuses updated')
            ->assertEmitted('defenderBonusesUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_battle_terrain()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('terrain', 'mountains')
            ->call('updateTerrain')
            ->assertSee('Terrain updated to mountains')
            ->assertEmitted('terrainUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_weather_conditions()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('weather', 'rain')
            ->call('updateWeather')
            ->assertSee('Weather updated to rain')
            ->assertEmitted('weatherUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_battle_time()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('timeOfDay', 'night')
            ->call('updateTimeOfDay')
            ->assertSee('Time of day updated to night')
            ->assertEmitted('timeOfDayUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_fortification_level()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('fortificationLevel', 5)
            ->call('updateFortificationLevel')
            ->assertSee('Fortification level updated to 5')
            ->assertEmitted('fortificationLevelUpdated');
    }

    /**
     * @test
     */
    public function it_can_run_multiple_simulations()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('simulationCount', 10)
            ->call('runMultipleSimulations')
            ->assertSee('Running 10 simulations...')
            ->assertEmitted('multipleSimulationsCompleted');
    }

    /**
     * @test
     */
    public function it_can_display_simulation_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runMultipleSimulations')
            ->call('showStatistics')
            ->assertSee('Simulation Statistics')
            ->assertSee('Win Rate')
            ->assertSee('Average Casualties')
            ->assertSee('Battle Duration Average');
    }

    /**
     * @test
     */
    public function it_can_compare_different_army_compositions()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('compareArmyCompositions')
            ->assertSee('Army Composition Comparison')
            ->assertSee('Infantry vs Cavalry')
            ->assertSee('Archers vs Infantry')
            ->assertSee('Mixed Army Analysis');
    }

    /**
     * @test
     */
    public function it_can_analyze_battle_tactics()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->call('analyzeTactics')
            ->assertSee('Tactical Analysis')
            ->assertSee('Optimal Strategy')
            ->assertSee('Tactical Recommendations');
    }

    /**
     * @test
     */
    public function it_can_simulate_historical_battles()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('simulateHistoricalBattle', 'Battle of Hastings')
            ->assertSee('Historical Battle: Battle of Hastings')
            ->assertEmitted('historicalBattleSimulated');
    }

    /**
     * @test
     */
    public function it_can_configure_battle_ai()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->set('aiSettings', [
                'ai_difficulty' => 'hard',
                'ai_aggression' => 0.8,
                'ai_intelligence' => 0.9,
            ])
            ->call('updateAISettings')
            ->assertSee('AI settings updated')
            ->assertEmitted('aiSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_run_battle_with_ai_opponent()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runAIBattle')
            ->assertSee('AI Battle Simulation')
            ->assertEmitted('aiBattleCompleted');
    }

    /**
     * @test
     */
    public function it_can_handle_battle_errors()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('runSimulation')
            ->call('handleError', 'Simulation error occurred')
            ->assertSee('Battle Error: Simulation error occurred')
            ->assertEmitted('battleErrorOccurred');
    }

    /**
     * @test
     */
    public function it_can_reset_battle_simulator()
    {
        Livewire::actingAs($this->user)
            ->test(BattleSimulator::class)
            ->call('resetSimulator')
            ->assertSee('Battle simulator reset')
            ->assertEmitted('battleSimulatorReset');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(BattleSimulator::class)
            ->assertSee('Please login to access Battle Simulator');
    }
}

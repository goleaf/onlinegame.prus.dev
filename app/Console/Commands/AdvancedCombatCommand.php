<?php

namespace App\Console\Commands;

use App\Models\Game\Battle;
use App\Models\Game\Hero;
use App\Models\Game\Player;
use App\Models\Game\SiegeWeapon;
use App\Models\Game\Village;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdvancedCombatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combat:advanced 
                            {action : Action to perform (heroes|siege|reports|simulate)}
                            {--player-id= : Specific player ID}
                            {--village-id= : Specific village ID}
                            {--force : Force the operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage advanced combat features - heroes, siege weapons, battle reports, and combat simulation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        $this->info('⚔️ Advanced Combat System');
        $this->info('========================');

        switch ($action) {
            case 'heroes':
                $this->manageHeroes();
                break;
            case 'siege':
                $this->manageSiegeWeapons();
                break;
            case 'reports':
                $this->generateBattleReports();
                break;
            case 'simulate':
                $this->simulateCombat();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Manage hero system.
     */
    protected function manageHeroes(): void
    {
        $this->info('🦸 Managing hero system...');

        $playerId = $this->option('player-id');

        $query = Player::with(['villages', 'hero']);

        if ($playerId) {
            $query->where('id', $playerId);
        }

        $players = $query->get();

        $heroesCreated = 0;

        foreach ($players as $player) {
            if (!$player->hero) {
                $hero = $this->createHeroForPlayer($player);
                if ($hero) {
                    $heroesCreated++;
                    $this->line("  → Created hero '{$hero->name}' for {$player->name}");
                }
            }
        }

        $this->info("✅ Created {$heroesCreated} heroes");
    }

    /**
     * Create a hero for a player.
     */
    protected function createHeroForPlayer(Player $player): ?Hero
    {
        $heroNames = [
            'roman' => ['Marcus Aurelius', 'Julius Caesar', 'Augustus', 'Trajan', 'Hadrian'],
            'teuton' => ['Siegfried', 'Beowulf', 'Thor', 'Odin', 'Ragnar'],
            'gaul' => ['Vercingetorix', 'Asterix', 'Obelix', 'Druid', 'Bard'],
            'natars' => ['Natarian Guardian', 'Ancient Warrior', 'Mystic Defender', 'Shadow Knight', 'Crystal Sage']
        ];

        $tribe = $player->tribe;
        $names = $heroNames[$tribe] ?? $heroNames['roman'];
        $name = $names[array_rand($names)];

        return Hero::create([
            'player_id' => $player->id,
            'name' => $name,
            'level' => 1,
            'experience' => 0,
            'attack_power' => 100,
            'defense_power' => 100,
            'health' => 1000,
            'max_health' => 1000,
            'special_abilities' => json_encode($this->getHeroAbilities($tribe)),
            'equipment' => json_encode([]),
            'is_active' => true,
        ]);
    }

    /**
     * Get hero abilities based on tribe.
     */
    protected function getHeroAbilities(string $tribe): array
    {
        $abilities = [
            'roman' => [
                'legion_leadership' => ['name' => 'Legion Leadership', 'description' => 'Increases infantry attack by 20%'],
                'tactical_brilliance' => ['name' => 'Tactical Brilliance', 'description' => 'Reduces enemy defense by 15%']
            ],
            'teuton' => [
                'berserker_rage' => ['name' => 'Berserker Rage', 'description' => 'Increases attack power by 30% when health is low'],
                'fearless_charge' => ['name' => 'Fearless Charge', 'description' => 'Cavalry units move 25% faster']
            ],
            'gaul' => [
                'nature_blessing' => ['name' => 'Nature Blessing', 'description' => 'Increases resource production by 15%'],
                'druidic_wisdom' => ['name' => 'Druidic Wisdom', 'description' => 'Reduces building construction time by 20%']
            ],
            'natars' => [
                'ancient_power' => ['name' => 'Ancient Power', 'description' => 'All units gain 25% bonus to all stats'],
                'mystic_shield' => ['name' => 'Mystic Shield', 'description' => 'Reduces incoming damage by 20%']
            ]
        ];

        return $abilities[$tribe] ?? $abilities['roman'];
    }

    /**
     * Manage siege weapons.
     */
    protected function manageSiegeWeapons(): void
    {
        $this->info('🏰 Managing siege weapons...');

        $villageId = $this->option('village-id');

        $query = Village::with(['player', 'siegeWeapons']);

        if ($villageId) {
            $query->where('id', $villageId);
        }

        $villages = $query->get();

        $siegeWeaponsCreated = 0;

        foreach ($villages as $village) {
            if ($village->siegeWeapons->isEmpty()) {
                $created = $this->createSiegeWeaponsForVillage($village);
                $siegeWeaponsCreated += $created;
            }
        }

        $this->info("✅ Created {$siegeWeaponsCreated} siege weapons");
    }

    /**
     * Create siege weapons for a village.
     */
    protected function createSiegeWeaponsForVillage(Village $village): int
    {
        $siegeWeaponTypes = [
            'ram' => [
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'cost' => ['wood' => 300, 'clay' => 200, 'iron' => 200, 'crop' => 100],
                'description' => 'Effective against walls and gates'
            ],
            'catapult' => [
                'name' => 'Catapult',
                'attack_power' => 100,
                'defense_power' => 30,
                'health' => 300,
                'cost' => ['wood' => 320, 'clay' => 400, 'iron' => 100, 'crop' => 100],
                'description' => 'Long-range siege weapon for destroying buildings'
            ],
            'trebuchet' => [
                'name' => 'Trebuchet',
                'attack_power' => 150,
                'defense_power' => 40,
                'health' => 400,
                'cost' => ['wood' => 500, 'clay' => 600, 'iron' => 200, 'crop' => 150],
                'description' => 'Most powerful siege weapon, slow but devastating'
            ]
        ];

        $created = 0;

        foreach ($siegeWeaponTypes as $key => $weapon) {
            SiegeWeapon::create([
                'village_id' => $village->id,
                'type' => $key,
                'name' => $weapon['name'],
                'attack_power' => $weapon['attack_power'],
                'defense_power' => $weapon['defense_power'],
                'health' => $weapon['health'],
                'max_health' => $weapon['health'],
                'cost' => json_encode($weapon['cost']),
                'description' => $weapon['description'],
                'is_active' => true,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Generate detailed battle reports.
     */
    protected function generateBattleReports(): void
    {
        $this->info('📊 Generating battle reports...');

        $battles = Battle::with(['attacker', 'defender', 'village'])
            ->where('occurred_at', '>=', now()->subDays(7))
            ->orderBy('occurred_at', 'desc')
            ->get();

        $this->info("Found {$battles->count()} recent battles");

        foreach ($battles as $battle) {
            $this->generateDetailedReport($battle);
        }

        $this->info('✅ Battle reports generated');
    }

    /**
     * Generate detailed report for a battle.
     */
    protected function generateDetailedReport(Battle $battle): void
    {
        $battleData = $battle->battle_data ?? [];

        $this->line("  → Battle #{$battle->id}:");
        $this->line("    Attacker: {$battle->attacker->name}");
        $this->line("    Defender: {$battle->defender->name}");
        $this->line("    Result: {$battle->result}");
        $this->line("    Village: {$battle->village->name}");

        if (isset($battleData['attacking_troops'])) {
            $this->line('    Attacking Troops: ' . count($battleData['attacking_troops']));
        }

        if (isset($battleData['defending_troops'])) {
            $this->line('    Defending Troops: ' . count($battleData['defending_troops']));
        }

        if (isset($battleData['battle_power'])) {
            $this->line("    Battle Power: {$battleData['battle_power']}");
        }

        if (isset($battleData['defensive_bonus'])) {
            $this->line('    Defensive Bonus: ' . ($battleData['defensive_bonus'] * 100) . '%');
        }
    }

    /**
     * Simulate combat scenarios.
     */
    protected function simulateCombat(): void
    {
        $this->info('🎯 Simulating combat scenarios...');

        $villages = Village::with(['player', 'troops.unitType', 'buildings.buildingType'])
            ->whereHas('troops', function ($query) {
                $query->where('count', '>', 0);
            })
            ->limit(10)
            ->get();

        $simulations = 0;

        foreach ($villages as $attacker) {
            $targets = Village::with(['player', 'troops.unitType', 'buildings.buildingType'])
                ->where('id', '!=', $attacker->id)
                ->where('player_id', '!=', $attacker->player_id)
                ->limit(3)
                ->get();

            foreach ($targets as $defender) {
                $result = $this->simulateBattle($attacker, $defender);
                $this->line("  → {$attacker->name} vs {$defender->name}: {$result['outcome']}");
                $simulations++;
            }
        }

        $this->info("✅ Completed {$simulations} combat simulations");
    }

    /**
     * Simulate a battle between two villages.
     */
    protected function simulateBattle(Village $attacker, Village $defender): array
    {
        $attackingTroops = $attacker
            ->troops()
            ->with('unitType')
            ->where('count', '>', 0)
            ->get()
            ->map(function ($troop) {
                return [
                    'count' => $troop->count,
                    'attack' => $troop->unitType->attack,
                    'defense_infantry' => $troop->unitType->defense_infantry,
                    'defense_cavalry' => $troop->unitType->defense_cavalry,
                ];
            })
            ->toArray();

        $defendingTroops = $defender
            ->troops()
            ->with('unitType')
            ->where('count', '>', 0)
            ->get()
            ->map(function ($troop) {
                return [
                    'count' => $troop->count,
                    'attack' => $troop->unitType->attack,
                    'defense_infantry' => $troop->unitType->defense_infantry,
                    'defense_cavalry' => $troop->unitType->defense_cavalry,
                ];
            })
            ->toArray();

        $attackerPower = 0;
        $defenderPower = 0;

        foreach ($attackingTroops as $troop) {
            $attackerPower += $troop['count'] * $troop['attack'];
        }

        foreach ($defendingTroops as $troop) {
            $defenderPower += $troop['count'] * ($troop['defense_infantry'] + $troop['defense_cavalry']);
        }

        // Add randomness
        $attackerPower *= (0.8 + (rand(0, 40) / 100));
        $defenderPower *= (0.8 + (rand(0, 40) / 100));

        $outcome = 'draw';
        if ($attackerPower > $defenderPower * 1.1) {
            $outcome = 'attacker_wins';
        } elseif ($defenderPower > $attackerPower * 1.1) {
            $outcome = 'defender_wins';
        }

        return [
            'outcome' => $outcome,
            'attacker_power' => $attackerPower,
            'defender_power' => $defenderPower,
            'attacking_troops' => $attackingTroops,
            'defending_troops' => $defendingTroops,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\Movement;
use App\Models\Game\Report;
use App\Services\BattleSimulationService;
use App\Services\DefenseCalculationService;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllianceWarfareService
{
    protected $battleService;
    protected $defenseService;
    protected $rabbitMQ;

    public function __construct()
    {
        $this->battleService = new BattleSimulationService();
        $this->defenseService = new DefenseCalculationService();
        $this->rabbitMQ = new RabbitMQService();
    }

    /**
     * Declare war between two alliances
     */
    public function declareWar(Alliance $attackerAlliance, Alliance $defenderAlliance, string $reason = ''): array
    {
        DB::beginTransaction();
        
        try {
            // Create war record
            $war = \App\Models\Game\AllianceWar::create([
                'attacker_alliance_id' => $attackerAlliance->id,
                'defender_alliance_id' => $defenderAlliance->id,
                'reason' => $reason,
                'status' => 'active',
                'declared_at' => now(),
                'war_score' => 0,
            ]);

            // Notify all members of both alliances
            $this->notifyAllianceMembers($attackerAlliance, 'war_declared', [
                'war_id' => $war->id,
                'enemy_alliance' => $defenderAlliance->name,
                'reason' => $reason,
            ]);

            $this->notifyAllianceMembers($defenderAlliance, 'war_declared_against', [
                'war_id' => $war->id,
                'enemy_alliance' => $attackerAlliance->name,
                'reason' => $reason,
            ]);

            // Publish war declaration event
            $this->rabbitMQ->publishGameEvent('alliance_war', [
                'event_type' => 'war_declared',
                'war_id' => $war->id,
                'attacker_alliance_id' => $attackerAlliance->id,
                'defender_alliance_id' => $defenderAlliance->id,
                'reason' => $reason,
                'timestamp' => now()->toISOString(),
            ]);

            DB::commit();

            Log::info("War declared between alliances {$attackerAlliance->name} and {$defenderAlliance->name}");

            return [
                'success' => true,
                'war_id' => $war->id,
                'message' => "War declared successfully between {$attackerAlliance->name} and {$defenderAlliance->name}",
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to declare war: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to declare war: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process alliance war battles
     */
    public function processAllianceWarBattles(): void
    {
        $activeWars = \App\Models\Game\AllianceWar::where('status', 'active')->get();

        foreach ($activeWars as $war) {
            $this->processWarBattles($war);
        }
    }

    /**
     * Process battles for a specific war
     */
    private function processWarBattles(\App\Models\Game\AllianceWar $war): void
    {
        // Get all movements between alliance members
        $attackerAlliance = $war->attackerAlliance;
        $defenderAlliance = $war->defenderAlliance;

        $attackerVillageIds = $attackerAlliance->members()
            ->with('villages')
            ->get()
            ->pluck('villages')
            ->flatten()
            ->pluck('id')
            ->toArray();

        $defenderVillageIds = $defenderAlliance->members()
            ->with('villages')
            ->get()
            ->pluck('villages')
            ->flatten()
            ->pluck('id')
            ->toArray();

        // Find attacks between alliance members
        $attacks = Movement::whereIn('from_village_id', $attackerVillageIds)
            ->whereIn('to_village_id', $defenderVillageIds)
            ->where('type', 'attack')
            ->where('arrives_at', '<=', now())
            ->get();

        foreach ($attacks as $attack) {
            $this->processAllianceAttack($attack, $war);
        }
    }

    /**
     * Process an alliance attack
     */
    private function processAllianceAttack(Movement $attack, \App\Models\Game\AllianceWar $war): void
    {
        $attackerVillage = $attack->fromVillage;
        $defenderVillage = $attack->toVillage;

        // Calculate battle result with alliance bonuses
        $battleResult = $this->calculateAllianceBattleResult($attack, $war);

        // Create battle record
        $battle = \App\Models\Game\Battle::create([
            'world_id' => $attackerVillage->world_id,
            'attacker_id' => $attackerVillage->player_id,
            'defender_id' => $defenderVillage->player_id,
            'from_village_id' => $attackerVillage->id,
            'to_village_id' => $defenderVillage->id,
            'type' => 'alliance_war',
            'result' => $battleResult['result'],
            'battle_data' => $battleResult,
            'occurred_at' => now(),
        ]);

        // Update war score
        $this->updateWarScore($war, $battleResult);

        // Create reports for both sides
        $this->createAllianceWarReports($battle, $war);

        // Publish alliance battle event
        $this->rabbitMQ->publishGameEvent('alliance_war', [
            'event_type' => 'battle_completed',
            'war_id' => $war->id,
            'battle_id' => $battle->id,
            'attacker_alliance_id' => $war->attacker_alliance_id,
            'defender_alliance_id' => $war->defender_alliance_id,
            'result' => $battleResult['result'],
            'war_score_change' => $battleResult['war_score_change'],
            'timestamp' => now()->toISOString(),
        ]);

        Log::info("Alliance war battle processed: {$battleResult['result']} in war {$war->id}");
    }

    /**
     * Calculate battle result with alliance bonuses
     */
    private function calculateAllianceBattleResult(Movement $attack, \App\Models\Game\AllianceWar $war): array
    {
        $attackerVillage = $attack->fromVillage;
        $defenderVillage = $attack->toVillage;

        // Get troop data
        $attackingTroops = $this->getMovementTroops($attack);
        $defendingTroops = $this->getVillageTroops($defenderVillage);

        // Calculate base battle result
        $baseResult = $this->battleService->simulateBattle(
            $attackingTroops,
            $defendingTroops,
            $defenderVillage,
            1
        );

        // Apply alliance bonuses
        $allianceBonuses = $this->calculateAllianceBonuses($war, $attackerVillage, $defenderVillage);
        
        // Modify battle power based on alliance bonuses
        $attackerPower = $baseResult['battle_power_stats']['attacker_avg'] * (1 + $allianceBonuses['attacker_bonus']);
        $defenderPower = $baseResult['battle_power_stats']['defender_avg'] * (1 + $allianceBonuses['defender_bonus']);

        // Determine result
        $result = 'draw';
        $warScoreChange = 0;

        if ($attackerPower > $defenderPower * 1.1) {
            $result = 'attacker_wins';
            $warScoreChange = 10; // Attacker gains 10 points
        } elseif ($defenderPower > $attackerPower * 1.1) {
            $result = 'defender_wins';
            $warScoreChange = -10; // Attacker loses 10 points
        }

        return [
            'result' => $result,
            'attacker_power' => $attackerPower,
            'defender_power' => $defenderPower,
            'alliance_bonuses' => $allianceBonuses,
            'war_score_change' => $warScoreChange,
            'attacker_losses' => $baseResult['attacker_avg_losses'] ?? [],
            'defender_losses' => $baseResult['defender_avg_losses'] ?? [],
            'resources_looted' => $baseResult['avg_resources_looted'] ?? [],
        ];
    }

    /**
     * Calculate alliance bonuses for battle
     */
    private function calculateAllianceBonuses(\App\Models\Game\AllianceWar $war, Village $attackerVillage, Village $defenderVillage): array
    {
        $attackerAlliance = $war->attackerAlliance;
        $defenderAlliance = $war->defenderAlliance;

        // Base alliance bonuses
        $attackerBonus = 0;
        $defenderBonus = 0;

        // Alliance size bonus (larger alliance gets penalty)
        $attackerSize = $attackerAlliance->members()->count();
        $defenderSize = $defenderAlliance->members()->count();

        if ($attackerSize > $defenderSize) {
            $attackerBonus -= 0.05; // 5% penalty for larger alliance
            $defenderBonus += 0.05; // 5% bonus for smaller alliance
        } elseif ($defenderSize > $attackerSize) {
            $attackerBonus += 0.05;
            $defenderBonus -= 0.05;
        }

        // Alliance activity bonus
        $attackerActivity = $this->calculateAllianceActivity($attackerAlliance);
        $defenderActivity = $this->calculateAllianceActivity($defenderAlliance);

        if ($attackerActivity > $defenderActivity) {
            $attackerBonus += 0.03; // 3% bonus for more active alliance
        } elseif ($defenderActivity > $attackerActivity) {
            $defenderBonus += 0.03;
        }

        // War experience bonus
        $attackerWarExperience = $this->calculateAllianceWarExperience($attackerAlliance);
        $defenderWarExperience = $this->calculateAllianceWarExperience($defenderAlliance);

        if ($attackerWarExperience > $defenderWarExperience) {
            $attackerBonus += 0.02; // 2% bonus for more experienced alliance
        } elseif ($defenderWarExperience > $attackerWarExperience) {
            $defenderBonus += 0.02;
        }

        return [
            'attacker_bonus' => max(-0.2, min(0.2, $attackerBonus)), // Cap at ±20%
            'defender_bonus' => max(-0.2, min(0.2, $defenderBonus)),
        ];
    }

    /**
     * Calculate alliance activity level
     */
    private function calculateAllianceActivity(Alliance $alliance): float
    {
        $members = $alliance->members()->with('villages')->get();
        $totalActivity = 0;
        $memberCount = 0;

        foreach ($members as $member) {
            $memberActivity = 0;
            
            // Check recent battles
            $recentBattles = \App\Models\Game\Battle::where('attacker_id', $member->id)
                ->orWhere('defender_id', $member->id)
                ->where('occurred_at', '>=', now()->subDays(7))
                ->count();
            
            $memberActivity += $recentBattles * 0.1;
            
            // Check recent movements
            $recentMovements = Movement::where('player_id', $member->id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
            
            $memberActivity += $recentMovements * 0.05;
            
            $totalActivity += $memberActivity;
            $memberCount++;
        }

        return $memberCount > 0 ? $totalActivity / $memberCount : 0;
    }

    /**
     * Calculate alliance war experience
     */
    private function calculateAllianceWarExperience(Alliance $alliance): int
    {
        return \App\Models\Game\AllianceWar::where('attacker_alliance_id', $alliance->id)
            ->orWhere('defender_alliance_id', $alliance->id)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Update war score
     */
    private function updateWarScore(\App\Models\Game\AllianceWar $war, array $battleResult): void
    {
        $war->war_score += $battleResult['war_score_change'];
        $war->save();

        // Check for war end conditions
        if (abs($war->war_score) >= 100) {
            $this->endWar($war);
        }
    }

    /**
     * End a war
     */
    private function endWar(\App\Models\Game\AllianceWar $war): void
    {
        $war->status = 'completed';
        $war->ended_at = now();
        $war->save();

        $winner = $war->war_score > 0 ? $war->attackerAlliance : $war->defenderAlliance;
        $loser = $war->war_score > 0 ? $war->defenderAlliance : $war->attackerAlliance;

        // Notify alliance members
        $this->notifyAllianceMembers($winner, 'war_won', [
            'war_id' => $war->id,
            'enemy_alliance' => $loser->name,
            'final_score' => abs($war->war_score),
        ]);

        $this->notifyAllianceMembers($loser, 'war_lost', [
            'war_id' => $war->id,
            'enemy_alliance' => $winner->name,
            'final_score' => abs($war->war_score),
        ]);

        // Publish war end event
        $this->rabbitMQ->publishGameEvent('alliance_war', [
            'event_type' => 'war_ended',
            'war_id' => $war->id,
            'winner_alliance_id' => $winner->id,
            'loser_alliance_id' => $loser->id,
            'final_score' => abs($war->war_score),
            'timestamp' => now()->toISOString(),
        ]);

        Log::info("War ended: {$winner->name} defeated {$loser->name} with score {$war->war_score}");
    }

    /**
     * Create alliance war reports
     */
    private function createAllianceWarReports(\App\Models\Game\Battle $battle, \App\Models\Game\AllianceWar $war): void
    {
        $attackerVillage = $battle->fromVillage;
        $defenderVillage = $battle->toVillage;

        // Attacker report
        Report::create([
            'world_id' => $battle->world_id,
            'attacker_id' => $battle->attacker_id,
            'defender_id' => $battle->defender_id,
            'from_village_id' => $battle->from_village_id,
            'to_village_id' => $battle->to_village_id,
            'type' => 'alliance_war',
            'status' => $battle->result,
            'title' => "Alliance War Battle - {$battle->result}",
            'content' => $this->generateAllianceWarReportContent($battle, $war, 'attacker'),
            'battle_data' => $battle->battle_data,
            'is_read' => false,
            'is_important' => true,
        ]);

        // Defender report
        Report::create([
            'world_id' => $battle->world_id,
            'attacker_id' => $battle->attacker_id,
            'defender_id' => $battle->defender_id,
            'from_village_id' => $battle->from_village_id,
            'to_village_id' => $battle->to_village_id,
            'type' => 'alliance_war',
            'status' => $battle->result,
            'title' => "Alliance War Battle - {$battle->result}",
            'content' => $this->generateAllianceWarReportContent($battle, $war, 'defender'),
            'battle_data' => $battle->battle_data,
            'is_read' => false,
            'is_important' => true,
        ]);
    }

    /**
     * Generate alliance war report content
     */
    private function generateAllianceWarReportContent(\App\Models\Game\Battle $battle, \App\Models\Game\AllianceWar $war, string $perspective): string
    {
        $attackerAlliance = $war->attackerAlliance;
        $defenderAlliance = $war->defenderAlliance;
        $battleData = $battle->battle_data;

        $content = "=== ALLIANCE WAR BATTLE ===\n";
        $content .= "War: {$attackerAlliance->name} vs {$defenderAlliance->name}\n";
        $content .= "Battle Result: {$battle->result}\n";
        $content .= "War Score Change: {$battleData['war_score_change']}\n";
        $content .= "Current War Score: {$war->war_score}\n\n";

        $content .= "=== BATTLE POWER ===\n";
        $content .= "Attacker Power: " . number_format($battleData['attacker_power'], 0) . "\n";
        $content .= "Defender Power: " . number_format($battleData['defender_power'], 0) . "\n\n";

        if (isset($battleData['alliance_bonuses'])) {
            $content .= "=== ALLIANCE BONUSES ===\n";
            $content .= "Attacker Bonus: " . number_format($battleData['alliance_bonuses']['attacker_bonus'] * 100, 1) . "%\n";
            $content .= "Defender Bonus: " . number_format($battleData['alliance_bonuses']['defender_bonus'] * 100, 1) . "%\n\n";
        }

        return $content;
    }

    /**
     * Get movement troops
     */
    private function getMovementTroops(Movement $movement): array
    {
        // This would need to be implemented based on your movement/troop system
        // For now, returning empty array as placeholder
        return [];
    }

    /**
     * Get village troops
     */
    private function getVillageTroops(Village $village): array
    {
        $troops = $village->troops()->with('unitType')->get();
        $villageTroops = [];

        foreach ($troops as $troop) {
            if ($troop->in_village > 0) {
                $villageTroops[] = [
                    'troop_id' => $troop->id,
                    'unit_type' => $troop->unitType->name,
                    'count' => $troop->in_village,
                    'attack' => $troop->unitType->attack_power,
                    'defense_infantry' => $troop->unitType->defense_power,
                    'defense_cavalry' => $troop->unitType->defense_power,
                    'speed' => $troop->unitType->speed,
                ];
            }
        }

        return $villageTroops;
    }

    /**
     * Notify alliance members
     */
    private function notifyAllianceMembers(Alliance $alliance, string $eventType, array $data): void
    {
        $members = $alliance->members()->get();

        foreach ($members as $member) {
            $this->rabbitMQ->publishInGameNotification(
                $member->id,
                "Alliance War: {$eventType}",
                $data
            );
        }
    }

    /**
     * Get alliance war statistics
     */
    public function getAllianceWarStatistics(Alliance $alliance): array
    {
        $wars = \App\Models\Game\AllianceWar::where('attacker_alliance_id', $alliance->id)
            ->orWhere('defender_alliance_id', $alliance->id)
            ->get();

        $totalWars = $wars->count();
        $warsWon = $wars->where('status', 'completed')
            ->filter(function ($war) use ($alliance) {
                return ($war->attacker_alliance_id === $alliance->id && $war->war_score > 0) ||
                       ($war->defender_alliance_id === $alliance->id && $war->war_score < 0);
            })
            ->count();

        $activeWars = $wars->where('status', 'active')->count();

        return [
            'total_wars' => $totalWars,
            'wars_won' => $warsWon,
            'wars_lost' => $totalWars - $warsWon,
            'win_rate' => $totalWars > 0 ? ($warsWon / $totalWars) * 100 : 0,
            'active_wars' => $activeWars,
            'war_experience' => $this->calculateAllianceWarExperience($alliance),
        ];
    }
}

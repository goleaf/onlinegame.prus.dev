<?php

namespace App\Services\Game;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\AllianceMember;
use App\Models\Game\AllianceDiplomacy;
use App\Models\Game\AllianceWar;
use App\Models\Game\Message;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class AllianceService
{
    /**
     * Create a new alliance
     */
    public function createAlliance(Player $founder, string $name, string $tag, string $description = null): array
    {
        // Validate alliance creation
        $validation = $this->validateAllianceCreation($founder, $name, $tag);
        if (!$validation['valid']) {
            return $validation;
        }

        DB::transaction(function () use ($founder, $name, $tag, $description) {
            // Create alliance
            $alliance = Alliance::create([
                'name' => $name,
                'tag' => $tag,
                'description' => $description,
                'world_id' => $founder->world_id,
                'leader_id' => $founder->id,
                'points' => $founder->points,
                'villages_count' => $founder->villages()->count(),
                'members_count' => 1,
                'is_active' => true,
            ]);

            // Add founder as member
            AllianceMember::create([
                'alliance_id' => $alliance->id,
                'player_id' => $founder->id,
                'role' => 'leader',
                'joined_at' => now(),
            ]);

            // Update player's alliance
            $founder->update(['alliance_id' => $alliance->id]);
        });

        // Clear cache
        $this->clearAllianceCache($founder->world_id);

        return [
            'success' => true,
            'message' => 'Alliance created successfully',
            'alliance' => $alliance,
        ];
    }

    /**
     * Invite player to alliance
     */
    public function invitePlayer(Alliance $alliance, Player $inviter, Player $invitee): array
    {
        // Validate invitation
        $validation = $this->validateInvitation($alliance, $inviter, $invitee);
        if (!$validation['valid']) {
            return $validation;
        }

        // Create invitation message
        Message::create([
            'sender_id' => $inviter->id,
            'recipient_id' => $invitee->id,
            'subject' => "Invitation to join {$alliance->name}",
            'body' => "You have been invited to join the alliance {$alliance->name} ({$alliance->tag}).",
            'message_type' => Message::TYPE_DIPLOMACY,
            'priority' => Message::PRIORITY_NORMAL,
        ]);

        return [
            'success' => true,
            'message' => 'Invitation sent successfully',
        ];
    }

    /**
     * Accept alliance invitation
     */
    public function acceptInvitation(Player $player, Alliance $alliance): array
    {
        // Validate acceptance
        $validation = $this->validateAcceptance($player, $alliance);
        if (!$validation['valid']) {
            return $validation;
        }

        DB::transaction(function () use ($player, $alliance) {
            // Add player to alliance
            AllianceMember::create([
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // Update player's alliance
            $player->update(['alliance_id' => $alliance->id]);

            // Update alliance statistics
            $alliance->increment('members_count');
            $alliance->increment('points', $player->points);
            $alliance->increment('villages_count', $player->villages()->count());
        });

        // Clear cache
        $this->clearAllianceCache($alliance->world_id);

        return [
            'success' => true,
            'message' => 'Successfully joined the alliance',
        ];
    }

    /**
     * Leave alliance
     */
    public function leaveAlliance(Player $player): array
    {
        if (!$player->alliance_id) {
            return [
                'success' => false,
                'message' => 'Player is not in an alliance',
            ];
        }

        $alliance = $player->alliance;

        // Check if player is the leader
        if ($alliance->leader_id === $player->id) {
            return [
                'success' => false,
                'message' => 'Leader cannot leave alliance. Transfer leadership first.',
            ];
        }

        DB::transaction(function () use ($player, $alliance) {
            // Remove player from alliance
            AllianceMember::where('alliance_id', $alliance->id)
                ->where('player_id', $player->id)
                ->delete();

            // Update player's alliance
            $player->update(['alliance_id' => null]);

            // Update alliance statistics
            $alliance->decrement('members_count');
            $alliance->decrement('points', $player->points);
            $alliance->decrement('villages_count', $player->villages()->count());
        });

        // Clear cache
        $this->clearAllianceCache($alliance->world_id);

        return [
            'success' => true,
            'message' => 'Successfully left the alliance',
        ];
    }

    /**
     * Kick player from alliance
     */
    public function kickPlayer(Alliance $alliance, Player $kicker, Player $target): array
    {
        // Validate kick
        $validation = $this->validateKick($alliance, $kicker, $target);
        if (!$validation['valid']) {
            return $validation;
        }

        DB::transaction(function () use ($alliance, $target) {
            // Remove player from alliance
            AllianceMember::where('alliance_id', $alliance->id)
                ->where('player_id', $target->id)
                ->delete();

            // Update player's alliance
            $target->update(['alliance_id' => null]);

            // Update alliance statistics
            $alliance->decrement('members_count');
            $alliance->decrement('points', $target->points);
            $alliance->decrement('villages_count', $target->villages()->count());
        });

        // Clear cache
        $this->clearAllianceCache($alliance->world_id);

        return [
            'success' => true,
            'message' => 'Player kicked from alliance',
        ];
    }

    /**
     * Transfer leadership
     */
    public function transferLeadership(Alliance $alliance, Player $currentLeader, Player $newLeader): array
    {
        // Validate transfer
        $validation = $this->validateLeadershipTransfer($alliance, $currentLeader, $newLeader);
        if (!$validation['valid']) {
            return $validation;
        }

        DB::transaction(function () use ($alliance, $currentLeader, $newLeader) {
            // Update alliance leader
            $alliance->update(['leader_id' => $newLeader->id]);

            // Update member roles
            AllianceMember::where('alliance_id', $alliance->id)
                ->where('player_id', $currentLeader->id)
                ->update(['role' => 'member']);

            AllianceMember::where('alliance_id', $alliance->id)
                ->where('player_id', $newLeader->id)
                ->update(['role' => 'leader']);
        });

        // Clear cache
        $this->clearAllianceCache($alliance->world_id);

        return [
            'success' => true,
            'message' => 'Leadership transferred successfully',
        ];
    }

    /**
     * Declare war on another alliance
     */
    public function declareWar(Alliance $attacker, Alliance $defender, string $reason = null): array
    {
        // Validate war declaration
        $validation = $this->validateWarDeclaration($attacker, $defender);
        if (!$validation['valid']) {
            return $validation;
        }

        $war = AllianceWar::create([
            'attacker_alliance_id' => $attacker->id,
            'defender_alliance_id' => $defender->id,
            'reason' => $reason,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Create diplomacy record
        AllianceDiplomacy::create([
            'alliance_id' => $attacker->id,
            'target_alliance_id' => $defender->id,
            'relationship_type' => 'war',
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Clear cache
        $this->clearAllianceCache($attacker->world_id);

        return [
            'success' => true,
            'message' => 'War declared successfully',
            'war' => $war,
        ];
    }

    /**
     * Get alliance statistics
     */
    public function getAllianceStatistics(Alliance $alliance): array
    {
        $cacheKey = "alliance_stats:{$alliance->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($alliance) {
            $members = $alliance->members()->with('player')->get();
            $villages = $alliance->players()->with('villages')->get()->pluck('villages')->flatten();

            return [
                'id' => $alliance->id,
                'name' => $alliance->name,
                'tag' => $alliance->tag,
                'description' => $alliance->description,
                'leader' => $alliance->leader->name,
                'members_count' => $alliance->members_count,
                'villages_count' => $alliance->villages_count,
                'total_points' => $alliance->points,
                'average_points' => $alliance->members_count > 0 ? $alliance->points / $alliance->members_count : 0,
                'active_members' => $members->where('player.last_active_at', '>=', now()->subDays(7))->count(),
                'total_population' => $villages->sum('population'),
                'average_population' => $villages->count() > 0 ? $villages->sum('population') / $villages->count() : 0,
                'created_at' => $alliance->created_at,
                'wars' => $alliance->allWars()->count(),
                'active_wars' => $alliance->allWars()->where('status', 'active')->count(),
            ];
        });
    }

    /**
     * Get alliance rankings
     */
    public function getAllianceRankings(int $worldId, int $limit = 50): array
    {
        $cacheKey = "alliance_rankings:{$worldId}:{$limit}";

        return SmartCache::remember($cacheKey, 300, function () use ($worldId, $limit) {
            return Alliance::where('world_id', $worldId)
                ->active()
                ->orderBy('points', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($alliance, $index) {
                    return [
                        'rank' => $index + 1,
                        'id' => $alliance->id,
                        'name' => $alliance->name,
                        'tag' => $alliance->tag,
                        'points' => $alliance->points,
                        'members_count' => $alliance->members_count,
                        'villages_count' => $alliance->villages_count,
                        'leader' => $alliance->leader->name,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Validate alliance creation
     */
    private function validateAllianceCreation(Player $founder, string $name, string $tag): array
    {
        if ($founder->alliance_id) {
            return [
                'valid' => false,
                'message' => 'Player is already in an alliance',
            ];
        }

        if (strlen($name) < 3 || strlen($name) > 50) {
            return [
                'valid' => false,
                'message' => 'Alliance name must be between 3 and 50 characters',
            ];
        }

        if (strlen($tag) < 2 || strlen($tag) > 10) {
            return [
                'valid' => false,
                'message' => 'Alliance tag must be between 2 and 10 characters',
            ];
        }

        if (Alliance::where('name', $name)->where('world_id', $founder->world_id)->exists()) {
            return [
                'valid' => false,
                'message' => 'Alliance name already exists',
            ];
        }

        if (Alliance::where('tag', $tag)->where('world_id', $founder->world_id)->exists()) {
            return [
                'valid' => false,
                'message' => 'Alliance tag already exists',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate invitation
     */
    private function validateInvitation(Alliance $alliance, Player $inviter, Player $invitee): array
    {
        if ($invitee->alliance_id) {
            return [
                'valid' => false,
                'message' => 'Player is already in an alliance',
            ];
        }

        if ($alliance->members_count >= 50) {
            return [
                'valid' => false,
                'message' => 'Alliance is full',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate acceptance
     */
    private function validateAcceptance(Player $player, Alliance $alliance): array
    {
        if ($player->alliance_id) {
            return [
                'valid' => false,
                'message' => 'Player is already in an alliance',
            ];
        }

        if ($alliance->members_count >= 50) {
            return [
                'valid' => false,
                'message' => 'Alliance is full',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate kick
     */
    private function validateKick(Alliance $alliance, Player $kicker, Player $target): array
    {
        if ($target->alliance_id !== $alliance->id) {
            return [
                'valid' => false,
                'message' => 'Player is not in this alliance',
            ];
        }

        if ($target->id === $alliance->leader_id) {
            return [
                'valid' => false,
                'message' => 'Cannot kick the leader',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate leadership transfer
     */
    private function validateLeadershipTransfer(Alliance $alliance, Player $currentLeader, Player $newLeader): array
    {
        if ($currentLeader->id !== $alliance->leader_id) {
            return [
                'valid' => false,
                'message' => 'Player is not the current leader',
            ];
        }

        if ($newLeader->alliance_id !== $alliance->id) {
            return [
                'valid' => false,
                'message' => 'New leader is not in this alliance',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate war declaration
     */
    private function validateWarDeclaration(Alliance $attacker, Alliance $defender): array
    {
        if ($attacker->id === $defender->id) {
            return [
                'valid' => false,
                'message' => 'Cannot declare war on yourself',
            ];
        }

        if ($attacker->world_id !== $defender->world_id) {
            return [
                'valid' => false,
                'message' => 'Alliances must be in the same world',
            ];
        }

        // Check if already at war
        $existingWar = AllianceWar::where('attacker_alliance_id', $attacker->id)
            ->where('defender_alliance_id', $defender->id)
            ->where('status', 'active')
            ->exists();

        if ($existingWar) {
            return [
                'valid' => false,
                'message' => 'Already at war with this alliance',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Clear alliance cache
     */
    private function clearAllianceCache(int $worldId): void
    {
        SmartCache::forget("alliance_rankings:{$worldId}:50");
        // Clear other alliance-related caches as needed
    }
}

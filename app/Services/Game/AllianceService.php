<?php

namespace App\Services\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceDiplomacy;
use App\Models\Game\AllianceMember;
use App\Models\Game\AllianceWar;
use App\Models\Game\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AllianceService
{
    /**
     * Create a new alliance
     */
    public function createAlliance(int $leaderId, string $name, string $description, array $settings = []): Alliance
    {
        return DB::transaction(function () use ($leaderId, $name, $description, $settings) {
            $alliance = Alliance::create([
                'name' => $name,
                'description' => $description,
                'tag' => $this->generateUniqueTag(),
                'leader_id' => $leaderId,
                'member_count' => 1,
                'max_members' => $settings['max_members'] ?? 100,
                'is_active' => true,
                'settings' => array_merge([
                    'allow_invites' => true,
                    'auto_accept' => false,
                    'war_declaration' => 'leader_only',
                    'diplomacy_level' => 'full',
                ], $settings),
            ]);

            // Add leader as member
            AllianceMember::create([
                'alliance_id' => $alliance->id,
                'player_id' => $leaderId,
                'rank' => 'leader',
                'joined_at' => now(),
                'reference_number' => $this->generateReferenceNumber(),
            ]);

            // Update player's alliance
            Player::where('id', $leaderId)->update(['alliance_id' => $alliance->id]);

            // Broadcast alliance creation
            $this->broadcastAllianceUpdate($alliance, 'created');

            return $alliance;
        });
    }

    /**
     * Invite a player to the alliance
     */
    public function invitePlayer(int $allianceId, int $inviterId, int $playerId, string $message = ''): bool
    {
        $alliance = Alliance::find($allianceId);
        $inviter = AllianceMember::where('alliance_id', $allianceId)
            ->where('player_id', $inviterId)
            ->first();

        if (! $alliance || ! $inviter || ! $this->canInvitePlayers($inviter)) {
            return false;
        }

        $player = Player::find($playerId);
        if (! $player || $player->alliance_id) {
            return false;
        }

        // Create invitation
        $invitation = AllianceMember::create([
            'alliance_id' => $allianceId,
            'player_id' => $playerId,
            'rank' => 'pending',
            'joined_at' => null,
            'invitation_message' => $message,
            'invited_by' => $inviterId,
            'invited_at' => now(),
            'reference_number' => $this->generateReferenceNumber(),
        ]);

        // Broadcast invitation
        $this->broadcastAllianceInvitation($alliance, $player, $invitation);

        return true;
    }

    /**
     * Accept an alliance invitation
     */
    public function acceptInvitation(int $playerId, int $allianceId): bool
    {
        $invitation = AllianceMember::where('alliance_id', $allianceId)
            ->where('player_id', $playerId)
            ->where('rank', 'pending')
            ->first();

        if (! $invitation) {
            return false;
        }

        return DB::transaction(function () use ($invitation, $playerId, $allianceId) {
            // Update invitation to accepted
            $invitation->update([
                'rank' => 'member',
                'joined_at' => now(),
            ]);

            // Update player's alliance
            Player::where('id', $playerId)->update(['alliance_id' => $allianceId]);

            // Update alliance member count
            Alliance::where('id', $allianceId)->increment('member_count');

            // Broadcast acceptance
            $alliance = Alliance::find($allianceId);
            $player = Player::find($playerId);
            $this->broadcastAllianceUpdate($alliance, 'member_joined', $player);

            return true;
        });
    }

    /**
     * Remove a player from the alliance
     */
    public function removePlayer(int $allianceId, int $removerId, int $playerId, string $reason = ''): bool
    {
        $alliance = Alliance::find($allianceId);
        $remover = AllianceMember::where('alliance_id', $allianceId)
            ->where('player_id', $removerId)
            ->first();

        $member = AllianceMember::where('alliance_id', $allianceId)
            ->where('player_id', $playerId)
            ->first();

        if (! $alliance || ! $member || ! $this->canRemovePlayers($remover, $member)) {
            return false;
        }

        return DB::transaction(function () use ($alliance, $member, $playerId, $reason) {
            // Remove from alliance
            $member->delete();

            // Update player's alliance
            Player::where('id', $playerId)->update(['alliance_id' => null]);

            // Update alliance member count
            Alliance::where('id', $alliance->id)->decrement('member_count');

            // Broadcast removal
            $player = Player::find($playerId);
            $this->broadcastAllianceUpdate($alliance, 'member_left', $player, $reason);

            return true;
        });
    }

    /**
     * Declare war on another alliance
     */
    public function declareWar(int $allianceId, int $targetAllianceId, string $reason = ''): AllianceWar
    {
        $alliance = Alliance::find($allianceId);
        $targetAlliance = Alliance::find($targetAllianceId);

        if (! $alliance || ! $targetAlliance || $allianceId === $targetAllianceId) {
            throw new \InvalidArgumentException('Invalid alliance war declaration');
        }

        return DB::transaction(function () use ($alliance, $targetAlliance, $reason) {
            $war = AllianceWar::create([
                'attacker_alliance_id' => $alliance->id,
                'defender_alliance_id' => $targetAlliance->id,
                'reason' => $reason,
                'status' => 'active',
                'declared_at' => now(),
                'attacker_score' => 0,
                'defender_score' => 0,
                'reference_number' => $this->generateReferenceNumber(),
            ]);

            // Update alliance settings
            $alliance->update(['at_war' => true]);
            $targetAlliance->update(['at_war' => true]);

            // Broadcast war declaration
            $this->broadcastWarDeclaration($war);

            return $war;
        });
    }

    /**
     * Get alliance statistics
     */
    public function getAllianceStats(int $allianceId): array
    {
        $alliance = Alliance::with(['members', 'wars'])->find($allianceId);

        if (! $alliance) {
            return [];
        }

        $members = $alliance->members;
        $totalPopulation = $members->sum('population');
        $totalVillages = $members->sum('village_count');
        $totalPoints = $members->sum('points');

        return [
            'alliance' => $alliance,
            'member_count' => $members->count(),
            'total_population' => $totalPopulation,
            'total_villages' => $totalVillages,
            'total_points' => $totalPoints,
            'average_points' => $members->count() > 0 ? $totalPoints / $members->count() : 0,
            'war_count' => $alliance->wars->where('status', 'active')->count(),
            'diplomatic_relations' => $this->getDiplomaticRelations($allianceId),
            'rank' => $this->getAllianceRank($allianceId),
        ];
    }

    /**
     * Get alliance rankings
     */
    public function getAllianceRankings(int $limit = 50): array
    {
        return Alliance::where('is_active', true)
            ->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($alliance, $index) {
                return [
                    'rank' => $index + 1,
                    'alliance' => $alliance,
                    'stats' => $this->getAllianceStats($alliance->id),
                ];
            })
            ->toArray();
    }

    /**
     * Check if alliance can invite players
     */
    private function canInvitePlayers($member): bool
    {
        return in_array($member->rank, ['leader', 'co_leader', 'elder']);
    }

    /**
     * Check if member can remove other players
     */
    private function canRemovePlayers($remover, $target): bool
    {
        $leaderRanks = ['leader', 'co_leader'];
        $elderRanks = ['leader', 'co_leader', 'elder'];

        // Leaders can remove anyone
        if (in_array($remover->rank, $leaderRanks)) {
            return true;
        }

        // Elders can only remove members (not other elders or leaders)
        if ($remover->rank === 'elder' && $target->rank === 'member') {
            return true;
        }

        return false;
    }

    /**
     * Get diplomatic relations
     */
    private function getDiplomaticRelations(int $allianceId): array
    {
        return AllianceDiplomacy::where('alliance_id', $allianceId)
            ->orWhere('target_alliance_id', $allianceId)
            ->with(['alliance', 'targetAlliance'])
            ->get()
            ->map(function ($relation) use ($allianceId) {
                $isInitiator = $relation->alliance_id === $allianceId;

                return [
                    'relation' => $relation,
                    'target_alliance' => $isInitiator ? $relation->targetAlliance : $relation->alliance,
                    'is_initiator' => $isInitiator,
                ];
            })
            ->toArray();
    }

    /**
     * Get alliance rank
     */
    private function getAllianceRank(int $allianceId): int
    {
        $alliance = Alliance::find($allianceId);
        if (! $alliance) {
            return 0;
        }

        $rank = Alliance::where('is_active', true)
            ->where('total_points', '>', $alliance->total_points)
            ->count() + 1;

        return $rank;
    }

    /**
     * Generate unique alliance tag
     */
    private function generateUniqueTag(): string
    {
        do {
            $tag = strtoupper(Str::random(3));
        } while (Alliance::where('tag', $tag)->exists());

        return $tag;
    }

    /**
     * Generate reference number
     */
    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'ALL-'.strtoupper(Str::random(8));
        } while (AllianceMember::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Broadcast alliance update
     */
    private function broadcastAllianceUpdate(Alliance $alliance, string $action, $data = null): void
    {
        try {
            $userIds = $alliance->members->pluck('user_id')->toArray();

            RealTimeGameService::broadcastUpdate($userIds, 'alliance_update', [
                'alliance_id' => $alliance->id,
                'action' => $action,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast alliance update', [
                'alliance_id' => $alliance->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast alliance invitation
     */
    private function broadcastAllianceInvitation(Alliance $alliance, Player $player, AllianceMember $invitation): void
    {
        try {
            RealTimeGameService::broadcastUpdate([$player->user_id], 'alliance_invitation', [
                'alliance' => $alliance,
                'invitation' => $invitation,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast alliance invitation', [
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast war declaration
     */
    private function broadcastWarDeclaration(AllianceWar $war): void
    {
        try {
            $attackerUserIds = $war->attackerAlliance->members->pluck('user_id')->toArray();
            $defenderUserIds = $war->defenderAlliance->members->pluck('user_id')->toArray();
            $allUserIds = array_merge($attackerUserIds, $defenderUserIds);

            RealTimeGameService::broadcastUpdate($allUserIds, 'war_declared', [
                'war' => $war,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast war declaration', [
                'war_id' => $war->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

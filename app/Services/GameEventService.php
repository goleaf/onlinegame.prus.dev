<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\Cache;
use LaraUtilX\Utilities\CachingUtil;

class GameEventService
{
    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(3600, []);
        $this->loggingUtil = new LoggingUtil();
        $this->notificationService = new NotificationService();
    }

    /**
     * Create game event
     */
    public function createEvent(
        Player $player,
        string $eventType,
        string $title,
        string $description,
        array $eventData = [],
        ?Village $village = null,
        ?Alliance $alliance = null
    ): GameEvent {
        $event = GameEvent::create([
            'player_id' => $player->id,
            'village_id' => $village?->id,
            'alliance_id' => $alliance?->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'event_data' => $eventData,
            'occurred_at' => now(),
            'is_read' => false,
        ]);

        $this->loggingUtil->info('Game event created', [
            'event_id' => $event->id,
            'player_id' => $player->id,
            'event_type' => $eventType,
            'title' => $title,
        ]);

        // Clear player events cache
        $this->clearPlayerEventsCache($player);

        return $event;
    }

    /**
     * Create battle event
     */
    public function createBattleEvent(
        Player $player,
        string $battleType,
        array $battleData,
        ?Village $village = null
    ): GameEvent {
        $title = match ($battleType) {
            'attack' => 'Battle: Attack',
            'defend' => 'Battle: Defense',
            'raid' => 'Battle: Raid',
            default => 'Battle Event'
        };

        $description = $this->generateBattleDescription($battleType, $battleData);

        return $this->createEvent(
            $player,
            'battle',
            $title,
            $description,
            $battleData,
            $village
        );
    }

    /**
     * Create movement event
     */
    public function createMovementEvent(
        Player $player,
        string $movementType,
        array $movementData,
        ?Village $village = null
    ): GameEvent {
        $title = match ($movementType) {
            'attack' => 'Movement: Attack',
            'support' => 'Movement: Support',
            'return' => 'Movement: Return',
            default => 'Movement Event'
        };

        $description = $this->generateMovementDescription($movementType, $movementData);

        return $this->createEvent(
            $player,
            'movement',
            $title,
            $description,
            $movementData,
            $village
        );
    }

    /**
     * Create building event
     */
    public function createBuildingEvent(
        Player $player,
        string $buildingType,
        array $buildingData,
        ?Village $village = null
    ): GameEvent {
        $title = match ($buildingType) {
            'completed' => 'Building: Completed',
            'started' => 'Building: Construction Started',
            'cancelled' => 'Building: Cancelled',
            'demolished' => 'Building: Demolished',
            default => 'Building Event'
        };

        $description = $this->generateBuildingDescription($buildingType, $buildingData);

        return $this->createEvent(
            $player,
            'building',
            $title,
            $description,
            $buildingData,
            $village
        );
    }

    /**
     * Create alliance event
     */
    public function createAllianceEvent(
        Player $player,
        string $allianceType,
        array $allianceData,
        ?Alliance $alliance = null
    ): GameEvent {
        $title = match ($allianceType) {
            'joined' => 'Alliance: Joined',
            'left' => 'Alliance: Left',
            'invited' => 'Alliance: Invited',
            'promoted' => 'Alliance: Promoted',
            'demoted' => 'Alliance: Demoted',
            'war_declared' => 'Alliance: War Declared',
            'peace_made' => 'Alliance: Peace Made',
            default => 'Alliance Event'
        };

        $description = $this->generateAllianceDescription($allianceType, $allianceData);

        return $this->createEvent(
            $player,
            'alliance',
            $title,
            $description,
            $allianceData,
            null,
            $alliance
        );
    }

    /**
     * Create resource event
     */
    public function createResourceEvent(
        Player $player,
        string $resourceType,
        array $resourceData,
        ?Village $village = null
    ): GameEvent {
        $title = match ($resourceType) {
            'overflow' => 'Resource: Storage Full',
            'depleted' => 'Resource: Depleted',
            'production' => 'Resource: Production',
            'trade' => 'Resource: Trade',
            default => 'Resource Event'
        };

        $description = $this->generateResourceDescription($resourceType, $resourceData);

        return $this->createEvent(
            $player,
            'resource',
            $title,
            $description,
            $resourceData,
            $village
        );
    }

    /**
     * Get player events
     */
    public function getPlayerEvents(Player $player, int $limit = 50, array $filters = []): array
    {
        $cacheKey = "player_events_{$player->id}_{$limit}_".md5(serialize($filters));

        return $this->cachingUtil->remember($cacheKey, 60, function () use ($player, $limit, $filters) {
            $query = GameEvent::where('player_id', $player->id);

            if (isset($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }

            if (isset($filters['village_id'])) {
                $query->where('village_id', $filters['village_id']);
            }

            if (isset($filters['alliance_id'])) {
                $query->where('alliance_id', $filters['alliance_id']);
            }

            if (isset($filters['date_from'])) {
                $query->where('occurred_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('occurred_at', '<=', $filters['date_to']);
            }

            return $query->orderBy('occurred_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'event_type' => $event->event_type,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_data' => $event->event_data,
                        'village_name' => $event->village?->name,
                        'alliance_name' => $event->alliance?->name,
                        'is_read' => $event->is_read,
                        'occurred_at' => $event->occurred_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Mark event as read
     */
    public function markAsRead(Player $player, int $eventId): bool
    {
        $event = GameEvent::where('player_id', $player->id)
            ->where('id', $eventId)
            ->first();

        if (! $event) {
            return false;
        }

        $event->update(['is_read' => true]);
        $this->clearPlayerEventsCache($player);

        return true;
    }

    /**
     * Mark all events as read
     */
    public function markAllAsRead(Player $player): int
    {
        $updated = GameEvent::where('player_id', $player->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->clearPlayerEventsCache($player);

        return $updated;
    }

    /**
     * Get unread event count
     */
    public function getUnreadCount(Player $player): int
    {
        $cacheKey = "player_unread_events_{$player->id}";

        return $this->cachingUtil->remember($cacheKey, 30, function () use ($player) {
            return GameEvent::where('player_id', $player->id)
                ->where('is_read', false)
                ->count();
        });
    }

    /**
     * Delete old events
     */
    public function cleanupOldEvents(int $daysOld = 90): int
    {
        $deleted = GameEvent::where('occurred_at', '<', now()->subDays($daysOld))
            ->delete();

        $this->loggingUtil->info('Cleaned up old game events', [
            'deleted_count' => $deleted,
            'days_old' => $daysOld,
        ]);

        return $deleted;
    }

    /**
     * Get event statistics
     */
    public function getEventStats(): array
    {
        $cacheKey = 'game_event_stats';

        return $this->cachingUtil->remember($cacheKey, 300, function () {
            return [
                'total_events' => GameEvent::count(),
                'unread_events' => GameEvent::where('is_read', false)->count(),
                'events_by_type' => GameEvent::groupBy('event_type')->selectRaw('event_type, count(*) as count')->pluck('count', 'event_type'),
                'today_events' => GameEvent::whereDate('occurred_at', today())->count(),
                'events_by_hour' => GameEvent::selectRaw('HOUR(occurred_at) as hour, count(*) as count')
                    ->whereDate('occurred_at', today())
                    ->groupBy('hour')
                    ->pluck('count', 'hour'),
            ];
        });
    }

    /**
     * Generate battle description
     */
    protected function generateBattleDescription(string $type, array $data): string
    {
        $village = $data['village_name'] ?? 'Unknown Village';
        $result = $data['result'] ?? 'unknown';
        $casualties = $data['casualties'] ?? 0;

        return match ($type) {
            'attack' => "Attacked {$village}. Result: {$result}. Casualties: {$casualties}",
            'defend' => "Defended {$village} from attack. Result: {$result}. Casualties: {$casualties}",
            'raid' => "Raided {$village}. Loot: ".($data['loot'] ?? 'None'),
            default => "Battle at {$village}"
        };
    }

    /**
     * Generate movement description
     */
    protected function generateMovementDescription(string $type, array $data): string
    {
        $destination = $data['destination'] ?? 'Unknown';
        $troops = $data['troop_count'] ?? 0;

        return match ($type) {
            'attack' => "Sent attack force ({$troops} troops) to {$destination}",
            'support' => "Sent support troops ({$troops} troops) to {$destination}",
            'return' => "Troops returned from {$destination}",
            default => "Movement to {$destination}"
        };
    }

    /**
     * Generate building description
     */
    protected function generateBuildingDescription(string $type, array $data): string
    {
        $building = $data['building_name'] ?? 'Building';
        $level = $data['level'] ?? 1;
        $village = $data['village_name'] ?? 'Unknown Village';

        return match ($type) {
            'completed' => "{$building} completed to level {$level} in {$village}",
            'started' => "Started construction of {$building} level {$level} in {$village}",
            'cancelled' => "Cancelled construction of {$building} in {$village}",
            'demolished' => "Demolished {$building} in {$village}",
            default => "{$building} update in {$village}"
        };
    }

    /**
     * Generate alliance description
     */
    protected function generateAllianceDescription(string $type, array $data): string
    {
        $player = $data['player_name'] ?? 'Player';
        $alliance = $data['alliance_name'] ?? 'Alliance';

        return match ($type) {
            'joined' => "Joined alliance {$alliance}",
            'left' => "Left alliance {$alliance}",
            'invited' => "Invited to join alliance {$alliance}",
            'promoted' => "Promoted to {$data['new_rank']} in {$alliance}",
            'demoted' => "Demoted to {$data['new_rank']} in {$alliance}",
            'war_declared' => "War declared against {$data['target_alliance']}",
            'peace_made' => "Peace treaty signed with {$data['target_alliance']}",
            default => "Alliance update for {$alliance}"
        };
    }

    /**
     * Generate resource description
     */
    protected function generateResourceDescription(string $type, array $data): string
    {
        $resource = $data['resource_type'] ?? 'Resource';
        $amount = $data['amount'] ?? 0;
        $village = $data['village_name'] ?? 'Unknown Village';

        return match ($type) {
            'overflow' => "{$resource} storage full in {$village}. Lost: {$amount}",
            'depleted' => "{$resource} depleted in {$village}",
            'production' => "Produced {$amount} {$resource} in {$village}",
            'trade' => "Traded {$amount} {$resource} in {$village}",
            default => "{$resource} update in {$village}"
        };
    }

    /**
     * Clear player events cache
     */
    protected function clearPlayerEventsCache(Player $player): void
    {
        $patterns = [
            "player_events_{$player->id}_*",
            "player_unread_events_{$player->id}",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }

    /**
     * Process world events
     */
    public function processWorldEvents(): void
    {
        // Process server-wide events like maintenance, special events, etc.
        $this->processServerMaintenance();
        $this->processSpecialEvents();
        $this->processTournamentEvents();
    }

    /**
     * Process server maintenance events
     */
    protected function processServerMaintenance(): void
    {
        // Check for upcoming maintenance windows
        // Create events for all players
    }

    /**
     * Process special events
     */
    protected function processSpecialEvents(): void
    {
        // Check for holiday events, special promotions, etc.
    }

    /**
     * Process tournament events
     */
    protected function processTournamentEvents(): void
    {
        // Check for tournament announcements, results, etc.
    }
}

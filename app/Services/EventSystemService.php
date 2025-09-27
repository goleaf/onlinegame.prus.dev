<?php

namespace App\Services;

use App\Models\Game\Event;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

class EventSystemService
{
    protected CachingUtil $cachingUtil;
    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(1200, ['event_system']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Create a new event
     */
    public function createEvent(array $eventData): Event
    {
        DB::beginTransaction();

        try {
            $event = Event::create([
                'title' => $eventData['title'],
                'description' => $eventData['description'] ?? '',
                'type' => $eventData['type'],
                'category' => $eventData['category'] ?? 'general',
                'severity' => $eventData['severity'] ?? 'info',
                'data' => $eventData['data'] ?? [],
                'affected_players' => $eventData['affected_players'] ?? [],
                'affected_alliances' => $eventData['affected_alliances'] ?? [],
                'affected_villages' => $eventData['affected_villages'] ?? [],
                'started_at' => $eventData['started_at'] ?? now(),
                'expires_at' => $eventData['expires_at'] ?? null,
                'is_active' => $eventData['is_active'] ?? true,
                'is_global' => $eventData['is_global'] ?? false,
            ]);

            // Generate reference number
            $event->generateReference();

            DB::commit();

            $this->loggingUtil->info('Event created', [
                'event_id' => $event->id,
                'type' => $event->type,
                'category' => $event->category,
                'severity' => $event->severity
            ]);

            // Clear cache
            $this->clearEventCache();

            return $event;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to create event', [
                'event_data' => $eventData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get active events
     */
    public function getActiveEvents(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = 'active_events_' . md5(serialize($filters));

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($filters) {
            $query = Event::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });

            // Apply filters
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['severity'])) {
                $query->where('severity', $filters['severity']);
            }

            if (isset($filters['is_global'])) {
                $query->where('is_global', $filters['is_global']);
            }

            if (isset($filters['player_id'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('is_global', true)
                        ->orWhereJsonContains('affected_players', $filters['player_id']);
                });
            }

            if (isset($filters['alliance_id'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('is_global', true)
                        ->orWhereJsonContains('affected_alliances', $filters['alliance_id']);
                });
            }

            return $query->orderBy('severity', 'desc')
                ->orderBy('started_at', 'desc')
                ->get();
        });
    }

    /**
     * Get events for a specific player
     */
    public function getPlayerEvents(Player $player, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "player_events_{$player->id}_" . md5(serialize($filters));

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player, $filters) {
            $query = Event::where('is_active', true)
                ->where(function ($q) use ($player) {
                    $q->where('is_global', true)
                        ->orWhereJsonContains('affected_players', $player->id)
                        ->orWhereJsonContains('affected_alliances', $player->alliance_id)
                        ->orWhereJsonContains('affected_villages', $player->villages()->pluck('id')->toArray());
                });

            // Apply additional filters
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['severity'])) {
                $query->where('severity', $filters['severity']);
            }

            return $query->orderBy('severity', 'desc')
                ->orderBy('started_at', 'desc')
                ->get();
        });
    }

    /**
     * Get events for a specific alliance
     */
    public function getAllianceEvents(Alliance $alliance, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "alliance_events_{$alliance->id}_" . md5(serialize($filters));

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($alliance, $filters) {
            $query = Event::where('is_active', true)
                ->where(function ($q) use ($alliance) {
                    $q->where('is_global', true)
                        ->orWhereJsonContains('affected_alliances', $alliance->id)
                        ->orWhereJsonContains('affected_players', $alliance->members()->pluck('id')->toArray());
                });

            // Apply additional filters
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['severity'])) {
                $query->where('severity', $filters['severity']);
            }

            return $query->orderBy('severity', 'desc')
                ->orderBy('started_at', 'desc')
                ->get();
        });
    }

    /**
     * Complete an event
     */
    public function completeEvent(Event $event, array $completionData = []): Event
    {
        DB::beginTransaction();

        try {
            $event->update([
                'is_active' => false,
                'completed_at' => now(),
                'completion_data' => $completionData,
            ]);

            // Execute event completion actions
            $this->executeEventCompletion($event, $completionData);

            DB::commit();

            $this->loggingUtil->info('Event completed', [
                'event_id' => $event->id,
                'type' => $event->type,
                'completion_data' => $completionData
            ]);

            // Clear cache
            $this->clearEventCache();

            return $event;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to complete event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel an event
     */
    public function cancelEvent(Event $event, string $reason = ''): Event
    {
        DB::beginTransaction();

        try {
            $event->update([
                'is_active' => false,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::commit();

            $this->loggingUtil->info('Event cancelled', [
                'event_id' => $event->id,
                'type' => $event->type,
                'reason' => $reason
            ]);

            // Clear cache
            $this->clearEventCache();

            return $event;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to cancel event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process expired events
     */
    public function processExpiredEvents(): int
    {
        $expiredEvents = Event::where('is_active', true)
            ->where('expires_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($expiredEvents as $event) {
            try {
                // Auto-complete expired events
                $this->completeEvent($event, ['auto_completed' => true, 'reason' => 'expired']);
                $processed++;
            } catch (\Exception $e) {
                $this->loggingUtil->error('Failed to process expired event', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($processed > 0) {
            $this->loggingUtil->info('Processed expired events', [
                'processed_count' => $processed
            ]);
        }

        return $processed;
    }

    /**
     * Get event statistics
     */
    public function getEventStatistics(): array
    {
        $cacheKey = 'event_statistics';

        return $this->cachingUtil->remember($cacheKey, 1800, function () {
            $stats = Event::selectRaw('
                COUNT(*) as total_events,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_events,
                SUM(CASE WHEN is_active = 0 AND completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_events,
                SUM(CASE WHEN is_active = 0 AND cancelled_at IS NOT NULL THEN 1 ELSE 0 END) as cancelled_events,
                COUNT(DISTINCT type) as unique_event_types,
                COUNT(DISTINCT category) as unique_categories,
                AVG(CASE WHEN completed_at IS NOT NULL THEN DATEDIFF(completed_at, started_at) ELSE NULL END) as avg_duration_days
            ')->first();

            $severityStats = Event::selectRaw('
                severity,
                COUNT(*) as count,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
            ')
                ->groupBy('severity')
                ->get();

            $typeStats = Event::selectRaw('
                type,
                COUNT(*) as count,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
                AVG(CASE WHEN completed_at IS NOT NULL THEN DATEDIFF(completed_at, started_at) ELSE NULL END) as avg_duration_days
            ')
                ->groupBy('type')
                ->get();

            return [
                'overview' => [
                    'total_events' => $stats->total_events ?? 0,
                    'active_events' => $stats->active_events ?? 0,
                    'completed_events' => $stats->completed_events ?? 0,
                    'cancelled_events' => $stats->cancelled_events ?? 0,
                    'unique_event_types' => $stats->unique_event_types ?? 0,
                    'unique_categories' => $stats->unique_categories ?? 0,
                    'avg_duration_days' => round($stats->avg_duration_days ?? 0, 2),
                ],
                'severity_breakdown' => $severityStats->toArray(),
                'type_breakdown' => $typeStats->toArray(),
            ];
        });
    }

    /**
     * Create system events
     */
    public function createSystemEvents(): array
    {
        $systemEvents = [];

        // Server maintenance event
        $systemEvents[] = $this->createEvent([
            'title' => 'Scheduled Server Maintenance',
            'description' => 'The server will undergo maintenance to improve performance and stability.',
            'type' => 'maintenance',
            'category' => 'system',
            'severity' => 'warning',
            'is_global' => true,
            'started_at' => now(),
            'expires_at' => now()->addHours(2),
            'data' => [
                'estimated_duration' => '2 hours',
                'affected_services' => ['game_server', 'database', 'cache']
            ]
        ]);

        // World event
        $systemEvents[] = $this->createEvent([
            'title' => 'World Event: Resource Surplus',
            'description' => 'All villages experience increased resource production for the next 24 hours.',
            'type' => 'world_event',
            'category' => 'economic',
            'severity' => 'info',
            'is_global' => true,
            'started_at' => now(),
            'expires_at' => now()->addHours(24),
            'data' => [
                'bonus_type' => 'resource_production',
                'bonus_amount' => 0.25,
                'duration_hours' => 24
            ]
        ]);

        $this->loggingUtil->info('System events created', [
            'events_created' => count($systemEvents)
        ]);

        return $systemEvents;
    }

    /**
     * Execute event completion actions
     */
    protected function executeEventCompletion(Event $event, array $completionData): void
    {
        switch ($event->type) {
            case 'world_event':
                $this->processWorldEventCompletion($event, $completionData);
                break;
            case 'maintenance':
                $this->processMaintenanceCompletion($event, $completionData);
                break;
            case 'alliance_war':
                $this->processAllianceWarCompletion($event, $completionData);
                break;
            case 'wonder_construction':
                $this->processWonderConstructionCompletion($event, $completionData);
                break;
            default:
                // Generic completion handling
                break;
        }
    }

    /**
     * Process world event completion
     */
    protected function processWorldEventCompletion(Event $event, array $completionData): void
    {
        $data = $event->data ?? [];
        
        if (isset($data['bonus_type']) && isset($data['bonus_amount'])) {
            // Remove world event bonuses
            $this->loggingUtil->info('World event bonus removed', [
                'event_id' => $event->id,
                'bonus_type' => $data['bonus_type'],
                'bonus_amount' => $data['bonus_amount']
            ]);
        }
    }

    /**
     * Process maintenance completion
     */
    protected function processMaintenanceCompletion(Event $event, array $completionData): void
    {
        $this->loggingUtil->info('Maintenance completed', [
            'event_id' => $event->id,
            'completion_data' => $completionData
        ]);
    }

    /**
     * Process alliance war completion
     */
    protected function processAllianceWarCompletion(Event $event, array $completionData): void
    {
        // Process alliance war results
        $this->loggingUtil->info('Alliance war completed', [
            'event_id' => $event->id,
            'completion_data' => $completionData
        ]);
    }

    /**
     * Process wonder construction completion
     */
    protected function processWonderConstructionCompletion(Event $event, array $completionData): void
    {
        // Process wonder construction results
        $this->loggingUtil->info('Wonder construction completed', [
            'event_id' => $event->id,
            'completion_data' => $completionData
        ]);
    }

    /**
     * Clear event cache
     */
    protected function clearEventCache(): void
    {
        $patterns = [
            'active_events_*',
            'player_events_*',
            'alliance_events_*',
            'event_statistics',
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }
}

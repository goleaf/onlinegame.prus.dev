<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\GameEvent;
use App\Models\Game\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

class NotificationService
{
    protected CachingUtil $cachingUtil;
    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil();
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Send notification to player
     */
    public function sendNotification(
        Player $player,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $priority = 'normal'
    ): Notification {
        $notification = Notification::create([
            'player_id' => $player->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'priority' => $priority,
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->loggingUtil->info('Notification sent', [
            'player_id' => $player->id,
            'type' => $type,
            'title' => $title,
        ]);

        // Clear player notifications cache
        $this->clearPlayerNotificationsCache($player);

        return $notification;
    }

    /**
     * Send battle notification
     */
    public function sendBattleNotification(
        Player $player,
        string $battleType,
        array $battleData
    ): Notification {
        $title = match ($battleType) {
            'attack' => 'Battle Report - Attack',
            'defend' => 'Battle Report - Defense',
            'raid' => 'Raid Report',
            default => 'Battle Report'
        };

        $message = $this->generateBattleMessage($battleType, $battleData);

        return $this->sendNotification(
            $player,
            'battle',
            $title,
            $message,
            $battleData,
            'high'
        );
    }

    /**
     * Send movement notification
     */
    public function sendMovementNotification(
        Player $player,
        string $movementType,
        array $movementData
    ): Notification {
        $title = match ($movementType) {
            'attack' => 'Attack Arrived',
            'support' => 'Support Arrived',
            'return' => 'Troops Returned',
            default => 'Movement Complete'
        };

        $message = $this->generateMovementMessage($movementType, $movementData);

        return $this->sendNotification(
            $player,
            'movement',
            $title,
            $message,
            $movementData,
            'normal'
        );
    }

    /**
     * Send building notification
     */
    public function sendBuildingNotification(
        Player $player,
        string $buildingType,
        array $buildingData
    ): Notification {
        $title = match ($buildingType) {
            'completed' => 'Building Completed',
            'cancelled' => 'Building Cancelled',
            'demolished' => 'Building Demolished',
            default => 'Building Update'
        };

        $message = $this->generateBuildingMessage($buildingType, $buildingData);

        return $this->sendNotification(
            $player,
            'building',
            $title,
            $message,
            $buildingData,
            'low'
        );
    }

    /**
     * Send alliance notification
     */
    public function sendAllianceNotification(
        Player $player,
        string $allianceType,
        array $allianceData
    ): Notification {
        $title = match ($allianceType) {
            'invitation' => 'Alliance Invitation',
            'accepted' => 'Member Joined',
            'rejected' => 'Invitation Rejected',
            'kicked' => 'Member Removed',
            'promoted' => 'Rank Promotion',
            'demoted' => 'Rank Demotion',
            default => 'Alliance Update'
        };

        $message = $this->generateAllianceMessage($allianceType, $allianceData);

        return $this->sendNotification(
            $player,
            'alliance',
            $title,
            $message,
            $allianceData,
            'normal'
        );
    }

    /**
     * Get player notifications
     */
    public function getPlayerNotifications(Player $player, int $limit = 50): array
    {
        $cacheKey = "player_notifications_{$player->id}_{$limit}";
        
        return $this->cachingUtil->remember($cacheKey, 60, function () use ($player, $limit) {
            return Notification::where('player_id', $player->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'data' => $notification->data,
                        'priority' => $notification->priority,
                        'is_read' => $notification->is_read,
                        'created_at' => $notification->created_at->toISOString(),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Player $player, int $notificationId): bool
    {
        $notification = Notification::where('player_id', $player->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->update(['is_read' => true]);
        $this->clearPlayerNotificationsCache($player);

        return true;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Player $player): int
    {
        $updated = Notification::where('player_id', $player->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->clearPlayerNotificationsCache($player);

        return $updated;
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(Player $player): int
    {
        $cacheKey = "player_unread_count_{$player->id}";
        
        return $this->cachingUtil->remember($cacheKey, 30, function () use ($player) {
            return Notification::where('player_id', $player->id)
                ->where('is_read', false)
                ->count();
        });
    }

    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $deleted = Notification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();

        $this->loggingUtil->info('Cleaned up old notifications', [
            'deleted_count' => $deleted,
            'days_old' => $daysOld
        ]);

        return $deleted;
    }

    /**
     * Generate battle message
     */
    protected function generateBattleMessage(string $type, array $data): string
    {
        $village = $data['village_name'] ?? 'Unknown Village';
        $result = $data['result'] ?? 'unknown';

        return match ($type) {
            'attack' => "Your attack on {$village} resulted in: {$result}",
            'defend' => "Your village {$village} was attacked. Result: {$result}",
            'raid' => "Raid on {$village} completed. Loot: " . ($data['loot'] ?? 'None'),
            default => "Battle at {$village} completed"
        };
    }

    /**
     * Generate movement message
     */
    protected function generateMovementMessage(string $type, array $data): string
    {
        $destination = $data['destination'] ?? 'Unknown';
        $troops = $data['troop_count'] ?? 0;

        return match ($type) {
            'attack' => "Your attack force ({$troops} troops) has arrived at {$destination}",
            'support' => "Support troops ({$troops} troops) have arrived at {$destination}",
            'return' => "Your troops have returned to {$destination}",
            default => "Movement to {$destination} completed"
        };
    }

    /**
     * Generate building message
     */
    protected function generateBuildingMessage(string $type, array $data): string
    {
        $building = $data['building_name'] ?? 'Building';
        $level = $data['level'] ?? 1;

        return match ($type) {
            'completed' => "{$building} has been completed to level {$level}",
            'cancelled' => "Construction of {$building} has been cancelled",
            'demolished' => "{$building} has been demolished",
            default => "{$building} update completed"
        };
    }

    /**
     * Generate alliance message
     */
    protected function generateAllianceMessage(string $type, array $data): string
    {
        $player = $data['player_name'] ?? 'Player';
        $alliance = $data['alliance_name'] ?? 'Alliance';

        return match ($type) {
            'invitation' => "You have been invited to join {$alliance}",
            'accepted' => "{$player} has joined {$alliance}",
            'rejected' => "{$player} has rejected the invitation to {$alliance}",
            'kicked' => "{$player} has been removed from {$alliance}",
            'promoted' => "{$player} has been promoted in {$alliance}",
            'demoted' => "{$player} has been demoted in {$alliance}",
            default => "Alliance update for {$alliance}"
        };
    }

    /**
     * Clear player notifications cache
     */
    protected function clearPlayerNotificationsCache(Player $player): void
    {
        $patterns = [
            "player_notifications_{$player->id}_*",
            "player_unread_count_{$player->id}",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }

    /**
     * Send email notification (if enabled)
     */
    public function sendEmailNotification(
        Player $player,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): bool {
        if (!$player->email_notifications_enabled) {
            return false;
        }

        try {
            Mail::send('emails.game-notification', [
                'player' => $player,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ], function ($mail) use ($player, $title) {
                $mail->to($player->user->email)
                    ->subject("[Game] {$title}");
            });

            return true;
        } catch (\Exception $e) {
            $this->loggingUtil->error('Failed to send email notification', [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        $cacheKey = 'notification_stats';
        
        return $this->cachingUtil->remember($cacheKey, 300, function () {
            return [
                'total_notifications' => Notification::count(),
                'unread_notifications' => Notification::where('is_read', false)->count(),
                'notifications_by_type' => Notification::groupBy('type')->selectRaw('type, count(*) as count')->pluck('count', 'type'),
                'notifications_by_priority' => Notification::groupBy('priority')->selectRaw('priority, count(*) as count')->pluck('count', 'priority'),
                'today_notifications' => Notification::whereDate('created_at', today())->count(),
            ];
        });
    }
}

<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Notification;
use App\Models\Game\Player;
use Illuminate\Support\Facades\Log;

class GameNotificationService
{
    /**
     * Send notification to a specific user
     */
    public function sendUserNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): bool {
        try {
            // Create notification record
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
                'is_read' => false,
            ]);

            // Send real-time update
            RealTimeGameService::sendUpdate($userId, 'notification_received', [
                'notification_id' => $notification->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]);

            Log::info('User notification sent', [
                'user_id' => $userId,
                'notification_id' => $notification->id,
                'type' => $type,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send user notification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBroadcastNotification(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        $sent = 0;

        foreach ($userIds as $userId) {
            if ($this->sendUserNotification($userId, $title, $message, $type, $data)) {
                $sent++;
            }
        }

        Log::info('Broadcast notification sent', [
            'total_users' => count($userIds),
            'sent' => $sent,
            'type' => $type,
        ]);

        return $sent;
    }

    /**
     * Send notification to all alliance members
     */
    public function sendAllianceNotification(
        int $allianceId,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        try {
            $alliance = Alliance::with('members')->findOrFail($allianceId);
            $userIds = $alliance->members->pluck('user_id')->toArray();

            $sent = $this->sendBroadcastNotification($userIds, $title, $message, $type, $data);

            Log::info('Alliance notification sent', [
                'alliance_id' => $allianceId,
                'members_count' => count($userIds),
                'sent' => $sent,
            ]);

            return $sent;

        } catch (\Exception $e) {
            Log::error('Failed to send alliance notification', [
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Send system notification to all users
     */
    public function sendSystemNotification(
        string $title,
        string $message,
        string $priority = 'normal'
    ): int {
        try {
            // Get all active users
            $userIds = Player::whereHas('user', function ($query): void {
                $query->where('last_activity', '>=', now()->subDays(7));
            })->pluck('user_id')->toArray();

            $sent = $this->sendBroadcastNotification(
                $userIds,
                $title,
                $message,
                'system',
                ['priority' => $priority]
            );

            Log::info('System notification sent', [
                'total_users' => count($userIds),
                'sent' => $sent,
                'priority' => $priority,
            ]);

            return $sent;

        } catch (\Exception $e) {
            Log::error('Failed to send system notification', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Send battle notification
     */
    public function sendBattleNotification(
        int $attackerId,
        int $defenderId,
        string $battleResult,
        array $battleData = []
    ): void {
        try {
            $attacker = Player::find($attackerId);
            $defender = Player::find($defenderId);

            if ($attacker) {
                $this->sendUserNotification(
                    $attacker->user_id,
                    'Battle Result',
                    "Your attack on {$defender->name} resulted in: {$battleResult}",
                    'battle',
                    $battleData
                );
            }

            if ($defender) {
                $this->sendUserNotification(
                    $defender->user_id,
                    'Battle Defense',
                    "You were attacked by {$attacker->name}. Result: {$battleResult}",
                    'battle',
                    $battleData
                );
            }

        } catch (\Exception $e) {
            Log::error('Failed to send battle notification', [
                'attacker_id' => $attackerId,
                'defender_id' => $defenderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send movement notification
     */
    public function sendMovementNotification(
        int $playerId,
        string $movementType,
        string $destination,
        string $status,
        array $movementData = []
    ): void {
        try {
            $player = Player::find($playerId);
            if (! $player) {
                return;
            }

            $this->sendUserNotification(
                $player->user_id,
                'Movement Update',
                "Your {$movementType} to {$destination} has {$status}",
                'movement',
                $movementData
            );

        } catch (\Exception $e) {
            Log::error('Failed to send movement notification', [
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send resource notification
     */
    public function sendResourceNotification(
        int $villageId,
        string $resourceType,
        int $amount,
        string $action = 'produced'
    ): void {
        try {
            $village = \App\Models\Game\Village::with('player')->find($villageId);
            if (! $village) {
                return;
            }

            $this->sendUserNotification(
                $village->player->user_id,
                'Resource Update',
                "Your village {$village->name} has {$action} {$amount} {$resourceType}",
                'resource',
                [
                    'village_id' => $villageId,
                    'resource_type' => $resourceType,
                    'amount' => $amount,
                    'action' => $action,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to send resource notification', [
                'village_id' => $villageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(int $userId, int $limit = 50): array
    {
        try {
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return $notifications->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get user notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(int $notificationId, int $userId): bool
    {
        try {
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();

            if ($notification) {
                $notification->update(['is_read' => true]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear user notifications
     */
    public function clearUserNotifications(int $userId): int
    {
        try {
            $deleted = Notification::where('user_id', $userId)->delete();

            Log::info('User notifications cleared', [
                'user_id' => $userId,
                'deleted_count' => $deleted,
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to clear user notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        try {
            $stats = [
                'total_notifications' => Notification::count(),
                'unread_notifications' => Notification::where('is_read', false)->count(),
                'notifications_today' => Notification::where('created_at', '>=', now()->subDay())->count(),
                'notifications_by_type' => Notification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get notification statistics', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Cleanup old notifications
     */
    public function cleanup(): int
    {
        try {
            // Delete notifications older than 30 days
            $deleted = Notification::where('created_at', '<', now()->subDays(30))->delete();

            Log::info('Notification cleanup completed', [
                'deleted_count' => $deleted,
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup notifications', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}

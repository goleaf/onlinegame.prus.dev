<?php

namespace App\Services;

use App\Models\User;
use App\Models\Game\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GameNotificationService
{
    /**
     * Send notification to specific users
     */
    public static function sendNotification(array $userIds, string $type, array $data, string $priority = 'normal'): void
    {
        try {
            foreach ($userIds as $userId) {
                $notification = [
                    'id' => uniqid(),
                    'type' => $type,
                    'data' => $data,
                    'priority' => $priority,
                    'timestamp' => now()->toISOString(),
                    'read' => false,
                ];

                // Store notification in cache
                $key = "user_notifications_{$userId}";
                $notifications = Cache::get($key, []);
                $notifications[] = $notification;
                
                // Keep only last 50 notifications
                if (count($notifications) > 50) {
                    $notifications = array_slice($notifications, -50);
                }
                
                Cache::put($key, $notifications, now()->addDays(7));

                Log::info('Game notification sent', [
                    'user_id' => $userId,
                    'type' => $type,
                    'priority' => $priority,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send game notification', [
                'user_ids' => $userIds,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast notification to multiple users
     */
    public static function broadcastNotification(array $userIds, string $type, array $data, string $priority = 'normal'): void
    {
        self::sendNotification($userIds, $type, $data, $priority);
    }

    /**
     * Send system-wide announcement
     */
    public static function sendSystemAnnouncement(string $title, string $message, string $priority = 'normal'): void
    {
        try {
            // Get all active users
            $activeUsers = User::where('last_activity_at', '>=', now()->subHours(1))
                ->pluck('id')
                ->toArray();

            self::sendNotification($activeUsers, 'system_announcement', [
                'title' => $title,
                'message' => $message,
                'system_announcement' => true,
            ], $priority);

            Log::info('System announcement sent', [
                'title' => $title,
                'priority' => $priority,
                'recipients' => count($activeUsers),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send system announcement', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user notifications
     */
    public static function getUserNotifications(int $userId): array
    {
        try {
            $key = "user_notifications_{$userId}";
            return Cache::get($key, []);

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
    public static function markNotificationAsRead(int $userId, string $notificationId): void
    {
        try {
            $key = "user_notifications_{$userId}";
            $notifications = Cache::get($key, []);

            foreach ($notifications as &$notification) {
                if ($notification['id'] === $notificationId) {
                    $notification['read'] = true;
                    break;
                }
            }

            Cache::put($key, $notifications, now()->addDays(7));

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup old notifications
     */
    public static function cleanupOldNotifications(): void
    {
        try {
            // Get all notification cache keys
            $keys = Cache::getStore()->getRedis()->keys('*user_notifications_*');
            
            foreach ($keys as $key) {
                $notifications = Cache::get($key, []);
                $cleanedNotifications = [];
                
                foreach ($notifications as $notification) {
                    // Keep notifications from last 7 days
                    $notificationTime = \Carbon\Carbon::parse($notification['timestamp']);
                    if ($notificationTime->isAfter(now()->subDays(7))) {
                        $cleanedNotifications[] = $notification;
                    }
                }
                
                if (count($cleanedNotifications) !== count($notifications)) {
                    Cache::put($key, $cleanedNotifications, now()->addDays(7));
                }
            }

            Log::info('Old notifications cleanup completed');

        } catch (\Exception $e) {
            Log::error('Failed to cleanup old notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send battle notification
     */
    public static function sendBattleNotification(int $attackerId, int $defenderId, array $battleData): void
    {
        self::sendNotification([$attackerId, $defenderId], 'battle_report', [
            'battle_id' => $battleData['id'],
            'attacker_id' => $attackerId,
            'defender_id' => $defenderId,
            'result' => $battleData['result'],
            'casualties' => $battleData['casualties'] ?? [],
            'loot' => $battleData['loot'] ?? [],
        ], 'high');
    }

    /**
     * Send movement notification
     */
    public static function sendMovementNotification(int $playerId, array $movementData): void
    {
        self::sendNotification([$playerId], 'movement_update', [
            'movement_id' => $movementData['id'],
            'type' => $movementData['type'],
            'status' => $movementData['status'],
            'arrival_time' => $movementData['arrival_time'] ?? null,
        ], 'normal');
    }

    /**
     * Send alliance notification
     */
    public static function sendAllianceNotification(int $allianceId, string $type, array $data): void
    {
        try {
            // Get all alliance members
            $memberIds = Player::where('alliance_id', $allianceId)
                ->pluck('user_id')
                ->toArray();

            if (!empty($memberIds)) {
                self::sendNotification($memberIds, "alliance_{$type}", $data, 'normal');
            }

        } catch (\Exception $e) {
            Log::error('Failed to send alliance notification', [
                'alliance_id' => $allianceId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send quest notification
     */
    public static function sendQuestNotification(int $playerId, string $type, array $data): void
    {
        self::sendNotification([$playerId], "quest_{$type}", $data, 'normal');
    }

    /**
     * Send resource notification
     */
    public static function sendResourceNotification(int $playerId, int $villageId, string $type, array $data): void
    {
        self::sendNotification([$playerId], "resource_{$type}", array_merge($data, [
            'village_id' => $villageId,
        ]), 'normal');
    }
}
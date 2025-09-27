<?php

namespace App\Services;

use App\Models\Game\GameNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Game Notification Service
 * Handles game notifications and messaging
 */
class GameNotificationService
{
    /**
     * Send notification to user
     */
    public static function sendNotification(
        int $userId,
        string $type,
        array $data = [],
        string $priority = 'normal'
    ): bool {
        try {
            $notification = GameNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'data' => $data,
                'priority' => $priority,
                'is_read' => false,
                'created_at' => now(),
            ]);

            // Invalidate user notification cache
            self::invalidateUserNotificationCache($userId);

            // Log notification
            Log::info('Game notification sent', [
                'user_id' => $userId,
                'type' => $type,
                'priority' => $priority,
                'notification_id' => $notification->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send game notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user notifications
     */
    public static function getUserNotifications(int $userId, int $limit = 50): array
    {
        $cacheKey = "user_notifications:{$userId}:{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $limit) {
            return GameNotification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $notification = GameNotification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->first();

            if ($notification) {
                $notification->update(['is_read' => true]);
                
                // Invalidate cache
                self::invalidateUserNotificationCache($userId);
                
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
     * Mark all notifications as read for user
     */
    public static function markAllAsRead(int $userId): bool
    {
        try {
            GameNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Invalidate cache
            self::invalidateUserNotificationCache($userId);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get unread notification count
     */
    public static function getUnreadCount(int $userId): int
    {
        $cacheKey = "user_unread_count:{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($userId) {
            return GameNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();
        });
    }

    /**
     * Get notification statistics
     */
    public static function getNotificationStats(): array
    {
        $cacheKey = 'notification_stats';

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return [
                'total_notifications' => GameNotification::count(),
                'unread_notifications' => GameNotification::where('is_read', false)->count(),
                'notifications_today' => GameNotification::whereDate('created_at', today())->count(),
                'notifications_this_week' => GameNotification::where('created_at', '>=', now()->subWeek())->count(),
                'by_type' => GameNotification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_priority' => GameNotification::selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority')
                    ->toArray(),
            ];
        });
    }

    /**
     * Send system-wide notification
     */
    public static function sendSystemNotification(
        string $type,
        array $data = [],
        string $priority = 'normal'
    ): bool {
        try {
            // Get all active users
            $activeUsers = \App\Models\User::where('last_activity_at', '>=', now()->subDays(7))
                ->pluck('id');

            $sentCount = 0;
            foreach ($activeUsers as $userId) {
                if (self::sendNotification($userId, $type, $data, $priority)) {
                    $sentCount++;
                }
            }

            Log::info('System notification sent', [
                'type' => $type,
                'sent_count' => $sentCount,
                'total_users' => $activeUsers->count(),
            ]);

            return $sentCount > 0;
        } catch (\Exception $e) {
            Log::error('Failed to send system notification', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up old notifications
     */
    public static function cleanupOldNotifications(int $daysOld = 30): int
    {
        try {
            $deletedCount = GameNotification::where('created_at', '<', now()->subDays($daysOld))
                ->delete();

            Log::info('Old notifications cleaned up', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old notifications', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Invalidate user notification cache
     */
    private static function invalidateUserNotificationCache(int $userId): void
    {
        $patterns = [
            "user_notifications:{$userId}:*",
            "user_unread_count:{$userId}",
        ];

        foreach ($patterns as $pattern) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = \Illuminate\Support\Facades\Redis::keys($pattern);
                if (!empty($keys)) {
                    \Illuminate\Support\Facades\Redis::del($keys);
                }
            } else {
                // For non-Redis stores, we'll clear specific keys
                Cache::forget("user_notifications:{$userId}:50");
                Cache::forget("user_unread_count:{$userId}");
            }
        }
    }

    /**
     * Get notification types
     */
    public static function getNotificationTypes(): array
    {
        return [
            'system_message' => 'System Message',
            'battle_report' => 'Battle Report',
            'alliance_invite' => 'Alliance Invite',
            'village_attack' => 'Village Attack',
            'resource_production' => 'Resource Production',
            'building_complete' => 'Building Complete',
            'troop_training' => 'Troop Training',
            'quest_complete' => 'Quest Complete',
            'achievement_unlock' => 'Achievement Unlock',
            'market_trade' => 'Market Trade',
        ];
    }

    /**
     * Get notification priorities
     */
    public static function getNotificationPriorities(): array
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }
}
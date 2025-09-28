<?php

namespace App\Services;

use App\Models\Game\Notification;
use App\Models\Game\Player;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LaraUtilX\Utilities\CachingUtil;

class NotificationService
{
    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(1800, ['notifications']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Send notification to a player
     */
    public function sendNotification(
        Player $player,
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'normal'
    ): Notification {
        DB::beginTransaction();

        try {
            $notification = Notification::create([
                'player_id' => $player->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'priority' => $priority,
                'read_at' => null,
                'sent_at' => now(),
            ]);

            // Generate reference number
            $notification->generateReference();

            DB::commit();

            $this->loggingUtil->info('Notification sent', [
                'notification_id' => $notification->id,
                'player_id' => $player->id,
                'type' => $type,
                'priority' => $priority,
            ]);

            // Clear cache
            $this->clearPlayerNotificationCache($player);

            return $notification;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->loggingUtil->error('Failed to send notification', [
                'player_id' => $player->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send email notification
     */
    public function sendEmailNotification(
        Player $player,
        string $type,
        string $subject,
        string $template,
        array $data = []
    ): bool {
        try {
            $user = $player->user;
            if (! $user || ! $user->email) {
                return false;
            }

            Mail::send($template, array_merge($data, [
                'player' => $player,
                'user' => $user,
                'notification_type' => $type,
            ]), function ($message) use ($user, $subject): void {
                $message->to($user->email, $user->name)
                    ->subject($subject);
            });

            $this->loggingUtil->info('Email notification sent', [
                'player_id' => $player->id,
                'user_email' => $user->email,
                'type' => $type,
                'subject' => $subject,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->loggingUtil->error('Failed to send email notification', [
                'player_id' => $player->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'read_at' => now(),
        ]);

        $this->loggingUtil->debug('Notification marked as read', [
            'notification_id' => $notification->id,
            'player_id' => $notification->player_id,
        ]);

        // Clear cache
        $this->clearPlayerNotificationCache($notification->player);

        return $notification;
    }

    /**
     * Mark all notifications as read for a player
     */
    public function markAllAsRead(Player $player): int
    {
        $updated = Notification::where('player_id', $player->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loggingUtil->info('All notifications marked as read', [
            'player_id' => $player->id,
            'updated_count' => $updated,
        ]);

        // Clear cache
        $this->clearPlayerNotificationCache($player);

        return $updated;
    }

    /**
     * Get unread notifications for a player
     */
    public function getUnreadNotifications(Player $player, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "unread_notifications_{$player->id}_{$limit}";

        return $this->cachingUtil->remember($cacheKey, 300, function () use ($player, $limit) {
            return Notification::where('player_id', $player->id)
                ->whereNull('read_at')
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get all notifications for a player
     */
    public function getPlayerNotifications(Player $player, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "player_notifications_{$player->id}_{$limit}";

        return $this->cachingUtil->remember($cacheKey, 600, function () use ($player, $limit) {
            return Notification::where('player_id', $player->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(): array
    {
        $cacheKey = 'notification_statistics';

        return $this->cachingUtil->remember($cacheKey, 1800, function () {
            $stats = Notification::selectRaw('
                COUNT(*) as total_notifications,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_notifications,
                SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read_notifications,
                COUNT(DISTINCT player_id) as players_with_notifications,
                COUNT(DISTINCT type) as unique_notification_types,
                AVG(CASE WHEN read_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, read_at) ELSE NULL END) as avg_read_time_minutes
            ')->first();

            $typeStats = Notification::selectRaw('
                type,
                COUNT(*) as count,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_count,
                AVG(CASE WHEN read_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, read_at) ELSE NULL END) as avg_read_time_minutes
            ')
                ->groupBy('type')
                ->get();

            $priorityStats = Notification::selectRaw('
                priority,
                COUNT(*) as count,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_count
            ')
                ->groupBy('priority')
                ->get();

            return [
                'overview' => [
                    'total_notifications' => $stats->total_notifications ?? 0,
                    'unread_notifications' => $stats->unread_notifications ?? 0,
                    'read_notifications' => $stats->read_notifications ?? 0,
                    'players_with_notifications' => $stats->players_with_notifications ?? 0,
                    'unique_notification_types' => $stats->unique_notification_types ?? 0,
                    'avg_read_time_minutes' => round($stats->avg_read_time_minutes ?? 0, 2),
                ],
                'type_breakdown' => $typeStats->toArray(),
                'priority_breakdown' => $priorityStats->toArray(),
            ];
        });
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deleted = Notification::where('created_at', '<', $cutoffDate)
            ->delete();

        $this->loggingUtil->info('Old notifications cleaned up', [
            'deleted_count' => $deleted,
            'days_kept' => $daysToKeep,
        ]);

        // Clear cache
        $this->clearNotificationCache();

        return $deleted;
    }

    /**
     * Send system-wide notification
     */
    public function sendSystemNotification(
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $priority = 'normal'
    ): int {
        $players = Player::where('is_active', true)->get();
        $sent = 0;

        foreach ($players as $player) {
            try {
                $this->sendNotification($player, $type, $title, $message, $data, $priority);
                $sent++;
            } catch (\Exception $e) {
                $this->loggingUtil->error('Failed to send system notification to player', [
                    'player_id' => $player->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->loggingUtil->info('System notification sent', [
            'type' => $type,
            'total_players' => $players->count(),
            'sent_count' => $sent,
        ]);

        return $sent;
    }

    /**
     * Get notification types
     */
    public function getNotificationTypes(): array
    {
        return [
            'battle' => 'Battle notifications',
            'alliance' => 'Alliance notifications',
            'village' => 'Village notifications',
            'quest' => 'Quest notifications',
            'achievement' => 'Achievement notifications',
            'system' => 'System notifications',
            'maintenance' => 'Maintenance notifications',
            'trade' => 'Trade notifications',
            'wonder' => 'Wonder notifications',
            'event' => 'Event notifications',
        ];
    }

    /**
     * Get notification priorities
     */
    public function getNotificationPriorities(): array
    {
        return [
            'low' => 'Low priority',
            'normal' => 'Normal priority',
            'high' => 'High priority',
            'urgent' => 'Urgent priority',
        ];
    }

    /**
     * Create notification templates
     */
    public function createNotificationTemplates(): array
    {
        return [
            'battle_attack' => [
                'title' => 'Village Under Attack!',
                'message' => 'Your village {village_name} is under attack by {attacker_name}!',
                'priority' => 'high',
                'data_template' => [
                    'village_name' => '',
                    'attacker_name' => '',
                    'attack_time' => '',
                ],
            ],
            'alliance_war' => [
                'title' => 'Alliance War Started',
                'message' => 'Your alliance {alliance_name} has declared war on {enemy_alliance}!',
                'priority' => 'normal',
                'data_template' => [
                    'alliance_name' => '',
                    'enemy_alliance' => '',
                    'war_duration' => '',
                ],
            ],
            'quest_completed' => [
                'title' => 'Quest Completed!',
                'message' => 'You have completed the quest "{quest_name}" and received {reward}!',
                'priority' => 'normal',
                'data_template' => [
                    'quest_name' => '',
                    'reward' => '',
                    'experience_gained' => '',
                ],
            ],
            'achievement_unlocked' => [
                'title' => 'Achievement Unlocked!',
                'message' => 'Congratulations! You have unlocked the achievement "{achievement_name}"!',
                'priority' => 'normal',
                'data_template' => [
                    'achievement_name' => '',
                    'achievement_description' => '',
                    'reward' => '',
                ],
            ],
            'maintenance_scheduled' => [
                'title' => 'Scheduled Maintenance',
                'message' => 'The server will undergo maintenance on {maintenance_date} from {start_time} to {end_time}.',
                'priority' => 'high',
                'data_template' => [
                    'maintenance_date' => '',
                    'start_time' => '',
                    'end_time' => '',
                    'duration' => '',
                ],
            ],
        ];
    }

    /**
     * Send templated notification
     */
    public function sendTemplatedNotification(
        Player $player,
        string $templateKey,
        array $templateData,
        ?string $priority = null
    ): Notification {
        $templates = $this->createNotificationTemplates();

        if (! isset($templates[$templateKey])) {
            throw new \Exception("Notification template '{$templateKey}' not found");
        }

        $template = $templates[$templateKey];

        // Replace template variables
        $title = $this->replaceTemplateVariables($template['title'], $templateData);
        $message = $this->replaceTemplateVariables($template['message'], $templateData);
        $priority = $priority ?? $template['priority'];

        return $this->sendNotification(
            $player,
            $templateKey,
            $title,
            $message,
            array_merge($template['data_template'], $templateData),
            $priority
        );
    }

    /**
     * Replace template variables
     */
    protected function replaceTemplateVariables(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }

    /**
     * Clear player notification cache
     */
    protected function clearPlayerNotificationCache(Player $player): void
    {
        $patterns = [
            "unread_notifications_{$player->id}_*",
            "player_notifications_{$player->id}_*",
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }

    /**
     * Clear notification cache
     */
    protected function clearNotificationCache(): void
    {
        $patterns = [
            'notification_statistics',
            'unread_notifications_*',
            'player_notifications_*',
        ];

        foreach ($patterns as $pattern) {
            $this->cachingUtil->forgetPattern($pattern);
        }
    }
}

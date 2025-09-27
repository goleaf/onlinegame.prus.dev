<?php

namespace App\Console\Commands;

use App\Services\GameAnalyticsService;
use App\Services\GameEventService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GameAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'game:analytics 
                            {action : The action to perform (generate, cleanup, stats)}
                            {--filters= : JSON filters for analytics}
                            {--days=30 : Days for cleanup operations}
                            {--output= : Output file for reports}';

    /**
     * The console command description.
     */
    protected $description = 'Generate game analytics, cleanup old data, and manage game statistics';

    protected GameAnalyticsService $analyticsService;
    protected GameEventService $eventService;
    protected NotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->analyticsService = new GameAnalyticsService();
        $this->eventService = new GameEventService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            return match ($action) {
                'generate' => $this->generateAnalytics(),
                'cleanup' => $this->cleanupOldData(),
                'stats' => $this->showStats(),
                default => $this->showHelp(),
            };
        } catch (\Exception $e) {
            $this->error('Error executing command: ' . $e->getMessage());
            Log::error('GameAnalyticsCommand error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Generate analytics report
     */
    protected function generateAnalytics(): int
    {
        $this->info('Generating game analytics...');

        $filters = [];
        if ($this->option('filters')) {
            $filters = json_decode($this->option('filters'), true) ?? [];
        }

        $analytics = $this->analyticsService->getGameAnalytics($filters);

        // Display analytics summary
        $this->displayAnalyticsSummary($analytics);

        // Generate report if output file specified
        if ($outputFile = $this->option('output')) {
            $report = $this->analyticsService->generateReport($filters);
            file_put_contents($outputFile, $report);
            $this->info("Analytics report saved to: {$outputFile}");
        }

        $this->info('Analytics generation completed successfully!');
        return 0;
    }

    /**
     * Cleanup old data
     */
    protected function cleanupOldData(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up data older than {$days} days...");

        // Cleanup old events
        $eventsDeleted = $this->eventService->cleanupOldEvents($days);
        $this->info("Deleted {$eventsDeleted} old events");

        // Cleanup old notifications
        $notificationsDeleted = $this->notificationService->cleanupOldNotifications($days);
        $this->info("Deleted {$notificationsDeleted} old notifications");

        $totalDeleted = $eventsDeleted + $notificationsDeleted;
        $this->info("Total cleanup: {$totalDeleted} records deleted");

        return 0;
    }

    /**
     * Show statistics
     */
    protected function showStats(): int
    {
        $this->info('Game Statistics:');
        $this->line('');

        // Event statistics
        $eventStats = $this->eventService->getEventStats();
        $this->info('📊 Event Statistics:');
        $this->line("Total Events: {$eventStats['total_events']}");
        $this->line("Unread Events: {$eventStats['unread_events']}");
        $this->line("Today's Events: {$eventStats['today_events']}");
        $this->line('');

        // Notification statistics
        $notificationStats = $this->notificationService->getNotificationStats();
        $this->info('🔔 Notification Statistics:');
        $this->line("Total Notifications: {$notificationStats['total_notifications']}");
        $this->line("Unread Notifications: {$notificationStats['unread_notifications']}");
        $this->line("Today's Notifications: {$notificationStats['today_notifications']}");
        $this->line('');

        // Analytics statistics
        $analytics = $this->analyticsService->getGameAnalytics();
        $this->info('🎮 Game Analytics:');
        $this->line("Total Players: {$analytics['player_statistics']['total_players']}");
        $this->line("Active Players: {$analytics['player_statistics']['active_players']}");
        $this->line("Total Alliances: {$analytics['alliance_analytics']['total_alliances']}");
        $this->line("Total Battles: {$analytics['battle_analytics']['total_battles']}");
        $this->line("Total Villages: {$analytics['village_analytics']['total_villages']}");

        return 0;
    }

    /**
     * Show help information
     */
    protected function showHelp(): int
    {
        $this->info('Game Analytics Command Help:');
        $this->line('');
        $this->line('Available actions:');
        $this->line('  generate  - Generate comprehensive analytics report');
        $this->line('  cleanup   - Cleanup old events and notifications');
        $this->line('  stats     - Show current statistics');
        $this->line('');
        $this->line('Options:');
        $this->line('  --filters=JSON  - Apply filters to analytics (JSON format)');
        $this->line('  --days=N        - Days for cleanup operations (default: 30)');
        $this->line('  --output=FILE   - Output file for reports');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan game:analytics generate');
        $this->line('  php artisan game:analytics generate --output=report.txt');
        $this->line('  php artisan game:analytics cleanup --days=7');
        $this->line('  php artisan game:analytics stats');

        return 0;
    }

    /**
     * Display analytics summary
     */
    protected function displayAnalyticsSummary(array $analytics): void
    {
        $this->info('📊 Game Analytics Summary:');
        $this->line('');

        // Player Statistics
        $playerStats = $analytics['player_statistics'];
        $this->info('👥 Player Statistics:');
        $this->line("  Total Players: {$playerStats['total_players']}");
        $this->line("  Active Players: {$playerStats['active_players']}");
        $this->line("  Alliance Members: {$playerStats['alliance_members']}");
        $this->line("  Independent Players: {$playerStats['independent_players']}");
        $this->line('');

        // Battle Analytics
        $battleStats = $analytics['battle_analytics'];
        $this->info('⚔️ Battle Analytics:');
        $this->line("  Total Battles: {$battleStats['total_battles']}");
        $this->line("  Attacker Victories: {$battleStats['attacker_victories']}");
        $this->line("  Defender Victories: {$battleStats['defender_victories']}");
        $this->line("  Draws: {$battleStats['draws']}");
        $this->line('');

        // Alliance Analytics
        $allianceStats = $analytics['alliance_analytics'];
        $this->info('🤝 Alliance Analytics:');
        $this->line("  Total Alliances: {$allianceStats['total_alliances']}");
        $this->line("  Active Alliances: {$allianceStats['active_alliances']}");
        $this->line("  Average Members: " . number_format($allianceStats['average_members_per_alliance'], 2));
        $this->line("  Largest Alliance: {$allianceStats['largest_alliance']} members");
        $this->line('');

        // Village Analytics
        $villageStats = $analytics['village_analytics'];
        $this->info('🏘️ Village Analytics:');
        $this->line("  Total Villages: {$villageStats['total_villages']}");
        $this->line("  Average Population: " . number_format($villageStats['average_population'], 2));
        $this->line("  Villages with Defense: {$villageStats['villages_with_defense']}");
        $this->line('');

        // Performance Metrics
        $performance = $analytics['performance_metrics'];
        $this->info('⚡ Performance Metrics:');
        $this->line("  Memory Usage: " . $this->formatBytes($performance['memory_usage']['current']));
        $this->line("  Peak Memory: " . $this->formatBytes($performance['memory_usage']['peak']));
        $this->line("  Response Time: " . number_format($performance['response_time'] * 1000, 2) . "ms");
        $this->line('');
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

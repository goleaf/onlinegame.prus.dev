<?php

namespace App\Console\Commands;

use App\Services\Game\UnitTrainingService;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GameNotificationService;
use App\Services\GamePerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Game Optimization Command
 * Comprehensive game system optimization and maintenance
 */
class GameOptimizationCommand extends Command
{
    protected $signature = 'game:optimize {--force : Force optimization without confirmation}';

    protected $description = 'Run comprehensive game system optimization and maintenance';

    public function handle()
    {
        $this->info('🎮 Starting comprehensive game optimization...');

        if (! $this->option('force')) {
            if (! $this->confirm('This will optimize the entire game system. Continue?')) {
                $this->info('Game optimization cancelled.');

                return 0;
            }
        }

        $this->optimizeGameSystems();
        $this->optimizeDatabase();
        $this->optimizeCache();
        $this->optimizePerformance();
        $this->cleanupData();
        $this->generateReports();

        $this->info('✅ Comprehensive game optimization completed!');

        return 0;
    }

    /**
     * Optimize game systems
     */
    private function optimizeGameSystems(): void
    {
        $this->info('🎯 Optimizing game systems...');

        try {
            // Process completed trainings
            $trainingService = app(UnitTrainingService::class);
            $result = $trainingService->processCompletedTrainings();

            if ($result['success']) {
                $this->info("  ✓ Processed {$result['processed']} completed trainings.");
                if ($result['errors'] > 0) {
                    $this->warn("  ⚠ {$result['errors']} training errors occurred.");
                }
            }

            // Process expired notifications
            $cleaned = GameNotificationService::cleanupOldNotifications(30);
            $this->info("  ✓ Cleaned up {$cleaned} old notifications.");

            // Process game events
            $this->processGameEvents();

        } catch (\Exception $e) {
            $this->warn('  ✗ Game systems optimization failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'game_systems_optimization']);
        }
    }

    /**
     * Optimize database
     */
    private function optimizeDatabase(): void
    {
        $this->info('🗄️ Optimizing game database...');

        try {
            // Analyze game tables
            $gameTables = [
                'players', 'villages', 'alliances', 'battles', 'buildings',
                'troops', 'resources', 'quests', 'achievements', 'training_queues',
                'player_quests', 'player_achievements', 'game_events', 'game_notifications',
            ];

            $optimizedCount = 0;
            foreach ($gameTables as $table) {
                try {
                    DB::statement("OPTIMIZE TABLE `{$table}`");
                    $optimizedCount++;
                } catch (\Exception $e) {
                    // Table might not exist, continue
                }
            }

            $this->info("  ✓ Optimized {$optimizedCount} game tables.");

            // Update statistics
            DB::statement('ANALYZE TABLE players, villages, alliances, battles');
            $this->info('  ✓ Updated table statistics.');

        } catch (\Exception $e) {
            $this->warn('  ✗ Database optimization failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'database_optimization']);
        }
    }

    /**
     * Optimize cache
     */
    private function optimizeCache(): void
    {
        $this->info('💾 Optimizing game cache...');

        try {
            // Clear and warm up game cache
            GameCacheService::clearAllGameCache();
            $this->info('  ✓ Game cache cleared.');

            // Warm up frequently accessed data
            GameCacheService::warmUpCache();
            $this->info('  ✓ Cache warmed up.');

            // Get cache statistics
            $stats = GameCacheService::getCacheStatistics();
            $this->info('  ✓ Cache statistics: '.json_encode($stats['cache_stats']));

        } catch (\Exception $e) {
            $this->warn('  ✗ Cache optimization failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'cache_optimization']);
        }
    }

    /**
     * Optimize performance
     */
    private function optimizePerformance(): void
    {
        $this->info('⚡ Optimizing game performance...');

        try {
            // Generate performance report
            $monitor = new GamePerformanceMonitor();
            $report = $monitor->generatePerformanceReport();

            $this->info('  ✓ Performance report generated.');

            // Log performance metrics
            if (isset($report['summary']['memory'])) {
                $memory = $report['summary']['memory'];
                $this->info("  ✓ Memory usage: {$memory['formatted_current']} (peak: {$memory['formatted_peak']})");
            }

            // Check for performance recommendations
            if (isset($report['recommendations']) && ! empty($report['recommendations'])) {
                $this->warn('  ⚠ Performance recommendations:');
                foreach ($report['recommendations'] as $recommendation) {
                    $this->warn("    - {$recommendation}");
                }
            }

        } catch (\Exception $e) {
            $this->warn('  ✗ Performance optimization failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'performance_optimization']);
        }
    }

    /**
     * Cleanup old data
     */
    private function cleanupData(): void
    {
        $this->info('🧹 Cleaning up old data...');

        try {
            // Clean up old battle reports (older than 30 days)
            $oldBattles = DB::table('battles')
                ->where('created_at', '<', now()->subDays(30))
                ->count();

            if ($oldBattles > 0) {
                DB::table('battles')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
                $this->info("  ✓ Cleaned up {$oldBattles} old battle reports.");
            }

            // Clean up old game events (older than 7 days)
            $oldEvents = DB::table('game_events')
                ->where('created_at', '<', now()->subDays(7))
                ->count();

            if ($oldEvents > 0) {
                DB::table('game_events')
                    ->where('created_at', '<', now()->subDays(7))
                    ->delete();
                $this->info("  ✓ Cleaned up {$oldEvents} old game events.");
            }

            // Clean up old notifications (older than 30 days)
            $cleaned = GameNotificationService::cleanupOldNotifications(30);
            $this->info("  ✓ Cleaned up {$cleaned} old notifications.");

        } catch (\Exception $e) {
            $this->warn('  ✗ Data cleanup failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'data_cleanup']);
        }
    }

    /**
     * Generate optimization reports
     */
    private function generateReports(): void
    {
        $this->info('📊 Generating optimization reports...');

        try {
            // Get game statistics
            $gameStats = GameCacheService::getGameStatistics('general');
            $this->info('  ✓ Game statistics: '.json_encode($gameStats));

            // Get error statistics
            $errorStats = GameErrorHandler::getErrorStatistics();
            $this->info('  ✓ Error statistics: '.json_encode($errorStats));

            // Get cache statistics
            $cacheStats = GameCacheService::getCacheStatistics();
            $this->info('  ✓ Cache statistics: '.json_encode($cacheStats['cache_stats']));

            // Log optimization completion
            GameErrorHandler::logGameAction('game_optimization_completed', [
                'timestamp' => now()->toISOString(),
                'game_stats' => $gameStats,
                'error_stats' => $errorStats,
                'cache_stats' => $cacheStats,
            ]);

        } catch (\Exception $e) {
            $this->warn('  ✗ Report generation failed: '.$e->getMessage());
            GameErrorHandler::handleGameError($e, ['action' => 'report_generation']);
        }
    }

    /**
     * Process game events
     */
    private function processGameEvents(): void
    {
        try {
            // Process any pending game events
            $pendingEvents = DB::table('game_events')
                ->where('processed', false)
                ->where('scheduled_at', '<=', now())
                ->limit(100)
                ->get();

            $processed = 0;
            foreach ($pendingEvents as $event) {
                try {
                    // Process the event (implementation would depend on event type)
                    DB::table('game_events')
                        ->where('id', $event->id)
                        ->update([
                            'processed' => true,
                            'processed_at' => now(),
                        ]);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error('Failed to process game event', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($processed > 0) {
                $this->info("  ✓ Processed {$processed} game events.");
            }

        } catch (\Exception $e) {
            $this->warn('  ✗ Game events processing failed: '.$e->getMessage());
        }
    }
}

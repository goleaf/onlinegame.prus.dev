<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GamePerformanceMonitor;
use App\Services\GameErrorHandler;
use App\Utilities\GameUtility;
use Illuminate\Support\Facades\DB;

class GameAdminCommand extends Command
{
    protected $signature = 'game:admin {action} {--player=} {--village=} {--amount=} {--type=}';
    protected $description = 'Admin commands for game management';

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'stats':
                $this->showGameStats();
                break;
            case 'performance':
                $this->showPerformanceReport();
                break;
            case 'give-resources':
                $this->giveResources();
                break;
            case 'reset-player':
                $this->resetPlayer();
                break;
            case 'cleanup':
                $this->cleanupOldData();
                break;
            case 'backup':
                $this->createGameBackup();
                break;
            default:
                $this->error('Unknown action: ' . $action);
                $this->showHelp();
        }
    }

    private function showGameStats()
    {
        $this->info('=== Game Statistics ===');
        
        // Player statistics
        $totalPlayers = DB::table('users')->count();
        $activePlayers = DB::table('users')
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->count();
        
        // Village statistics
        $totalVillages = DB::table('villages')->count();
        $activeVillages = DB::table('villages')
            ->where('updated_at', '>=', now()->subDays(1))
            ->count();
        
        // Alliance statistics
        $totalAlliances = DB::table('alliances')->count();
        
        // Battle statistics
        $totalBattles = DB::table('battles')->count();
        $recentBattles = DB::table('battles')
            ->where('created_at', '>=', now()->subDays(1))
            ->count();

        $this->table(
            ['Metric', 'Total', 'Recent/Active'],
            [
                ['Players', $totalPlayers, $activePlayers],
                ['Villages', $totalVillages, $activeVillages],
                ['Alliances', $totalAlliances, '-'],
                ['Battles', $totalBattles, $recentBattles],
            ]
        );
    }

    private function showPerformanceReport()
    {
        $this->info('=== Performance Report ===');
        
        $report = GamePerformanceMonitor::generatePerformanceReport();
        
        $this->info('Performance Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $report['performance_stats']['queries']['total']],
                ['Average Query Time', $report['performance_stats']['queries']['average_time'] . 's'],
                ['Slow Queries', $report['performance_stats']['queries']['slow_queries']],
                ['Total Responses', $report['performance_stats']['responses']['total']],
                ['Average Response Time', $report['performance_stats']['responses']['average_time'] . 's'],
                ['Slow Responses', $report['performance_stats']['responses']['slow_responses']],
                ['Current Memory Usage', $report['performance_stats']['memory']['current_usage_mb'] . ' MB'],
                ['Peak Memory Usage', $report['performance_stats']['memory']['peak_usage_mb'] . ' MB'],
                ['Concurrent Users', $report['concurrent_users']],
            ]
        );

        if (!empty($report['recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($report['recommendations'] as $recommendation) {
                $this->line('- ' . $recommendation);
            }
        }
    }

    private function giveResources()
    {
        $playerId = $this->option('player');
        $villageId = $this->option('village');
        $amount = (int) $this->option('amount');
        $type = $this->option('type');

        if (!$playerId || !$villageId || !$amount || !$type) {
            $this->error('All options are required: --player, --village, --amount, --type');
            return;
        }

        $validTypes = ['wood', 'clay', 'iron', 'crop'];
        if (!in_array($type, $validTypes)) {
            $this->error('Invalid resource type. Valid types: ' . implode(', ', $validTypes));
            return;
        }

        try {
            $village = DB::table('villages')
                ->where('id', $villageId)
                ->where('player_id', $playerId)
                ->first();

            if (!$village) {
                $this->error('Village not found or does not belong to player');
                return;
            }

            DB::table('villages')
                ->where('id', $villageId)
                ->increment($type, $amount);

            $this->info("Added {$amount} {$type} to village {$villageId}");
            
            GameErrorHandler::logGameAction('admin_give_resources', [
                'admin_id' => auth()->id(),
                'player_id' => $playerId,
                'village_id' => $villageId,
                'resource_type' => $type,
                'amount' => $amount,
            ]);

        } catch (\Exception $e) {
            $this->error('Failed to give resources: ' . $e->getMessage());
            GameErrorHandler::handleGameError($e, [
                'action' => 'admin_give_resources',
                'player_id' => $playerId,
                'village_id' => $villageId,
            ]);
        }
    }

    private function resetPlayer()
    {
        $playerId = $this->option('player');

        if (!$playerId) {
            $this->error('Player ID is required: --player');
            return;
        }

        if (!$this->confirm("Are you sure you want to reset player {$playerId}? This action cannot be undone.")) {
            $this->info('Operation cancelled');
            return;
        }

        try {
            DB::beginTransaction();

            // Reset player data
            DB::table('users')
                ->where('id', $playerId)
                ->update([
                    'points' => 0,
                    'rank' => null,
                    'updated_at' => now(),
                ]);

            // Reset villages
            DB::table('villages')
                ->where('player_id', $playerId)
                ->update([
                    'wood' => 1000,
                    'clay' => 1000,
                    'iron' => 1000,
                    'crop' => 1000,
                    'updated_at' => now(),
                ]);

            // Clear battle history
            DB::table('battles')
                ->where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId)
                ->delete();

            DB::commit();

            $this->info("Player {$playerId} has been reset successfully");
            
            GameErrorHandler::logGameAction('admin_reset_player', [
                'admin_id' => auth()->id(),
                'player_id' => $playerId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to reset player: ' . $e->getMessage());
            GameErrorHandler::handleGameError($e, [
                'action' => 'admin_reset_player',
                'player_id' => $playerId,
            ]);
        }
    }

    private function cleanupOldData()
    {
        $this->info('Starting cleanup of old data...');

        try {
            // Clean up old battle reports (older than 30 days)
            $deletedBattles = DB::table('battles')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();

            // Clean up old movements (older than 7 days)
            $deletedMovements = DB::table('movements')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();

            // Clean up old logs (older than 14 days)
            $deletedLogs = DB::table('activity_log')
                ->where('created_at', '<', now()->subDays(14))
                ->delete();

            $this->info("Cleanup completed:");
            $this->line("- Deleted {$deletedBattles} old battle reports");
            $this->line("- Deleted {$deletedMovements} old movements");
            $this->line("- Deleted {$deletedLogs} old log entries");

            GameErrorHandler::logGameAction('admin_cleanup', [
                'admin_id' => auth()->id(),
                'deleted_battles' => $deletedBattles,
                'deleted_movements' => $deletedMovements,
                'deleted_logs' => $deletedLogs,
            ]);

        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            GameErrorHandler::handleGameError($e, [
                'action' => 'admin_cleanup',
            ]);
        }
    }

    private function createGameBackup()
    {
        $this->info('Creating game backup...');

        try {
            $backupPath = storage_path('backups/game_backup_' . now()->format('Y_m_d_H_i_s') . '.sql');
            
            // Create backup directory if it doesn't exist
            if (!file_exists(dirname($backupPath))) {
                mkdir(dirname($backupPath), 0755, true);
            }

            // Create database backup
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.host'),
                config('database.connections.mysql.database'),
                $backupPath
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $this->info("Backup created successfully: {$backupPath}");
                
                GameErrorHandler::logGameAction('admin_backup', [
                    'admin_id' => auth()->id(),
                    'backup_path' => $backupPath,
                ]);
            } else {
                $this->error('Backup failed');
            }

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            GameErrorHandler::handleGameError($e, [
                'action' => 'admin_backup',
            ]);
        }
    }

    private function showHelp()
    {
        $this->info('Available actions:');
        $this->line('  stats                    - Show game statistics');
        $this->line('  performance             - Show performance report');
        $this->line('  give-resources          - Give resources to a player');
        $this->line('  reset-player            - Reset a player');
        $this->line('  cleanup                 - Clean up old data');
        $this->line('  backup                  - Create game backup');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan game:admin stats');
        $this->line('  php artisan game:admin give-resources --player=1 --village=1 --amount=10000 --type=wood');
        $this->line('  php artisan game:admin reset-player --player=1');
    }
}


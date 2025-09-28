<?php

namespace App\Console\Commands;

use App\Services\LaradumpsHelperService;
use Illuminate\Console\Command;

class LaradumpsTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradumps:test {--component= : Test specific component} {--service= : Test specific service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Laradumps integration with game components and services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Laradumps Integration Test');
        $this->line('=====================================');

        $component = $this->option('component');
        $service = $this->option('service');

        if ($component) {
            $this->testComponent($component);
        } elseif ($service) {
            $this->testService($service);
        } else {
            $this->testAll();
        }

        $this->newLine();
        $this->info('✅ Laradumps test completed!');
        $this->line('📊 Check the Laradumps desktop app for debug output.');
    }

    /**
     * Test all components and services
     */
    private function testAll(): void
    {
        $this->info('Testing all Laradumps integrations...');

        // Test helper service
        LaradumpsHelperService::debug('Laradumps Test Command', [
            'test_type' => 'comprehensive',
            'components_tested' => ['EnhancedGameDashboard', 'BattleManager', 'TaskManager', 'MovementManager'],
            'services_tested' => ['GameTickService', 'GameMechanicsService', 'GameIntegrationService'],
            'timestamp' => now(),
        ], 'Laradumps Test Command');

        // Test performance
        $startTime = microtime(true);
        usleep(100000); // Simulate processing
        LaradumpsHelperService::debugPerformance('Test Command Execution', $startTime);

        // Test system health
        LaradumpsHelperService::debugSystemHealth();

        $this->line('✅ All tests completed');
    }

    /**
     * Test specific component
     */
    private function testComponent(string $component): void
    {
        $this->info("Testing component: {$component}");

        switch ($component) {
            case 'dashboard':
                $this->testDashboardComponent();

                break;
            case 'battle':
                $this->testBattleComponent();

                break;
            case 'task':
                $this->testTaskComponent();

                break;
            case 'movement':
                $this->testMovementComponent();

                break;
            default:
                $this->error("Unknown component: {$component}");

                return;
        }

        $this->line("✅ Component {$component} tested");
    }

    /**
     * Test specific service
     */
    private function testService(string $service): void
    {
        $this->info("Testing service: {$service}");

        switch ($service) {
            case 'game-tick':
                $this->testGameTickService();

                break;
            case 'mechanics':
                $this->testGameMechanicsService();

                break;
            case 'integration':
                $this->testGameIntegrationService();

                break;
            default:
                $this->error("Unknown service: {$service}");

                return;
        }

        $this->line("✅ Service {$service} tested");
    }

    /**
     * Test dashboard component
     */
    private function testDashboardComponent(): void
    {
        LaradumpsHelperService::debugComponentMount('EnhancedGameDashboard', [
            'player_id' => 123,
            'villages_count' => 3,
            'game_tick_active' => true,
        ]);

        LaradumpsHelperService::debugGameAction('Village Selection', [
            'village_id' => 456,
            'village_name' => 'Test Village',
            'coordinates' => '(100|200)',
        ]);
    }

    /**
     * Test battle component
     */
    private function testBattleComponent(): void
    {
        LaradumpsHelperService::debugComponentMount('BattleManager', [
            'player_id' => 123,
            'available_targets' => 15,
        ]);

        LaradumpsHelperService::debugGameAction('Attack Launch', [
            'from_village' => 'Attacker Village',
            'to_village' => 'Defender Village',
            'distance' => 15.5,
            'troops' => ['legionnaire' => 10, 'praetorian' => 5],
        ]);

        LaradumpsHelperService::debugGeographic('Attack Distance Calculation', [
            'game_distance' => 15.5,
            'real_world_distance_km' => 23.7,
            'travel_time' => 1800,
        ]);
    }

    /**
     * Test task component
     */
    private function testTaskComponent(): void
    {
        LaradumpsHelperService::debugComponentMount('TaskManager', [
            'player_id' => 123,
            'available_tasks' => 8,
        ]);

        LaradumpsHelperService::debugGameAction('Task Completion', [
            'task_id' => 456,
            'reference_number' => 'TSK-2025010001',
            'rewards' => ['experience' => 100, 'resources' => ['wood' => 500]],
        ]);
    }

    /**
     * Test movement component
     */
    private function testMovementComponent(): void
    {
        LaradumpsHelperService::debugComponentMount('MovementManager', [
            'player_id' => 123,
            'active_movements' => 5,
        ]);

        LaradumpsHelperService::debugGameAction('Movement Creation', [
            'movement_type' => 'attack',
            'from_village' => 'Home Village',
            'to_village' => 'Target Village',
            'travel_time' => 1440,
        ]);
    }

    /**
     * Test game tick service
     */
    private function testGameTickService(): void
    {
        $startTime = microtime(true);

        LaradumpsHelperService::debugService('GameTickService', 'processGameTick', [
            'tick_id' => 'tick_' . now()->format('Y-m-d-H-i'),
            'villages_to_process' => 150,
        ]);

        LaradumpsHelperService::debugPerformance('Game Tick Processing', $startTime, [
            'villages_processed' => 150,
            'memory_usage' => memory_get_usage(true),
        ]);
    }

    /**
     * Test game mechanics service
     */
    private function testGameMechanicsService(): void
    {
        LaradumpsHelperService::debugService('GameMechanicsService', 'processWorldMechanics', [
            'world_id' => 1,
            'world_name' => 'Test World',
            'villages_count' => 150,
        ]);
    }

    /**
     * Test game integration service
     */
    private function testGameIntegrationService(): void
    {
        LaradumpsHelperService::debugService('GameIntegrationService', 'initializeUserRealTime', [
            'user_id' => 789,
            'player_id' => 123,
            'villages_count' => 3,
        ]);
    }
}

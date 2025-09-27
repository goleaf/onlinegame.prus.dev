<?php

namespace App\Console\Commands;

use App\Services\RabbitMQService;
use Illuminate\Console\Command;

class TestRabbitMQ extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:test {--type=all : Type of test to run (all, game-events, notifications, custom)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test RabbitMQ integration by publishing sample messages';

    protected $rabbitMQ;

    public function __construct()
    {
        parent::__construct();
        $this->rabbitMQ = new RabbitMQService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');

        $this->info('Testing RabbitMQ Integration...');
        $this->newLine();

        switch ($type) {
            case 'game-events':
                $this->testGameEvents();
                break;
            case 'notifications':
                $this->testNotifications();
                break;
            case 'custom':
                $this->testCustomMessages();
                break;
            case 'all':
            default:
                $this->testGameEvents();
                $this->testNotifications();
                $this->testCustomMessages();
                break;
        }

        $this->newLine();
        $this->info('RabbitMQ test completed! Check your consumer logs for message processing.');
        $this->info('To start the consumer, run: php artisan amqp:consume default game_events');
    }

    private function testGameEvents()
    {
        $this->info('Testing Game Events...');

        // Test player action
        $this->rabbitMQ->publishPlayerAction(1, 'login', ['ip' => '127.0.0.1']);
        $this->line('✓ Published player login event');

        $this->rabbitMQ->publishPlayerAction(1, 'attack', [
            'target_village_id' => 2,
            'troops_sent' => ['warrior' => 100, 'archer' => 50]
        ]);
        $this->line('✓ Published player attack event');

        // Test spy events
        $this->rabbitMQ->publishPlayerAction(1, 'spy_caught', [
            'target_village_id' => 2,
            'target_village_name' => 'Enemy Village',
            'trap_level' => 5,
            'spy_defense' => 25
        ]);
        $this->line('✓ Published spy caught event');

        $this->rabbitMQ->publishPlayerAction(1, 'spy_success', [
            'target_village_id' => 3,
            'target_village_name' => 'Target Village',
            'spy_data' => [
                'population' => 150,
                'resources' => ['wood' => 2000, 'clay' => 1500],
                'buildings' => ['barracks' => 3, 'wall' => 2]
            ]
        ]);
        $this->line('✓ Published spy success event');

        // Test battle simulation event
        $this->rabbitMQ->publishGameEvent('battle_simulation', [
            'simulation_type' => 'troop_optimization',
            'attacker_village_id' => 1,
            'defender_village_id' => 2,
            'total_troops' => 100,
            'best_win_rate' => 75.5,
            'optimal_composition' => [
                'warrior' => 60,
                'archer' => 30,
                'cavalry' => 10
            ],
            'timestamp' => now()->toISOString()
        ]);
        $this->line('✓ Published battle simulation event');

        // Test building completion
        $this->rabbitMQ->publishBuildingCompleted(1, 1, 'barracks');
        $this->line('✓ Published building completion event');

        // Test battle result with defensive bonuses
        $this->rabbitMQ->publishBattleResult(1, 2, [
            'result' => 'attacker_wins',
            'attacker_losses' => ['warrior' => 10],
            'defender_losses' => ['warrior' => 50],
            'resources_looted' => ['wood' => 1000, 'clay' => 500],
            'defensive_bonus' => 0.15,
            'battle_power' => [
                'attacker' => 1500,
                'defender' => 1200
            ]
        ]);
        $this->line('✓ Published battle result event with defensive bonuses');

        // Test resource update
        $this->rabbitMQ->publishResourceUpdate(1, [
            'wood' => 5000,
            'clay' => 3000,
            'iron' => 2000,
            'crop' => 1000
        ]);
        $this->line('✓ Published resource update event');
    }

    private function testNotifications()
    {
        $this->info('Testing Notifications...');

        // Test email notification
        $this->rabbitMQ->publishEmailNotification(
            'player@example.com',
            'Battle Report - Victory!',
            [
                'player_name' => 'TestPlayer',
                'battle_result' => 'victory',
                'village_name' => 'Test Village'
            ]
        );
        $this->line('✓ Published email notification');

        // Test in-game notification
        $this->rabbitMQ->publishInGameNotification(
            1,
            'Your barracks has been completed!',
            [
                'building_name' => 'barracks',
                'level' => 2,
                'village_id' => 1
            ]
        );
        $this->line('✓ Published in-game notification');
    }

    private function testCustomMessages()
    {
        $this->info('Testing Custom Messages...');

        // Test custom game event
        $this->rabbitMQ->publishGameEvent('custom_event', [
            'player_id' => 1,
            'village_id' => 1,
            'custom_data' => [
                'achievement' => 'first_victory',
                'reward' => ['gold' => 100]
            ]
        ]);
        $this->line('✓ Published custom game event');

        // Test custom notification
        $this->rabbitMQ->publishNotification('push', [
            'player_id' => 1,
            'title' => 'Game Update Available',
            'message' => 'New features have been added to the game!',
            'action_url' => '/game/updates'
        ]);
        $this->line('✓ Published custom push notification');
    }
}

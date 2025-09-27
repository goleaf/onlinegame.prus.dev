<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartRabbitMQConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:start-consumer {--connection=default : RabbitMQ connection name} {--queue=game_events : Queue name to consume from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start RabbitMQ consumer for processing game events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->option('connection');
        $queue = $this->option('queue');

        $this->info("Starting RabbitMQ consumer...");
        $this->info("Connection: {$connection}");
        $this->info("Queue: {$queue}");
        $this->newLine();

        $this->info('Consumer started. Press Ctrl+C to stop.');
        $this->info('Messages will be processed as they arrive...');
        $this->newLine();

        try {
            // Start the consumer process
            $process = Process::start("php artisan amqp:consume {$connection} {$queue}");

            // Monitor the process
            while ($process->running()) {
                $output = $process->latestOutput();
                if ($output) {
                    $this->line($output);
                }
                
                sleep(1);
            }

        } catch (\Exception $e) {
            $this->error('Failed to start consumer: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

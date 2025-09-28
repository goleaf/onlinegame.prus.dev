<?php

namespace App\Console\Commands;

use App\Services\TrainingQueueService;
use Illuminate\Console\Command;

class ProcessTrainingQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:process-training-queues {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process completed training queues and add troops to villages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing training queues...');

        $trainingQueueService = new TrainingQueueService();

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No changes will be made');

            $completedQueues = \App\Models\Game\TrainingQueue::where('status', 'in_progress')
                ->where('completed_at', '<=', now())
                ->with(['village', 'unitType'])
                ->get();

            if ($completedQueues->isEmpty()) {
                $this->info('No training queues ready for completion.');

                return;
            }

            $this->info("Found {$completedQueues->count()} training queues ready for completion:");

            $headers = ['ID', 'Village', 'Unit Type', 'Quantity', 'Reference', 'Completed At'];
            $rows = [];

            foreach ($completedQueues as $queue) {
                $rows[] = [
                    $queue->id,
                    $queue->village->name.' ('.$queue->village->x.'|'.$queue->village->y.')',
                    $queue->unitType->name,
                    number_format($queue->count),
                    $queue->reference_number,
                    $queue->completed_at->format('Y-m-d H:i:s'),
                ];
            }

            $this->table($headers, $rows);

            return;
        }

        $processed = $trainingQueueService->processCompletedTraining();

        if ($processed > 0) {
            $this->info("Successfully processed {$processed} training queues.");
        } else {
            $this->info('No training queues were ready for completion.');
        }

        // Show statistics
        $totalActive = \App\Models\Game\TrainingQueue::where('status', 'in_progress')->count();
        $totalCompleted = \App\Models\Game\TrainingQueue::where('status', 'completed')->count();
        $totalCancelled = \App\Models\Game\TrainingQueue::where('status', 'cancelled')->count();

        $this->info("\nTraining Queue Statistics:");
        $this->info("- Active: {$totalActive}");
        $this->info("- Completed: {$totalCompleted}");
        $this->info("- Cancelled: {$totalCancelled}");

        return Command::SUCCESS;
    }
}

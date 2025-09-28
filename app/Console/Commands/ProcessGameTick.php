<?php

namespace App\Console\Commands;

use App\Services\GameTickService;
use Illuminate\Console\Command;

class ProcessGameTick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:tick {--force : Force processing even if not scheduled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process game tick - handle movements, battles, resource production, and other game mechanics';

    protected $gameTickService;

    public function __construct(GameTickService $gameTickService)
    {
        parent::__construct();
        $this->gameTickService = $gameTickService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting game tick processing...');

        try {
            $startTime = microtime(true);

            $this->gameTickService->processGameTick();

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $this->info("Game tick processed successfully in {$executionTime}ms");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Game tick processing failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}

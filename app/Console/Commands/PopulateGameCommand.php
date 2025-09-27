<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PopulateGameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:populate 
                            {--fresh : Run migrations fresh before seeding}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the Travian game database using Laragear Populate enhanced seeding';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎮 Travian Game Database Population');
        $this->info('=====================================');

        if ($this->option('fresh')) {
            $this->info('🔄 Running fresh migrations...');
            Artisan::call('migrate:fresh', [
                '--force' => $this->option('force')
            ]);
            $this->info('✅ Migrations completed');
        }

        $this->info('🌱 Starting enhanced seeding with Laragear Populate...');

        try {
            Artisan::call('db:seed', [
                '--class' => 'GameSuperSeeder',
                '--force' => $this->option('force')
            ]);

            $this->info('✅ Game population completed successfully!');
            $this->info('🎯 Your Travian game is now ready to play!');

            // Show some statistics
            $this->showStatistics();
        } catch (\Exception $e) {
            $this->error('❌ Error during population: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Show database statistics after population.
     */
    protected function showStatistics(): void
    {
        $this->info('');
        $this->info('📊 Database Statistics:');
        $this->info('======================');

        try {
            $stats = [
                'Users' => \App\Models\User::count(),
                'Players' => \App\Models\Game\Player::count(),
                'Villages' => \App\Models\Game\Village::count(),
                'Worlds' => \App\Models\Game\World::count(),
                'Building Types' => \App\Models\Game\BuildingType::count(),
                'Unit Types' => \App\Models\Game\UnitType::count(),
                'Buildings' => \App\Models\Game\Building::count(),
                'Alliances' => \App\Models\Game\Alliance::count(),
            ];

            foreach ($stats as $type => $count) {
                $this->line("  {$type}: {$count}");
            }
        } catch (\Exception $e) {
            $this->warn('Could not retrieve statistics: ' . $e->getMessage());
        }
    }
}

<?php

namespace Database\Seeders;

use Laragear\Populate\Seeder;

class GameSuperSeeder extends Seeder
{
    /**
     * Run logic before executing the seed steps.
     */
    public function before(): void
    {
        $this->command->info('🚀 Starting Laragear Populate enhanced seeding...');
        $this->command->info('📊 This will create comprehensive game data with improved structure!');
    }

    /**
     * Populate the database with records using existing seeders.
     */
    public function seed(): void
    {
        $this->seedCoreData();
        $this->seedGameData();
        $this->seedTableData();
    }

    /**
     * Run logic after executing the seed steps.
     */
    public function after(): void
    {
        $this->command->info('✅ Laragear Populate seeding completed successfully!');
        $this->command->info('🎮 Your Travian game is now fully populated and ready!');
    }

    /**
     * Seed core game data using existing seeders.
     */
    protected function seedCoreData(): void
    {
        $this->command->info('👤 Seeding admin user and core data...');
        $this->call(AdminUserSeeder::class);
        $this->command->info('✅ Core data seeded');
    }

    /**
     * Seed game configuration and types.
     */
    protected function seedGameData(): void
    {
        $this->command->info('🎮 Seeding game configuration data...');
        $this->call(GameDataSeeder::class);
        $this->command->info('✅ Game configuration data seeded');
    }

    /**
     * Seed table-specific data using existing table seeders.
     */
    protected function seedTableData(): void
    {
        $this->command->info('📋 Seeding table-specific data...');
        
        $tableSeeders = [
            \Database\Seeders\Tables\PlayersSeeder::class,
            \Database\Seeders\Tables\VillagesSeeder::class,
            \Database\Seeders\Tables\WorldsSeeder::class,
            \Database\Seeders\Tables\AchievementsSeeder::class,
            \Database\Seeders\Tables\BuildingTypesSeeder::class,
            \Database\Seeders\Tables\BuildingsSeeder::class,
            \Database\Seeders\Tables\QuestsSeeder::class,
            \Database\Seeders\Tables\UnitTypesSeeder::class,
        ];

        foreach ($tableSeeders as $seeder) {
            $this->command->info("  → Running {$seeder}...");
            $this->call($seeder);
        }

        $this->command->info('✅ All table data seeded');
    }
}

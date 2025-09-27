<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GameDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            GameConfigSeeder::class,
            BuildingTypeSeeder::class,
            UnitTypeSeeder::class,
            TechnologySeeder::class,
            QuestSeeder::class,
            AchievementSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user first
        $this->call(AdminUserSeeder::class);

        // Create game data
        $this->call(GameDataSeeder::class);

        // Create additional test users
        User::factory(5)->create();
        $this->call(\Database\Seeders\Tables\PlayersSeeder::class);
        $this->call(\Database\Seeders\Tables\VillagesSeeder::class);
        $this->call(\Database\Seeders\Tables\WorldsSeeder::class);
        $this->call(\Database\Seeders\Tables\AchievementsSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingTypesSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingsSeeder::class);
        $this->call(\Database\Seeders\Tables\QuestsSeeder::class);
        $this->call(\Database\Seeders\Tables\UnitTypesSeeder::class);
        $this->call(\Database\Seeders\Tables\database\seeders\Tables\UsersTestSeeder.php\UsersSeeder::class);
        $this->call(\Database\Seeders\Tables\database\seeders\Tables\ActivePlayersSeeder.php\PlayersSeeder::class);
        $this->call(\Database\Seeders\Tables\GameConfigsSeeder::class);
        $this->call(\GameEvents::class);
        $this->call(\Database\Seeders\Tables\GameNotificationsSeeder::class);
        $this->call(\Database\Seeders\Tables\GameTasksSeeder::class);
    }
}

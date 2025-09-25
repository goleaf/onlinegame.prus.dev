<?php

namespace Database\Seeders;

use App\Models\Game\Building;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TravianGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test world
        $world = World::create([
            'name' => 'Test World',
            'description' => 'A test world for the Travian game',
            'is_active' => true,
            'max_players' => 1000,
            'speed' => 1.0,
        ]);

        // Create a test user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'test@travian.com'],
            [
                'name' => 'Test Player',
                'password' => bcrypt('password'),
            ]
        );

        // Create a test player
        $player = Player::create([
            'user_id' => $user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'population' => 0,
            'villages_count' => 0,
            'is_active' => true,
        ]);

        // Create a capital village for the player
        $village = Village::create([
            'player_id' => $player->id,
            'world_id' => $world->id,
            'name' => 'Capital',
            'x_coordinate' => 50,
            'y_coordinate' => 50,
            'population' => 2,
            'is_capital' => true,
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
            'wood_capacity' => 1000,
            'clay_capacity' => 1000,
            'iron_capacity' => 1000,
            'crop_capacity' => 1000,
        ]);

        // Create resource buildings
        $buildings = [
            ['type' => 'wood', 'level' => 1, 'name' => 'Woodcutter'],
            ['type' => 'clay', 'level' => 1, 'name' => 'Clay Pit'],
            ['type' => 'iron', 'level' => 1, 'name' => 'Iron Mine'],
            ['type' => 'crop', 'level' => 1, 'name' => 'Cropland'],
        ];

        foreach ($buildings as $building) {
            Building::create([
                'village_id' => $village->id,
                'type' => $building['type'],
                'level' => $building['level'],
                'name' => $building['name'],
                'is_active' => true,
            ]);
        }

        // Update player statistics
        $player->update([
            'population' => $village->population,
            'villages_count' => 1,
        ]);

        $this->command->info('Travian game seeded successfully!');
        $this->command->info("World: {$world->name}");
        $this->command->info("Player: {$player->name}");
        $this->command->info("Village: {$village->name} at ({$village->x_coordinate}|{$village->y_coordinate})");
    }
}

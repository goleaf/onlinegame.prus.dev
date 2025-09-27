<?php

namespace Database\Seeders;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Create default world
        $world = World::create([
            'name' => 'Travian World 1',
            'description' => 'The main Travian world',
            'max_players' => 1000,
            'is_active' => true,
            'speed' => 1,
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
        ]);

        // Create admin player
        $adminPlayer = Player::create([
            'user_id' => $adminUser->id,
            'world_id' => $world->id,
            'name' => 'Admin',
            'tribe' => 'roman',
            'population' => 0,
            'villages_count' => 1,
            'is_active' => true,
            'is_online' => true,
            'last_login' => now(),
            'last_active_at' => now(),
            'points' => 0,
        ]);

        // Create admin's capital village
        $capitalVillage = Village::create([
            'player_id' => $adminPlayer->id,
            'world_id' => $world->id,
            'name' => 'Capital',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 2,
            'is_capital' => true,
            'is_active' => true,
        ]);

        // Create resources for the capital
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
        foreach ($resourceTypes as $type) {
            Resource::create([
                'village_id' => $capitalVillage->id,
                'type' => $type,
                'amount' => 1000,
                'production_rate' => 10,
                'storage_capacity' => 800,
            ]);
        }

        // Create basic buildings for the capital
        $buildingTypes = BuildingType::where('is_active', true)->take(4)->get();

        foreach ($buildingTypes as $index => $buildingType) {
            Building::create([
                'village_id' => $capitalVillage->id,
                'building_type_id' => $buildingType->id,
                'level' => 1,
                'x' => $index,
                'y' => 0,
            ]);
        }

        // Update player statistics
        $adminPlayer->update([
            'population' => $capitalVillage->population,
            'villages_count' => 1,
            'points' => $capitalVillage->population * 10,
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password123');
    }
}

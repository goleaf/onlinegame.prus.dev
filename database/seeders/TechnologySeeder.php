<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            [
                'name' => 'Spy',
                'key' => 'spy',
                'description' => 'Allows training of spy units',
                'requirements' => json_encode(['academy' => 1]),
                'costs' => json_encode(['wood' => 200, 'clay' => 200, 'iron' => 200, 'crop' => 200]),
                'effects' => json_encode(['unlock_unit' => 'spy']),
                'is_active' => true,
            ],
            [
                'name' => 'Horseback Riding',
                'key' => 'horseback_riding',
                'description' => 'Allows training of cavalry units',
                'requirements' => json_encode(['academy' => 5, 'stable' => 1]),
                'costs' => json_encode(['wood' => 400, 'clay' => 400, 'iron' => 400, 'crop' => 400]),
                'effects' => json_encode(['unlock_unit' => 'cavalry']),
                'is_active' => true,
            ],
            [
                'name' => 'Iron Casting',
                'key' => 'iron_casting',
                'description' => 'Improves weapon strength',
                'requirements' => json_encode(['academy' => 3]),
                'costs' => json_encode(['wood' => 300, 'clay' => 300, 'iron' => 300, 'crop' => 300]),
                'effects' => json_encode(['attack_bonus' => 0.1]),
                'is_active' => true,
            ],
            [
                'name' => 'Armor',
                'key' => 'armor',
                'description' => 'Improves defensive strength',
                'requirements' => json_encode(['academy' => 3]),
                'costs' => json_encode(['wood' => 300, 'clay' => 300, 'iron' => 300, 'crop' => 300]),
                'effects' => json_encode(['defense_bonus' => 0.1]),
                'is_active' => true,
            ],
            [
                'name' => 'Horseback Riding',
                'key' => 'horseback_riding',
                'description' => 'Allows training of cavalry units',
                'requirements' => json_encode(['academy' => 5, 'stable' => 1]),
                'costs' => json_encode(['wood' => 400, 'clay' => 400, 'iron' => 400, 'crop' => 400]),
                'effects' => json_encode(['unlock_unit' => 'cavalry']),
                'is_active' => true,
            ],
            [
                'name' => 'Compass',
                'key' => 'compass',
                'description' => 'Increases movement speed',
                'requirements' => json_encode(['academy' => 5]),
                'costs' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'effects' => json_encode(['speed_bonus' => 0.1]),
                'is_active' => true,
            ],
            [
                'name' => 'Architecture',
                'key' => 'architecture',
                'description' => 'Reduces construction time',
                'requirements' => json_encode(['academy' => 3]),
                'costs' => json_encode(['wood' => 300, 'clay' => 300, 'iron' => 300, 'crop' => 300]),
                'effects' => json_encode(['construction_speed' => 0.1]),
                'is_active' => true,
            ],
            [
                'name' => 'Trade',
                'key' => 'trade',
                'description' => 'Allows trading with other players',
                'requirements' => json_encode(['academy' => 3, 'market' => 1]),
                'costs' => json_encode(['wood' => 300, 'clay' => 300, 'iron' => 300, 'crop' => 300]),
                'effects' => json_encode(['unlock_trading' => true]),
                'is_active' => true,
            ],
            [
                'name' => 'Research',
                'key' => 'research',
                'description' => 'Reduces research time',
                'requirements' => json_encode(['academy' => 5]),
                'costs' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'effects' => json_encode(['research_speed' => 0.1]),
                'is_active' => true,
            ],
            [
                'name' => 'Training',
                'key' => 'training',
                'description' => 'Reduces training time',
                'requirements' => json_encode(['academy' => 5]),
                'costs' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'effects' => json_encode(['training_speed' => 0.1]),
                'is_active' => true,
            ],
        ];

        foreach ($technologies as $technology) {
            \App\Models\Game\Technology::firstOrCreate(
                ['key' => $technology['key']],
                $technology
            );
        }
    }
}

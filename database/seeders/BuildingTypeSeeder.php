<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildings = [
            // Resource Buildings
            [
                'name' => 'Woodcutter',
                'key' => 'woodcutter',
                'description' => 'Produces wood for your village',
                'max_level' => 20,
                'requirements' => json_encode([]),
                'costs' => json_encode(['wood' => 100, 'clay' => 80, 'iron' => 50, 'crop' => 30]),
                'production' => json_encode(['wood' => 2]),
                'population' => json_encode([2]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Clay Pit',
                'key' => 'clay_pit',
                'description' => 'Produces clay for your village',
                'max_level' => 20,
                'requirements' => json_encode([]),
                'costs' => json_encode(['wood' => 80, 'clay' => 100, 'iron' => 50, 'crop' => 30]),
                'production' => json_encode(['clay' => 2]),
                'population' => json_encode([2]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Iron Mine',
                'key' => 'iron_mine',
                'description' => 'Produces iron for your village',
                'max_level' => 20,
                'requirements' => json_encode([]),
                'costs' => json_encode(['wood' => 80, 'clay' => 80, 'iron' => 100, 'crop' => 30]),
                'production' => json_encode(['iron' => 2]),
                'population' => json_encode([2]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Cropland',
                'key' => 'cropland',
                'description' => 'Produces crop for your village',
                'max_level' => 20,
                'requirements' => json_encode([]),
                'costs' => json_encode(['wood' => 80, 'clay' => 80, 'iron' => 50, 'crop' => 100]),
                'production' => json_encode(['crop' => 2]),
                'population' => json_encode([2]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Warehouse',
                'key' => 'warehouse',
                'description' => 'Increases storage capacity for resources',
                'max_level' => 20,
                'requirements' => json_encode(['woodcutter' => 1, 'clay_pit' => 1, 'iron_mine' => 1]),
                'costs' => json_encode(['wood' => 130, 'clay' => 160, 'iron' => 90, 'crop' => 40]),
                'production' => json_encode([]),
                'population' => json_encode([1]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Granary',
                'key' => 'granary',
                'description' => 'Increases storage capacity for crop',
                'max_level' => 20,
                'requirements' => json_encode(['cropland' => 1]),
                'costs' => json_encode(['wood' => 130, 'clay' => 160, 'iron' => 90, 'crop' => 40]),
                'production' => json_encode([]),
                'population' => json_encode([1]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Barracks',
                'key' => 'barracks',
                'description' => 'Trains infantry units',
                'max_level' => 20,
                'requirements' => json_encode(['warehouse' => 1, 'granary' => 1]),
                'costs' => json_encode(['wood' => 200, 'clay' => 200, 'iron' => 200, 'crop' => 200]),
                'production' => json_encode([]),
                'population' => json_encode([4]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Academy',
                'key' => 'academy',
                'description' => 'Researches new technologies',
                'max_level' => 20,
                'requirements' => json_encode(['warehouse' => 1, 'granary' => 1]),
                'costs' => json_encode(['wood' => 200, 'clay' => 200, 'iron' => 200, 'crop' => 200]),
                'production' => json_encode([]),
                'population' => json_encode([4]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Rally Point',
                'key' => 'rally_point',
                'description' => 'Coordinates troop movements',
                'max_level' => 1,
                'requirements' => json_encode([]),
                'costs' => json_encode(['wood' => 110, 'clay' => 110, 'iron' => 110, 'crop' => 110]),
                'production' => json_encode([]),
                'population' => json_encode([2]),
                'is_special' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Market',
                'key' => 'market',
                'description' => 'Trades resources with other players',
                'max_level' => 20,
                'requirements' => json_encode(['warehouse' => 1, 'granary' => 1]),
                'costs' => json_encode(['wood' => 200, 'clay' => 200, 'iron' => 200, 'crop' => 200]),
                'production' => json_encode([]),
                'population' => json_encode([4]),
                'is_special' => false,
                'is_active' => true,
            ],
        ];

        foreach ($buildings as $building) {
            \App\Models\Game\BuildingType::firstOrCreate(
                ['key' => $building['key']],
                $building
            );
        }
    }
}

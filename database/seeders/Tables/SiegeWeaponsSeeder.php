<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiegeWeaponsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --table-mode --all-tables
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'reference_number' => NULL,
                'village_id' => 1,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 2,
                'reference_number' => NULL,
                'village_id' => 143,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 3,
                'reference_number' => NULL,
                'village_id' => 144,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 4,
                'reference_number' => NULL,
                'village_id' => 146,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 5,
                'reference_number' => NULL,
                'village_id' => 147,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 6,
                'reference_number' => NULL,
                'village_id' => 149,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 7,
                'reference_number' => NULL,
                'village_id' => 152,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 8,
                'reference_number' => NULL,
                'village_id' => 153,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 9,
                'reference_number' => NULL,
                'village_id' => 154,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ],
            [
                'id' => 10,
                'reference_number' => NULL,
                'village_id' => 155,
                'type' => 'ram',
                'name' => 'Battering Ram',
                'attack_power' => 50,
                'defense_power' => 20,
                'health' => 500,
                'max_health' => 500,
                'cost' => '{"wood":300,"clay":200,"iron":200,"crop":100}',
                'description' => 'Effective against walls and gates',
                'is_active' => 1,
                'created_at' => '2025-09-27 04:25:16',
                'updated_at' => '2025-09-27 04:25:16',
            ]
        ];
        
        DB::table("siege_weapons")->insert($dataTables);
    }
}
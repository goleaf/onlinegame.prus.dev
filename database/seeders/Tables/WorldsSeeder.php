<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorldsSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=worlds,players,villages
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'name' => 'Travian World 1',
                'description' => 'The main game world for all players',
                'is_active' => 1,
                'max_players' => 10000,
                'map_size' => 400,
                'speed' => 1,
                'has_plus' => 0,
                'has_artifacts' => 0,
                'start_date' => '2025-03-25 23:37:46',
                'end_date' => '2026-03-25 23:37:46',
                'created_at' => '2025-09-25 23:37:46',
                'updated_at' => '2025-09-25 23:37:46',
            ],
            [
                'id' => 2,
                'name' => 'Speed World',
                'description' => 'High-speed world with accelerated gameplay',
                'is_active' => 1,
                'max_players' => 1000,
                'map_size' => 400,
                'speed' => 3,
                'has_plus' => 0,
                'has_artifacts' => 0,
                'start_date' => NULL,
                'end_date' => NULL,
                'created_at' => '2025-09-25 23:37:46',
                'updated_at' => '2025-09-25 23:37:46',
            ],
            [
                'id' => 3,
                'name' => 'Classic World',
                'description' => 'Classic Travian experience',
                'is_active' => 1,
                'max_players' => 1000,
                'map_size' => 400,
                'speed' => 1,
                'has_plus' => 0,
                'has_artifacts' => 0,
                'start_date' => NULL,
                'end_date' => NULL,
                'created_at' => '2025-09-25 23:37:46',
                'updated_at' => '2025-09-25 23:37:46',
            ]
        ];
        
        // Use updateOrInsert to avoid duplicate key errors
        foreach ($dataTables as $data) {
            DB::table("worlds")->updateOrInsert(
                ['id' => $data['id']],
                $data
            );
        }
    }
}
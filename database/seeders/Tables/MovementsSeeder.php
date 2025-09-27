<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovementsSeeder extends Seeder
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
                'reference_number' => 'MOV-2025090001',
                'player_id' => 1,
                'from_village_id' => 1,
                'to_village_id' => 2,
                'type' => 'attack',
                'troops' => NULL,
                'resources' => NULL,
                'started_at' => '2025-09-27 00:45:02',
                'arrives_at' => '2025-09-27 01:15:03',
                'returned_at' => NULL,
                'status' => 'travelling',
                'metadata' => NULL,
                'created_at' => '2025-09-27 00:45:03',
                'updated_at' => '2025-09-27 00:45:03',
            ],
            [
                'id' => 2,
                'reference_number' => 'MOV-2025090002',
                'player_id' => 1,
                'from_village_id' => 1,
                'to_village_id' => 2,
                'type' => 'attack',
                'troops' => NULL,
                'resources' => NULL,
                'started_at' => '2025-09-27 00:45:43',
                'arrives_at' => '2025-09-27 01:15:43',
                'returned_at' => NULL,
                'status' => 'travelling',
                'metadata' => NULL,
                'created_at' => '2025-09-27 00:45:43',
                'updated_at' => '2025-09-27 00:45:43',
            ]
        ];
        
        foreach ($dataTables as $data) {
            DB::table("movements")->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}
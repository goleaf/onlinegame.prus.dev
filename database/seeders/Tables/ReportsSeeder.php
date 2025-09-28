<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportsSeeder extends Seeder
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
         */
        $dataTables = [
            [
                'id' => 1,

                'world_id' => 1,
                'attacker_id' => 7,
                'defender_id' => 48,
                'from_village_id' => 1,
                'to_village_id' => 2,
                'title' => 'Test Report',
                'content' => 'Test content',
                'type' => 'attack',
                'status' => 'pending',
                'battle_data' => null,
                'attachments' => null,
                'is_read' => 0,
                'is_important' => 0,
                'read_at' => null,
                'created_at' => '2025-09-27 00:49:11',
                'updated_at' => '2025-09-27 00:49:11',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('reports')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

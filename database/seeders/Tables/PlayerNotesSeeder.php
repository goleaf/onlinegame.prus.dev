<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerNotesSeeder extends Seeder
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

                'player_id' => 7,
                'target_player_id' => 2,
                'title' => 'Test Note',
                'content' => 'Test note content',
                'color' => 'blue',
                'is_public' => 0,
                'created_at' => '2025-09-27 01:06:42',
                'updated_at' => '2025-09-27 01:06:42',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('player_notes')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

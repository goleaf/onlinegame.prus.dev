<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BattlesSeeder extends Seeder
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
        // Get valid player IDs
        $validPlayerIds = [7, 48, 2];

        $dataTables = [
            [
                'id' => 1,
                'reference_number' => 'BTL-2025090003',
                'attacker_id' => $validPlayerIds[0], // Use first valid player ID
                'defender_id' => $validPlayerIds[1], // Use second valid player ID
                'village_id' => 1,
                'attacker_troops' => '{"spear":100}',
                'defender_troops' => '{"sword":50}',
                'attacker_losses' => null,
                'defender_losses' => null,
                'loot' => null,
                'result' => 'attacker_wins',
                'occurred_at' => '2025-09-27 00:47:44',
                'created_at' => '2025-09-27 00:47:45',
                'updated_at' => '2025-09-27 00:47:45',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('battles')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

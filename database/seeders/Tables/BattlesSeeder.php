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
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'reference_number' => 'BTL-2025090003',
                'attacker_id' => 1,
                'defender_id' => 2,
                'village_id' => 1,
                'attacker_troops' => '{"spear":100}',
                'defender_troops' => '{"sword":50}',
                'attacker_losses' => NULL,
                'defender_losses' => NULL,
                'loot' => NULL,
                'result' => 'attacker_wins',
                'occurred_at' => '2025-09-27 00:47:44',
                'created_at' => '2025-09-27 00:47:45',
                'updated_at' => '2025-09-27 00:47:45',
            ]
        ];
        
        DB::table("battles")->insert($dataTables);
    }
}
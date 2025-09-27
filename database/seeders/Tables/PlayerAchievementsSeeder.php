<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerAchievementsSeeder extends Seeder
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
                'reference_number' => 'PACH-2025090001',
                'player_id' => 1,
                'achievement_id' => 1,
                'unlocked_at' => '2025-09-27 01:03:25',
                'progress_data' => NULL,
                'created_at' => '2025-09-27 01:03:25',
                'updated_at' => '2025-09-27 01:03:25',
            ]
        ];
        
        DB::table("player_achievements")->insert($dataTables);
    }
}
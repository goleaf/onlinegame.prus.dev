<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementsSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=buildings,building_types,unit_types,achievements,quests
         *
         */

        $dataTables = [
            
        ];
        
        // Use updateOrInsert to avoid duplicate key errors
        foreach ($dataTables as $data) {
            DB::table("achievements")->updateOrInsert(
                ['id' => $data['id']],
                $data
            );
        }
    }
}
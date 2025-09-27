<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingsSeeder extends Seeder
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
        
        DB::table("buildings")->insert($dataTables);
    }
}
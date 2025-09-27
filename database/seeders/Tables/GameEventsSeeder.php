<?php
namespace ;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameEvents extends Seeder
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
         * artisan seed:generate --table-mode --tables=game_events
         *
         */

        $dataTables = [
            
        ];
        
        DB::table("game_events")->insert($dataTables);
    }
}
<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayersSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=players --order-by=points,desc --limit=3 --fields=id,name,tribe,points
         *
         */

        $dataTables = [
            [
                'id' => 2,
                'name' => 'jeanette.welch',
                'tribe' => 'gaul',
                'points' => 96904,
            ],
            [
                'id' => 48,
                'name' => 'douglas.cleta',
                'tribe' => 'natars',
                'points' => 96335,
            ],
            [
                'id' => 7,
                'name' => 'gaylord.ryann',
                'tribe' => 'gaul',
                'points' => 92676,
            ]
        ];
        
        DB::table("players")->insert($dataTables);
    }
}
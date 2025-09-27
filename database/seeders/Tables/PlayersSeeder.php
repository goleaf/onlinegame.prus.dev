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
         * artisan seed:generate --table-mode --tables=players --limit=5 --fields=name,tribe,points
         *
         */

        $dataTables = [
            [
                'name' => 'Admin',
                'tribe' => 'roman',
                'points' => 0,
            ],
            [
                'name' => 'jeanette.welch',
                'tribe' => 'gaul',
                'points' => 96904,
            ],
            [
                'name' => 'bhuels',
                'tribe' => 'gaul',
                'points' => 84504,
            ],
            [
                'name' => 'gturcotte',
                'tribe' => 'teuton',
                'points' => 84335,
            ],
            [
                'name' => 'swift.joey',
                'tribe' => 'teuton',
                'points' => 12620,
            ]
        ];
        
        DB::table("players")->insert($dataTables);
    }
}
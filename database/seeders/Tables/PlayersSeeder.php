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
         * artisan seed:generate --table-mode --tables=players --ids=1,2,3,4,5
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'user_id' => 1,
                'world_id' => 1,
                'name' => 'Admin',
                'tribe' => 'roman',
                'points' => 0,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 0,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-09-25 23:37:48',
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'world_id' => 1,
                'name' => 'jeanette.welch',
                'tribe' => 'gaul',
                'points' => 96904,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 3184,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-09-23 00:04:55',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'world_id' => 1,
                'name' => 'bhuels',
                'tribe' => 'gaul',
                'points' => 84504,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 7797,
                'villages_count' => 2,
                'is_active' => 1,
                'last_login' => '2025-09-14 17:46:55',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 4,
                'user_id' => 4,
                'world_id' => 1,
                'name' => 'gturcotte',
                'tribe' => 'teuton',
                'points' => 84335,
                'is_online' => 1,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 1721,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-09-25 16:58:39',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 5,
                'user_id' => 5,
                'world_id' => 1,
                'name' => 'swift.joey',
                'tribe' => 'teuton',
                'points' => 12620,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 325,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-08-31 20:20:27',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ]
        ];
        
        DB::table("players")->insert($dataTables);
    }
}
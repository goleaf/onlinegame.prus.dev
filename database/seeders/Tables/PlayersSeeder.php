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
         * artisan seed:generate --table-mode --tables=players --where=tribe,=,roman --limit=5
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
                'id' => 6,
                'user_id' => 6,
                'world_id' => 1,
                'name' => 'abaumbach',
                'tribe' => 'roman',
                'points' => 17808,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 1696,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-08-28 07:56:36',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 8,
                'user_id' => 8,
                'world_id' => 1,
                'name' => 'harrison69',
                'tribe' => 'roman',
                'points' => 45119,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 6428,
                'villages_count' => 3,
                'is_active' => 1,
                'last_login' => '2025-09-07 04:41:03',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 10,
                'user_id' => 10,
                'world_id' => 1,
                'name' => 'columbus48',
                'tribe' => 'roman',
                'points' => 36194,
                'is_online' => 1,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 12195,
                'villages_count' => 4,
                'is_active' => 1,
                'last_login' => '2025-09-11 03:08:43',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ],
            [
                'id' => 25,
                'user_id' => 25,
                'world_id' => 1,
                'name' => 'ardella83',
                'tribe' => 'roman',
                'points' => 50750,
                'is_online' => 1,
                'last_active_at' => '2025-09-27 00:51:04',
                'population' => 522,
                'villages_count' => 1,
                'is_active' => 1,
                'last_login' => '2025-09-03 07:35:52',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:04',
            ]
        ];
        
        DB::table("players")->insert($dataTables);
    }
}
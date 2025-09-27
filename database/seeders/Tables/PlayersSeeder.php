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
         * artisan seed:generate --table-mode --tables=players --order-by=points,desc --limit=3
         *
         */

        $dataTables = [
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
                'id' => 48,
                'user_id' => 48,
                'world_id' => 1,
                'name' => 'douglas.cleta',
                'tribe' => 'natars',
                'points' => 96335,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:04',
                'population' => 7277,
                'villages_count' => 3,
                'is_active' => 1,
                'last_login' => '2025-08-29 03:21:07',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:04',
            ],
            [
                'id' => 7,
                'user_id' => 7,
                'world_id' => 1,
                'name' => 'gaylord.ryann',
                'tribe' => 'gaul',
                'points' => 92676,
                'is_online' => 0,
                'last_active_at' => '2025-09-27 00:51:03',
                'population' => 6456,
                'villages_count' => 2,
                'is_active' => 1,
                'last_login' => '2025-09-02 23:32:14',
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:51:03',
            ]
        ];
        
        DB::table("players")->insert($dataTables);
    }
}
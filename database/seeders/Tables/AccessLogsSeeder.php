<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessLogsSeeder extends Seeder
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
                'ip' => '127.0.0.1',
                'method' => 'HEAD',
                'url' => 'http://localhost:8001',
                'user_agent' => 'curl/7.76.1',
                'request_data' => NULL,
                'created_at' => '2025-09-27 01:19:32',
                'updated_at' => '2025-09-27 01:19:32',
            ]
        ];
        
        foreach ($dataTables as $data) {
            DB::table("access_logs")->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}
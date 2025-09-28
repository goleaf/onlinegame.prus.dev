<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
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
         */
        $dataTables = [

        ];

        foreach ($dataTables as $data) {
            DB::table('activity_log')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

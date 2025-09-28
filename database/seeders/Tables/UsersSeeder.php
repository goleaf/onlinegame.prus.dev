<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=users --limit=2 --fields=id,name,email
         */
        $dataTables = [
            [
                'id' => 1,
                'name' => 'Test Audit Update - 1758933575',
                'email' => 'admin@example.com',
            ],
            [
                'id' => 2,
                'name' => 'Darion Cruickshank',
                'email' => 'grimes.oren@example.com',
            ],
        ];

        DB::table('users')->insert($dataTables);
    }
}

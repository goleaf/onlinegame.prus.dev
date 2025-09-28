<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelReferenceCountersSeeder extends Seeder
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
            [
                'id' => 1,
                'key' => 'App\\Models\\Game\\Movement',
                'value' => 3,
                'created_at' => '2025-09-27 00:45:03',
                'updated_at' => '2025-09-27 00:45:43',
            ],
            [
                'id' => 2,
                'key' => 'App\\Models\\Game\\Battle',
                'value' => 4,
                'created_at' => '2025-09-27 00:45:43',
                'updated_at' => '2025-09-27 00:47:45',
            ],
            [
                'id' => 3,
                'key' => 'App\\Models\\Game\\Report',
                'value' => 4,
                'created_at' => '2025-09-27 00:48:09',
                'updated_at' => '2025-09-27 00:49:11',
            ],
            [
                'id' => 4,
                'key' => 'App\\Models\\Game\\Achievement',
                'value' => 2,
                'created_at' => '2025-09-27 01:02:42',
                'updated_at' => '2025-09-27 01:02:42',
            ],
            [
                'id' => 5,
                'key' => 'App\\Models\\Game\\Alliance',
                'value' => 3,
                'created_at' => '2025-09-27 01:02:42',
                'updated_at' => '2025-09-27 01:03:13',
            ],
            [
                'id' => 6,
                'key' => 'App\\Models\\Game\\PlayerAchievement',
                'value' => 2,
                'created_at' => '2025-09-27 01:03:25',
                'updated_at' => '2025-09-27 01:03:26',
            ],
            [
                'id' => 7,
                'key' => 'App\\Models\\Game\\AllianceMember',
                'value' => 4,
                'created_at' => '2025-09-27 01:03:26',
                'updated_at' => '2025-09-27 01:04:33',
            ],
            [
                'id' => 8,
                'key' => 'App\\Models\\Game\\PlayerQuest',
                'value' => 4,
                'created_at' => '2025-09-27 01:04:50',
                'updated_at' => '2025-09-27 01:05:27',
            ],
            [
                'id' => 9,
                'key' => 'App\\Models\\Game\\PlayerNote',
                'value' => 3,
                'created_at' => '2025-09-27 01:05:40',
                'updated_at' => '2025-09-27 01:06:42',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('model_reference_counters')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotablesSeeder extends Seeder
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
                'note' => 'Test note for user',
                'notable_type' => 'App\\Models\\User',
                'notable_id' => 1,
                'creator_type' => 'App\\Models\\User',
                'creator_id' => 1,
                'created_at' => '2025-09-27 00:38:32',
                'updated_at' => '2025-09-27 00:38:32',
            ],
            [
                'id' => 2,
                'note' => 'Test note for player: Admin',
                'notable_type' => 'App\\Models\\Game\\Player',
                'notable_id' => 1,
                'creator_type' => 'App\\Models\\Game\\Player',
                'creator_id' => 1,
                'created_at' => '2025-09-27 00:38:45',
                'updated_at' => '2025-09-27 00:38:45',
            ],
            [
                'id' => 3,
                'note' => 'Test note for village: Admin Capital',
                'notable_type' => 'App\\Models\\Game\\Village',
                'notable_id' => 1,
                'creator_type' => 'App\\Models\\Game\\Village',
                'creator_id' => 1,
                'created_at' => '2025-09-27 00:38:57',
                'updated_at' => '2025-09-27 00:38:57',
            ],
            [
                'id' => 4,
                'note' => 'Test note for world: Travian World 1',
                'notable_type' => 'App\\Models\\Game\\World',
                'notable_id' => 1,
                'creator_type' => 'App\\Models\\Game\\World',
                'creator_id' => 1,
                'created_at' => '2025-09-27 00:39:11',
                'updated_at' => '2025-09-27 00:39:11',
            ],
            [
                'id' => 5,
                'note' => 'Test note for quest: Welcome to Travian',
                'notable_type' => 'App\\Models\\Game\\Quest',
                'notable_id' => 1,
                'creator_type' => 'App\\Models\\Game\\Quest',
                'creator_id' => 1,
                'created_at' => '2025-09-27 00:39:21',
                'updated_at' => '2025-09-27 00:39:21',
            ]
        ];
        
        foreach ($dataTables as $data) {
            DB::table("notables")->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}
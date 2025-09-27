<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingTypesSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=buildings,building_types,unit_types,achievements,quests
         *
         */

        $dataTables = [
            [
                'id' => 13,
                'name' => 'repellat iure',
                'key' => 'rerum-in-molestiae',
                'description' => 'Tenetur sapiente alias similique quaerat quasi.',
                'max_level' => 5,
                'requirements' => '"[]"',
                'costs' => '"{\\"wood\\":274,\\"clay\\":374,\\"iron\\":183,\\"crop\\":405}"',
                'production' => '"[]"',
                'population' => '"[]"',
                'is_special' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:46:17',
                'updated_at' => '2025-09-25 23:46:17',
            ]
        ];
        
        // Use updateOrInsert to avoid duplicate key errors
        foreach ($dataTables as $data) {
            DB::table("building_types")->updateOrInsert(
                ['id' => $data['id']],
                $data
            );
        }
    }
}
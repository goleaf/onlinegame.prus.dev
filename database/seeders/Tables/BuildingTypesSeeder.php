<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;

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
         * artisan seed:generate --table-mode --all-tables
         */
        $dataTables = [
            // Only keep unique building types that are not in BuildingTypeSeeder
            [
                'name' => 'repellat iure',
                'key' => 'rerum-in-molestiae',
                'description' => 'Tenetur sapiente alias similique quaerat quasi.',
                'max_level' => 5,
                'requirements' => '"[]"',
                'costs' => '"{\"wood\":274,\"clay\":374,\"iron\":183,\"crop\":405}"',
                'production' => '"[]"',
                'population' => '"[]"',
                'is_special' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:46:17',
                'updated_at' => '2025-09-25 23:46:17',
            ],
        ];

        foreach ($dataTables as $data) {
            // Use firstOrCreate to avoid unique constraint violations on the key field
            \App\Models\Game\BuildingType::firstOrCreate(
                ['key' => $data['key']],
                $data
            );
        }
    }
}

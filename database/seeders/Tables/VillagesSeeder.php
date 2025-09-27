<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VillagesSeeder extends Seeder
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
         * artisan seed:generate --table-mode --tables=villages --limit=5
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'player_id' => 1,
                'world_id' => 1,
                'name' => 'Admin Capital',
                'x_coordinate' => 0,
                'y_coordinate' => 0,
                'latitude' => 50,
                'longitude' => 8,
                'geohash' => 'u0vsqn1r',
                'elevation' => NULL,
                'geographic_metadata' => NULL,
                'population' => 0,
                'culture_points' => 0,
                'is_capital' => 1,
                'is_active' => 1,
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
                'wood_capacity' => 10000,
                'clay_capacity' => 10000,
                'iron_capacity' => 10000,
                'crop_capacity' => 10000,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-27 00:29:54',
            ],
            [
                'id' => 2,
                'player_id' => 2,
                'world_id' => 1,
                'name' => 'Joaniestad Village',
                'x_coordinate' => 7,
                'y_coordinate' => 354,
                'latitude' => '50.35400000',
                'longitude' => '8.00700000',
                'geohash' => 'u0vwqnwt',
                'elevation' => NULL,
                'geographic_metadata' => NULL,
                'population' => 3184,
                'culture_points' => 0,
                'is_capital' => 1,
                'is_active' => 1,
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
                'wood_capacity' => 1000,
                'clay_capacity' => 1000,
                'iron_capacity' => 1000,
                'crop_capacity' => 1000,
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:29:54',
            ],
            [
                'id' => 3,
                'player_id' => 3,
                'world_id' => 1,
                'name' => 'Kulasberg Village',
                'x_coordinate' => 115,
                'y_coordinate' => 158,
                'latitude' => '50.15800000',
                'longitude' => '8.11500000',
                'geohash' => 'u0vv2dvp',
                'elevation' => NULL,
                'geographic_metadata' => NULL,
                'population' => 3496,
                'culture_points' => 0,
                'is_capital' => 1,
                'is_active' => 1,
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
                'wood_capacity' => 1000,
                'clay_capacity' => 1000,
                'iron_capacity' => 1000,
                'crop_capacity' => 1000,
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:29:54',
            ],
            [
                'id' => 4,
                'player_id' => 3,
                'world_id' => 1,
                'name' => 'Parisview Village',
                'x_coordinate' => 71,
                'y_coordinate' => 261,
                'latitude' => '50.26100000',
                'longitude' => '8.07100000',
                'geohash' => 'u0vtzttp',
                'elevation' => NULL,
                'geographic_metadata' => NULL,
                'population' => 4301,
                'culture_points' => 0,
                'is_capital' => 0,
                'is_active' => 1,
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
                'wood_capacity' => 1000,
                'clay_capacity' => 1000,
                'iron_capacity' => 1000,
                'crop_capacity' => 1000,
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:29:54',
            ],
            [
                'id' => 5,
                'player_id' => 4,
                'world_id' => 1,
                'name' => 'Vaughnfort Village',
                'x_coordinate' => 268,
                'y_coordinate' => 172,
                'latitude' => '50.17200000',
                'longitude' => '8.26800000',
                'geohash' => 'u0vvkjs9',
                'elevation' => NULL,
                'geographic_metadata' => NULL,
                'population' => 1721,
                'culture_points' => 0,
                'is_capital' => 1,
                'is_active' => 1,
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
                'wood_capacity' => 1000,
                'clay_capacity' => 1000,
                'iron_capacity' => 1000,
                'crop_capacity' => 1000,
                'created_at' => '2025-09-25 23:37:49',
                'updated_at' => '2025-09-27 00:29:54',
            ]
        ];
        
        DB::table("villages")->insert($dataTables);
    }
}
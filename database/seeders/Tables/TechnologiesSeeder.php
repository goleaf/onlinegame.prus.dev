<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnologiesSeeder extends Seeder
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
                'name' => 'Spy',
                'key' => 'spy',
                'description' => 'Allows training of spy units',
                'requirements' => '"{\\"academy\\":1}"',
                'costs' => '{"wood":200,"clay":200,"iron":200,"crop":200}',
                'effects' => '"{\\"unlock_unit\\":\\"spy\\"}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 2,
                'name' => 'Horseback Riding',
                'key' => 'horseback_riding',
                'description' => 'Allows training of cavalry units',
                'requirements' => '"{\\"academy\\":5,\\"stable\\":1}"',
                'costs' => '{"wood":400,"clay":400,"iron":400,"crop":400}',
                'effects' => '"{\\"unlock_unit\\":\\"cavalry\\"}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 3,
                'name' => 'Iron Casting',
                'key' => 'iron_casting',
                'description' => 'Improves weapon strength',
                'requirements' => '"{\\"academy\\":3}"',
                'costs' => '{"wood":300,"clay":300,"iron":300,"crop":300}',
                'effects' => '"{\\"attack_bonus\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 4,
                'name' => 'Armor',
                'key' => 'armor',
                'description' => 'Improves defensive strength',
                'requirements' => '"{\\"academy\\":3}"',
                'costs' => '{"wood":300,"clay":300,"iron":300,"crop":300}',
                'effects' => '"{\\"defense_bonus\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 5,
                'name' => 'Compass',
                'key' => 'compass',
                'description' => 'Increases movement speed',
                'requirements' => '"{\\"academy\\":5}"',
                'costs' => '{"wood":500,"clay":500,"iron":500,"crop":500}',
                'effects' => '"{\\"speed_bonus\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 6,
                'name' => 'Architecture',
                'key' => 'architecture',
                'description' => 'Reduces construction time',
                'requirements' => '"{\\"academy\\":3}"',
                'costs' => '{"wood":300,"clay":300,"iron":300,"crop":300}',
                'effects' => '"{\\"construction_speed\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 7,
                'name' => 'Trade',
                'key' => 'trade',
                'description' => 'Allows trading with other players',
                'requirements' => '"{\\"academy\\":3,\\"market\\":1}"',
                'costs' => '{"wood":300,"clay":300,"iron":300,"crop":300}',
                'effects' => '"{\\"unlock_trading\\":true}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 8,
                'name' => 'Research',
                'key' => 'research',
                'description' => 'Reduces research time',
                'requirements' => '"{\\"academy\\":5}"',
                'costs' => '{"wood":500,"clay":500,"iron":500,"crop":500}',
                'effects' => '"{\\"research_speed\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
            [
                'id' => 9,
                'name' => 'Training',
                'key' => 'training',
                'description' => 'Reduces training time',
                'requirements' => '"{\\"academy\\":5}"',
                'costs' => '{"wood":500,"clay":500,"iron":500,"crop":500}',
                'effects' => '"{\\"training_speed\\":0.1}"',
                'is_active' => 1,
                'created_at' => '2025-09-27 00:42:29',
                'updated_at' => '2025-09-27 00:42:29',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('technologies')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

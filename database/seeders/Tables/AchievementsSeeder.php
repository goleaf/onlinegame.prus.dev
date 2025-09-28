<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementsSeeder extends Seeder
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
                'reference_number' => null,
                'name' => 'First Steps',
                'key' => 'first_steps',
                'description' => 'Complete your first quest.',
                'category' => 'milestone',
                'points' => 10,
                'requirements' => '"{\\"complete_quest\\":1}"',
                'rewards' => '"{\\"wood\\":1000,\\"clay\\":1000,\\"iron\\":1000,\\"crop\\":1000}"',
                'icon' => 'achievements/first_steps.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 2,
                'reference_number' => null,
                'name' => 'Builder',
                'key' => 'builder',
                'description' => 'Build your first building.',
                'category' => 'building',
                'points' => 20,
                'requirements' => '"{\\"build_building\\":1}"',
                'rewards' => '"{\\"wood\\":500,\\"clay\\":500,\\"iron\\":500,\\"crop\\":500}"',
                'icon' => 'achievements/builder.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 3,
                'reference_number' => null,
                'name' => 'Resource Collector',
                'key' => 'resource_collector',
                'description' => 'Collect 100,000 resources in total.',
                'category' => 'milestone',
                'points' => 30,
                'requirements' => '"{\\"collect_resources\\":100000}"',
                'rewards' => '"{\\"wood\\":2000,\\"clay\\":2000,\\"iron\\":2000,\\"crop\\":2000}"',
                'icon' => 'achievements/resource_collector.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 4,
                'reference_number' => null,
                'name' => 'Warrior',
                'key' => 'warrior',
                'description' => 'Win your first battle.',
                'category' => 'combat',
                'points' => 50,
                'requirements' => '"{\\"win_battle\\":1}"',
                'rewards' => '"{\\"wood\\":1000,\\"clay\\":1000,\\"iron\\":1000,\\"crop\\":1000}"',
                'icon' => 'achievements/warrior.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 5,
                'reference_number' => null,
                'name' => 'Explorer',
                'key' => 'explorer',
                'description' => 'Explore 10 different villages.',
                'category' => 'exploration',
                'points' => 40,
                'requirements' => '"{\\"explore_villages\\":10}"',
                'rewards' => '"{\\"wood\\":500,\\"clay\\":500,\\"iron\\":500,\\"crop\\":500}"',
                'icon' => 'achievements/explorer.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 6,
                'reference_number' => null,
                'name' => 'Trader',
                'key' => 'trader',
                'description' => 'Complete 5 successful trades.',
                'category' => 'trade',
                'points' => 35,
                'requirements' => '"{\\"complete_trades\\":5}"',
                'rewards' => '"{\\"wood\\":1500,\\"clay\\":1500,\\"iron\\":1500,\\"crop\\":1500}"',
                'icon' => 'achievements/trader.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 7,
                'reference_number' => null,
                'name' => 'Alliance Member',
                'key' => 'alliance_member',
                'description' => 'Join an alliance.',
                'category' => 'alliance',
                'points' => 25,
                'requirements' => '"{\\"join_alliance\\":1}"',
                'rewards' => '"{\\"wood\\":1000,\\"clay\\":1000,\\"iron\\":1000,\\"crop\\":1000}"',
                'icon' => 'achievements/alliance_member.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 8,
                'reference_number' => null,
                'name' => 'Master Builder',
                'key' => 'master_builder',
                'description' => 'Build 50 buildings.',
                'category' => 'building',
                'points' => 100,
                'requirements' => '"{\\"build_building\\":50}"',
                'rewards' => '"{\\"wood\\":5000,\\"clay\\":5000,\\"iron\\":5000,\\"crop\\":5000}"',
                'icon' => 'achievements/master_builder.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 9,
                'reference_number' => null,
                'name' => 'Legend',
                'key' => 'legend',
                'description' => 'Reach 1,000,000 points.',
                'category' => 'milestone',
                'points' => 200,
                'requirements' => '"{\\"reach_points\\":1000000}"',
                'rewards' => '"{\\"wood\\":100000,\\"clay\\":100000,\\"iron\\":100000,\\"crop\\":100000}"',
                'icon' => 'achievements/legend.png',
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 00:48:21',
                'updated_at' => '2025-09-27 00:48:21',
            ],
            [
                'id' => 10,

                'name' => 'Test Achievement',
                'key' => 'test_achievement',
                'description' => 'Test achievement description',
                'category' => 'building',
                'points' => 100,
                'requirements' => null,
                'rewards' => null,
                'icon' => null,
                'is_hidden' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 01:02:42',
                'updated_at' => '2025-09-27 01:02:42',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('achievements')->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}

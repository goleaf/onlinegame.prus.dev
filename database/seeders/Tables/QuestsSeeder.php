<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestsSeeder extends Seeder
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
                'id' => 1,
                'name' => 'Welcome to Travian',
                'key' => 'welcome',
                'description' => 'Welcome to the world of Travian! Complete this quest to get started.',
                'instructions' => NULL,
                'category' => 'tutorial',
                'difficulty' => 1,
                'requirements' => '"[]"',
                'rewards' => '"{\\"resources\\":{\\"wood\\":1000,\\"clay\\":1000,\\"iron\\":1000,\\"crop\\":1000}}"',
                'experience_reward' => 0,
                'gold_reward' => 0,
                'resource_rewards' => NULL,
                'is_repeatable' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-25 23:37:48',
            ],
            [
                'id' => 2,
                'name' => 'First Building',
                'key' => 'first_building',
                'description' => 'Build your first building to expand your village.',
                'instructions' => NULL,
                'category' => 'building',
                'difficulty' => 1,
                'requirements' => '"{\\"buildings\\":{\\"main_building\\":1}}"',
                'rewards' => '"{\\"resources\\":{\\"wood\\":500,\\"clay\\":500,\\"iron\\":500,\\"crop\\":500}}"',
                'experience_reward' => 0,
                'gold_reward' => 0,
                'resource_rewards' => NULL,
                'is_repeatable' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-25 23:37:48',
            ],
            [
                'id' => 3,
                'name' => 'Resource Production',
                'key' => 'resource_production',
                'description' => 'Upgrade your resource buildings to increase production.',
                'instructions' => NULL,
                'category' => 'building',
                'difficulty' => 2,
                'requirements' => '"{\\"buildings\\":{\\"woodcutter\\":5,\\"clay_pit\\":5,\\"iron_mine\\":5,\\"crop_field\\":5}}"',
                'rewards' => '"{\\"resources\\":{\\"wood\\":2000,\\"clay\\":2000,\\"iron\\":2000,\\"crop\\":2000}}"',
                'experience_reward' => 0,
                'gold_reward' => 0,
                'resource_rewards' => NULL,
                'is_repeatable' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-25 23:37:48',
            ],
            [
                'id' => 4,
                'name' => 'Military Training',
                'key' => 'military_training',
                'description' => 'Train your first military units.',
                'instructions' => NULL,
                'category' => 'combat',
                'difficulty' => 2,
                'requirements' => '"{\\"buildings\\":{\\"barracks\\":3},\\"units\\":{\\"legionnaire\\":10}}"',
                'rewards' => '"{\\"resources\\":{\\"wood\\":1000,\\"clay\\":1000,\\"iron\\":1000,\\"crop\\":1000}}"',
                'experience_reward' => 0,
                'gold_reward' => 0,
                'resource_rewards' => NULL,
                'is_repeatable' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-25 23:37:48',
            ],
            [
                'id' => 5,
                'name' => 'Village Expansion',
                'key' => 'village_expansion',
                'description' => 'Expand your village by building more structures.',
                'instructions' => NULL,
                'category' => 'building',
                'difficulty' => 3,
                'requirements' => '"{\\"buildings\\":{\\"main_building\\":10,\\"warehouse\\":5,\\"granary\\":5}}"',
                'rewards' => '"{\\"resources\\":{\\"wood\\":5000,\\"clay\\":5000,\\"iron\\":5000,\\"crop\\":5000}}"',
                'experience_reward' => 0,
                'gold_reward' => 0,
                'resource_rewards' => NULL,
                'is_repeatable' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:48',
                'updated_at' => '2025-09-25 23:37:48',
            ]
        ];
        
        // Use updateOrInsert to avoid duplicate key errors
        foreach ($dataTables as $data) {
            DB::table("quests")->updateOrInsert(
                ['id' => $data['id']],
                $data
            );
        }
    }
}
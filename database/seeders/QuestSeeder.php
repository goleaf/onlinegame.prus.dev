<?php

namespace Database\Seeders;

use App\Models\Game\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quests = [
            [
                'name' => 'Welcome to Travian',
                'key' => 'welcome',
                'description' => 'Welcome to the world of Travian! Complete this quest to get started.',
                'category' => 'tutorial',
                'difficulty' => 1,
                'requirements' => json_encode(['build_first_building' => true]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'experience_reward' => 100,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'First Building',
                'key' => 'first_building',
                'description' => 'Build your first building to establish your village.',
                'category' => 'building',
                'difficulty' => 1,
                'requirements' => json_encode(['build_building' => 1]),
                'rewards' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'experience_reward' => 50,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Resource Storage',
                'key' => 'resource_storage',
                'description' => 'Build storage buildings to increase your resource capacity.',
                'category' => 'building',
                'difficulty' => 1,
                'requirements' => json_encode(['build_warehouse' => 1, 'build_granary' => 1]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'experience_reward' => 75,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Military Training',
                'key' => 'military_training',
                'description' => 'Build your first military building and train troops.',
                'category' => 'building',
                'difficulty' => 2,
                'requirements' => json_encode(['build_barracks' => 1, 'train_first_unit' => true]),
                'rewards' => json_encode(['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]),
                'experience_reward' => 150,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Research and Development',
                'key' => 'research_development',
                'description' => 'Learn about research and technology.',
                'category' => 'building',
                'difficulty' => 2,
                'requirements' => json_encode(['build_academy' => 1, 'research_first_tech' => true]),
                'rewards' => json_encode(['wood' => 3000, 'clay' => 3000, 'iron' => 3000, 'crop' => 3000]),
                'experience_reward' => 200,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 3000, 'clay' => 3000, 'iron' => 3000, 'crop' => 3000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'First Battle',
                'key' => 'first_battle',
                'description' => 'Launch your first attack on another village.',
                'category' => 'combat',
                'difficulty' => 3,
                'requirements' => json_encode(['launch_attack' => 1]),
                'rewards' => json_encode(['wood' => 5000, 'clay' => 5000, 'iron' => 5000, 'crop' => 5000]),
                'experience_reward' => 300,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 5000, 'clay' => 5000, 'iron' => 5000, 'crop' => 5000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Exploration',
                'key' => 'exploration',
                'description' => 'Explore the world map and discover new villages.',
                'category' => 'exploration',
                'difficulty' => 2,
                'requirements' => json_encode(['explore_villages' => 5]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'experience_reward' => 100,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Trade Master',
                'key' => 'trade_master',
                'description' => 'Complete your first trade on the market.',
                'category' => 'trade',
                'difficulty' => 2,
                'requirements' => json_encode(['complete_trade' => 1]),
                'rewards' => json_encode(['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]),
                'experience_reward' => 150,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Alliance Builder',
                'key' => 'alliance_builder',
                'description' => 'Join or create an alliance.',
                'category' => 'alliance',
                'difficulty' => 3,
                'requirements' => json_encode(['join_alliance' => 1]),
                'rewards' => json_encode(['wood' => 3000, 'clay' => 3000, 'iron' => 3000, 'crop' => 3000]),
                'experience_reward' => 250,
                'gold_reward' => 0,
                'resource_rewards' => json_encode(['wood' => 3000, 'clay' => 3000, 'iron' => 3000, 'crop' => 3000]),
                'is_repeatable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Special Mission',
                'key' => 'special_mission',
                'description' => 'Complete a special mission for extra rewards.',
                'category' => 'special',
                'difficulty' => 4,
                'requirements' => json_encode(['complete_special_task' => 1]),
                'rewards' => json_encode(['wood' => 10000, 'clay' => 10000, 'iron' => 10000, 'crop' => 10000]),
                'experience_reward' => 500,
                'gold_reward' => 100,
                'resource_rewards' => json_encode(['wood' => 10000, 'clay' => 10000, 'iron' => 10000, 'crop' => 10000]),
                'is_repeatable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($quests as $quest) {
            Quest::firstOrCreate(
                ['key' => $quest['key']],
                array_merge($quest, ['reference_number' => uniqid()])
            );
        }
    }
}

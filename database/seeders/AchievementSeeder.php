<?php

namespace Database\Seeders;

use App\Models\Game\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Steps',
                'key' => 'first_steps',
                'description' => 'Complete your first quest.',
                'category' => 'milestone',
                'points' => 10,
                'requirements' => json_encode(['complete_quest' => 1]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'icon' => 'achievements/first_steps.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Builder',
                'key' => 'builder',
                'description' => 'Build your first building.',
                'category' => 'building',
                'points' => 20,
                'requirements' => json_encode(['build_building' => 1]),
                'rewards' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'icon' => 'achievements/builder.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Resource Collector',
                'key' => 'resource_collector',
                'description' => 'Collect 100,000 resources in total.',
                'category' => 'milestone',
                'points' => 30,
                'requirements' => json_encode(['collect_resources' => 100000]),
                'rewards' => json_encode(['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]),
                'icon' => 'achievements/resource_collector.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Warrior',
                'key' => 'warrior',
                'description' => 'Win your first battle.',
                'category' => 'combat',
                'points' => 50,
                'requirements' => json_encode(['win_battle' => 1]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'icon' => 'achievements/warrior.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Explorer',
                'key' => 'explorer',
                'description' => 'Explore 10 different villages.',
                'category' => 'exploration',
                'points' => 40,
                'requirements' => json_encode(['explore_villages' => 10]),
                'rewards' => json_encode(['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]),
                'icon' => 'achievements/explorer.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Trader',
                'key' => 'trader',
                'description' => 'Complete 5 successful trades.',
                'category' => 'trade',
                'points' => 35,
                'requirements' => json_encode(['complete_trades' => 5]),
                'rewards' => json_encode(['wood' => 1500, 'clay' => 1500, 'iron' => 1500, 'crop' => 1500]),
                'icon' => 'achievements/trader.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Alliance Member',
                'key' => 'alliance_member',
                'description' => 'Join an alliance.',
                'category' => 'alliance',
                'points' => 25,
                'requirements' => json_encode(['join_alliance' => 1]),
                'rewards' => json_encode(['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]),
                'icon' => 'achievements/alliance_member.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Master Builder',
                'key' => 'master_builder',
                'description' => 'Build 50 buildings.',
                'category' => 'building',
                'points' => 100,
                'requirements' => json_encode(['build_building' => 50]),
                'rewards' => json_encode(['wood' => 5000, 'clay' => 5000, 'iron' => 5000, 'crop' => 5000]),
                'icon' => 'achievements/master_builder.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Legend',
                'key' => 'legend',
                'description' => 'Reach 1,000,000 points.',
                'category' => 'milestone',
                'points' => 200,
                'requirements' => json_encode(['reach_points' => 1000000]),
                'rewards' => json_encode(['wood' => 100000, 'clay' => 100000, 'iron' => 100000, 'crop' => 100000]),
                'icon' => 'achievements/legend.png',
                'is_hidden' => false,
                'is_active' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['key' => $achievement['key']],
                $achievement
            );
        }
    }
}

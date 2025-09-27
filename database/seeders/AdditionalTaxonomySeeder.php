<?php

namespace Database\Seeders;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Seeder;

class AdditionalTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // Achievement Categories
        $achievementCategories = [
            'combat' => [
                'name' => 'Combat Achievements',
                'description' => 'Achievements related to combat and warfare',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 1]
            ],
            'building' => [
                'name' => 'Building Achievements',
                'description' => 'Achievements for construction and development',
                'meta' => ['icon' => 'building', 'color' => '#10b981', 'order' => 2]
            ],
            'resource' => [
                'name' => 'Resource Achievements',
                'description' => 'Achievements for resource management',
                'meta' => ['icon' => 'coins', 'color' => '#f59e0b', 'order' => 3]
            ],
            'alliance' => [
                'name' => 'Alliance Achievements',
                'description' => 'Achievements for alliance activities',
                'meta' => ['icon' => 'users', 'color' => '#3b82f6', 'order' => 4]
            ],
            'exploration' => [
                'name' => 'Exploration Achievements',
                'description' => 'Achievements for world exploration',
                'meta' => ['icon' => 'map', 'color' => '#8b5cf6', 'order' => 5]
            ],
            'special' => [
                'name' => 'Special Achievements',
                'description' => 'Unique and special achievements',
                'meta' => ['icon' => 'star', 'color' => '#f97316', 'order' => 6]
            ]
        ];

        foreach ($achievementCategories as $key => $category) {
            Taxonomy::firstOrCreate(
                ['slug' => $key, 'type' => 'achievement_category'],
                [
                    'name' => $category['name'],
                    'slug' => $key,
                    'type' => 'achievement_category',
                    'description' => $category['description'],
                    'meta' => $category['meta'],
                    'sort_order' => $category['meta']['order']
                ]
            );
        }

        // Report Types
        $reportTypes = [
            'attack' => [
                'name' => 'Attack Reports',
                'description' => 'Reports of attacks and raids',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 1]
            ],
            'defense' => [
                'name' => 'Defense Reports',
                'description' => 'Reports of defensive actions',
                'meta' => ['icon' => 'shield', 'color' => '#6b7280', 'order' => 2]
            ],
            'support' => [
                'name' => 'Support Reports',
                'description' => 'Reports of support actions',
                'meta' => ['icon' => 'handshake', 'color' => '#10b981', 'order' => 3]
            ],
            'trade' => [
                'name' => 'Trade Reports',
                'description' => 'Reports of trading activities',
                'meta' => ['icon' => 'currency-dollar', 'color' => '#f59e0b', 'order' => 4]
            ],
            'movement' => [
                'name' => 'Movement Reports',
                'description' => 'Reports of troop movements',
                'meta' => ['icon' => 'map', 'color' => '#3b82f6', 'order' => 5]
            ],
            'system' => [
                'name' => 'System Reports',
                'description' => 'System-generated reports',
                'meta' => ['icon' => 'cog', 'color' => '#8b5cf6', 'order' => 6]
            ]
        ];

        foreach ($reportTypes as $key => $type) {
            Taxonomy::firstOrCreate(
                ['slug' => $key, 'type' => 'report_type'],
                [
                    'name' => $type['name'],
                    'slug' => $key,
                    'type' => 'report_type',
                    'description' => $type['description'],
                    'meta' => $type['meta'],
                    'sort_order' => $type['meta']['order']
                ]
            );
        }

        // Battle Types
        $battleTypes = [
            'attack' => [
                'name' => 'Attack Battles',
                'description' => 'Offensive battle actions',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 1]
            ],
            'defense' => [
                'name' => 'Defense Battles',
                'description' => 'Defensive battle actions',
                'meta' => ['icon' => 'shield', 'color' => '#6b7280', 'order' => 2]
            ],
            'raid' => [
                'name' => 'Raid Battles',
                'description' => 'Quick raid attacks',
                'meta' => ['icon' => 'bolt', 'color' => '#f59e0b', 'order' => 3]
            ],
            'siege' => [
                'name' => 'Siege Battles',
                'description' => 'Extended siege operations',
                'meta' => ['icon' => 'wall', 'color' => '#8b5cf6', 'order' => 4]
            ],
            'alliance_war' => [
                'name' => 'Alliance War Battles',
                'description' => 'Battles in alliance wars',
                'meta' => ['icon' => 'users', 'color' => '#3b82f6', 'order' => 5]
            ],
            'tournament' => [
                'name' => 'Tournament Battles',
                'description' => 'Tournament battle events',
                'meta' => ['icon' => 'trophy', 'color' => '#f97316', 'order' => 6]
            ]
        ];

        foreach ($battleTypes as $key => $type) {
            Taxonomy::firstOrCreate(
                ['slug' => $key, 'type' => 'battle_type'],
                [
                    'name' => $type['name'],
                    'slug' => $key,
                    'type' => 'battle_type',
                    'description' => $type['description'],
                    'meta' => $type['meta'],
                    'sort_order' => $type['meta']['order']
                ]
            );
        }

        $this->command->info('Additional taxonomies seeded successfully!');
    }
}

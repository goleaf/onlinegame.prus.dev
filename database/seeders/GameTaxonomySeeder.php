<?php

namespace Database\Seeders;

use Aliziodev\LaravelTaxonomy\Facades\Taxonomy;
use Aliziodev\LaravelTaxonomy\Enums\TaxonomyType;
use Illuminate\Database\Seeder;

class GameTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        // Quest Categories
        $questCategories = [
            'tutorial' => [
                'name' => 'Tutorial',
                'description' => 'Tutorial and learning quests for new players',
                'meta' => ['icon' => 'book-open', 'color' => '#3b82f6', 'order' => 1]
            ],
            'building' => [
                'name' => 'Building',
                'description' => 'Quests related to building construction and upgrades',
                'meta' => ['icon' => 'building', 'color' => '#10b981', 'order' => 2]
            ],
            'combat' => [
                'name' => 'Combat',
                'description' => 'Military and combat-related quests',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 3]
            ],
            'resource' => [
                'name' => 'Resource Management',
                'description' => 'Quests about resource gathering and management',
                'meta' => ['icon' => 'coins', 'color' => '#f59e0b', 'order' => 4]
            ],
            'exploration' => [
                'name' => 'Exploration',
                'description' => 'Quests involving world exploration and discovery',
                'meta' => ['icon' => 'map', 'color' => '#8b5cf6', 'order' => 5]
            ],
            'diplomacy' => [
                'name' => 'Diplomacy',
                'description' => 'Alliance and diplomatic quests',
                'meta' => ['icon' => 'handshake', 'color' => '#06b6d4', 'order' => 6]
            ]
        ];

        foreach ($questCategories as $key => $category) {
            Taxonomy::firstOrCreate(
                ['slug' => $key, 'type' => 'quest_category'],
                [
                    'name' => $category['name'],
                    'slug' => $key,
                    'type' => 'quest_category',
                    'description' => $category['description'],
                    'meta' => $category['meta'],
                    'sort_order' => $category['meta']['order']
                ]
            );
        }

        // Quest Difficulties
        $questDifficulties = [
            'beginner' => [
                'name' => 'Beginner',
                'description' => 'Easy quests for new players',
                'meta' => ['icon' => 'star', 'color' => '#22c55e', 'order' => 1, 'experience_multiplier' => 1.0]
            ],
            'intermediate' => [
                'name' => 'Intermediate',
                'description' => 'Moderate difficulty quests',
                'meta' => ['icon' => 'star', 'color' => '#f59e0b', 'order' => 2, 'experience_multiplier' => 1.5]
            ],
            'advanced' => [
                'name' => 'Advanced',
                'description' => 'Challenging quests for experienced players',
                'meta' => ['icon' => 'star', 'color' => '#ef4444', 'order' => 3, 'experience_multiplier' => 2.0]
            ],
            'expert' => [
                'name' => 'Expert',
                'description' => 'Very difficult quests for expert players',
                'meta' => ['icon' => 'star', 'color' => '#8b5cf6', 'order' => 4, 'experience_multiplier' => 3.0]
            ],
            'legendary' => [
                'name' => 'Legendary',
                'description' => 'Extremely challenging legendary quests',
                'meta' => ['icon' => 'star', 'color' => '#f97316', 'order' => 5, 'experience_multiplier' => 5.0]
            ]
        ];

        foreach ($questDifficulties as $key => $difficulty) {
            Taxonomy::firstOrCreate(
                ['slug' => $key, 'type' => 'quest_difficulty'],
                [
                    'name' => $difficulty['name'],
                    'slug' => $key,
                    'type' => 'quest_difficulty',
                    'description' => $difficulty['description'],
                    'meta' => $difficulty['meta'],
                    'sort_order' => $difficulty['meta']['order']
                ]
            );
        }

        // Building Categories
        $buildingCategories = [
            'resource' => [
                'name' => 'Resource Buildings',
                'description' => 'Buildings that produce resources',
                'meta' => ['icon' => 'factory', 'color' => '#f59e0b', 'order' => 1]
            ],
            'military' => [
                'name' => 'Military Buildings',
                'description' => 'Buildings for military purposes',
                'meta' => ['icon' => 'shield', 'color' => '#ef4444', 'order' => 2]
            ],
            'infrastructure' => [
                'name' => 'Infrastructure',
                'description' => 'Basic infrastructure buildings',
                'meta' => ['icon' => 'home', 'color' => '#6b7280', 'order' => 3]
            ],
            'special' => [
                'name' => 'Special Buildings',
                'description' => 'Unique and special purpose buildings',
                'meta' => ['icon' => 'sparkles', 'color' => '#8b5cf6', 'order' => 4]
            ],
            'defense' => [
                'name' => 'Defense',
                'description' => 'Defensive structures and fortifications',
                'meta' => ['icon' => 'wall', 'color' => '#374151', 'order' => 5]
            ]
        ];

        foreach ($buildingCategories as $key => $category) {
            Taxonomy::create([
                'name' => $category['name'],
                'slug' => $key,
                'type' => 'building_category',
                'description' => $category['description'],
                'meta' => $category['meta'],
                'sort_order' => $category['meta']['order']
            ]);
        }

        // Unit Tribes
        $unitTribes = [
            'romans' => [
                'name' => 'Romans',
                'description' => 'Roman civilization units',
                'meta' => ['icon' => 'shield', 'color' => '#dc2626', 'order' => 1, 'specialty' => 'infantry']
            ],
            'teutons' => [
                'name' => 'Teutons',
                'description' => 'Teutonic civilization units',
                'meta' => ['icon' => 'sword', 'color' => '#059669', 'order' => 2, 'specialty' => 'cavalry']
            ],
            'gauls' => [
                'name' => 'Gauls',
                'description' => 'Gallic civilization units',
                'meta' => ['icon' => 'bow', 'color' => '#7c3aed', 'order' => 3, 'specialty' => 'defense']
            ],
            'egyptians' => [
                'name' => 'Egyptians',
                'description' => 'Egyptian civilization units',
                'meta' => ['icon' => 'pyramid', 'color' => '#d97706', 'order' => 4, 'specialty' => 'archers']
            ],
            'huns' => [
                'name' => 'Huns',
                'description' => 'Hunnic civilization units',
                'meta' => ['icon' => 'horse', 'color' => '#be185d', 'order' => 5, 'specialty' => 'cavalry']
            ]
        ];

        foreach ($unitTribes as $key => $tribe) {
            Taxonomy::create([
                'name' => $tribe['name'],
                'slug' => $key,
                'type' => 'unit_tribe',
                'description' => $tribe['description'],
                'meta' => $tribe['meta'],
                'sort_order' => $tribe['meta']['order']
            ]);
        }

        // Technology Categories
        $technologyCategories = [
            'military' => [
                'name' => 'Military Technology',
                'description' => 'Technologies for military advancement',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 1]
            ],
            'economy' => [
                'name' => 'Economic Technology',
                'description' => 'Technologies for economic development',
                'meta' => ['icon' => 'coins', 'color' => '#f59e0b', 'order' => 2]
            ],
            'infrastructure' => [
                'name' => 'Infrastructure Technology',
                'description' => 'Technologies for infrastructure improvement',
                'meta' => ['icon' => 'building', 'color' => '#6b7280', 'order' => 3]
            ],
            'research' => [
                'name' => 'Research Technology',
                'description' => 'Technologies for research and development',
                'meta' => ['icon' => 'flask', 'color' => '#3b82f6', 'order' => 4]
            ],
            'special' => [
                'name' => 'Special Technology',
                'description' => 'Unique and special technologies',
                'meta' => ['icon' => 'sparkles', 'color' => '#8b5cf6', 'order' => 5]
            ]
        ];

        foreach ($technologyCategories as $key => $category) {
            Taxonomy::create([
                'name' => $category['name'],
                'slug' => $key,
                'type' => 'technology_category',
                'description' => $category['description'],
                'meta' => $category['meta'],
                'sort_order' => $category['meta']['order']
            ]);
        }

        // Resource Types
        $resourceTypes = [
            'wood' => [
                'name' => 'Wood',
                'description' => 'Basic building material',
                'meta' => ['icon' => 'tree', 'color' => '#8b4513', 'order' => 1]
            ],
            'clay' => [
                'name' => 'Clay',
                'description' => 'Construction material',
                'meta' => ['icon' => 'brick', 'color' => '#cd853f', 'order' => 2]
            ],
            'iron' => [
                'name' => 'Iron',
                'description' => 'Metal for weapons and tools',
                'meta' => ['icon' => 'hammer', 'color' => '#708090', 'order' => 3]
            ],
            'crop' => [
                'name' => 'Crop',
                'description' => 'Food for population',
                'meta' => ['icon' => 'wheat', 'color' => '#9acd32', 'order' => 4]
            ],
            'gold' => [
                'name' => 'Gold',
                'description' => 'Premium currency',
                'meta' => ['icon' => 'coins', 'color' => '#ffd700', 'order' => 5]
            ]
        ];

        foreach ($resourceTypes as $key => $resource) {
            Taxonomy::create([
                'name' => $resource['name'],
                'slug' => $key,
                'type' => 'resource_type',
                'description' => $resource['description'],
                'meta' => $resource['meta'],
                'sort_order' => $resource['meta']['order']
            ]);
        }

        // Task Types
        $taskTypes = [
            'daily' => [
                'name' => 'Daily Tasks',
                'description' => 'Tasks that reset daily',
                'meta' => ['icon' => 'calendar', 'color' => '#3b82f6', 'order' => 1]
            ],
            'weekly' => [
                'name' => 'Weekly Tasks',
                'description' => 'Tasks that reset weekly',
                'meta' => ['icon' => 'calendar-days', 'color' => '#10b981', 'order' => 2]
            ],
            'monthly' => [
                'name' => 'Monthly Tasks',
                'description' => 'Tasks that reset monthly',
                'meta' => ['icon' => 'calendar-month', 'color' => '#f59e0b', 'order' => 3]
            ],
            'achievement' => [
                'name' => 'Achievement Tasks',
                'description' => 'One-time achievement tasks',
                'meta' => ['icon' => 'trophy', 'color' => '#8b5cf6', 'order' => 4]
            ],
            'special' => [
                'name' => 'Special Tasks',
                'description' => 'Event and special occasion tasks',
                'meta' => ['icon' => 'star', 'color' => '#ef4444', 'order' => 5]
            ]
        ];

        foreach ($taskTypes as $key => $type) {
            Taxonomy::create([
                'name' => $type['name'],
                'slug' => $key,
                'type' => 'task_type',
                'description' => $type['description'],
                'meta' => $type['meta'],
                'sort_order' => $type['meta']['order']
            ]);
        }

        // Artifact Types
        $artifactTypes = [
            'weapon' => [
                'name' => 'Weapons',
                'description' => 'Combat weapons and tools',
                'meta' => ['icon' => 'sword', 'color' => '#ef4444', 'order' => 1]
            ],
            'armor' => [
                'name' => 'Armor',
                'description' => 'Protective gear and shields',
                'meta' => ['icon' => 'shield', 'color' => '#6b7280', 'order' => 2]
            ],
            'tool' => [
                'name' => 'Tools',
                'description' => 'Construction and crafting tools',
                'meta' => ['icon' => 'wrench', 'color' => '#f59e0b', 'order' => 3]
            ],
            'mystical' => [
                'name' => 'Mystical Items',
                'description' => 'Magical and mystical artifacts',
                'meta' => ['icon' => 'sparkles', 'color' => '#8b5cf6', 'order' => 4]
            ],
            'relic' => [
                'name' => 'Relics',
                'description' => 'Ancient and historical relics',
                'meta' => ['icon' => 'gem', 'color' => '#d97706', 'order' => 5]
            ],
            'crystal' => [
                'name' => 'Crystals',
                'description' => 'Energy crystals and gems',
                'meta' => ['icon' => 'diamond', 'color' => '#06b6d4', 'order' => 6]
            ]
        ];

        foreach ($artifactTypes as $key => $type) {
            Taxonomy::create([
                'name' => $type['name'],
                'slug' => $key,
                'type' => 'artifact_type',
                'description' => $type['description'],
                'meta' => $type['meta'],
                'sort_order' => $type['meta']['order']
            ]);
        }

        // Artifact Rarities
        $artifactRarities = [
            'common' => [
                'name' => 'Common',
                'description' => 'Common artifacts with basic effects',
                'meta' => ['icon' => 'circle', 'color' => '#6b7280', 'order' => 1, 'power_multiplier' => 1.0]
            ],
            'uncommon' => [
                'name' => 'Uncommon',
                'description' => 'Uncommon artifacts with improved effects',
                'meta' => ['icon' => 'circle', 'color' => '#22c55e', 'order' => 2, 'power_multiplier' => 1.5]
            ],
            'rare' => [
                'name' => 'Rare',
                'description' => 'Rare artifacts with significant effects',
                'meta' => ['icon' => 'circle', 'color' => '#3b82f6', 'order' => 3, 'power_multiplier' => 2.0]
            ],
            'epic' => [
                'name' => 'Epic',
                'description' => 'Epic artifacts with powerful effects',
                'meta' => ['icon' => 'circle', 'color' => '#8b5cf6', 'order' => 4, 'power_multiplier' => 3.0]
            ],
            'legendary' => [
                'name' => 'Legendary',
                'description' => 'Legendary artifacts with extraordinary effects',
                'meta' => ['icon' => 'circle', 'color' => '#f59e0b', 'order' => 5, 'power_multiplier' => 4.0]
            ],
            'mythic' => [
                'name' => 'Mythic',
                'description' => 'Mythic artifacts with godlike effects',
                'meta' => ['icon' => 'circle', 'color' => '#ef4444', 'order' => 6, 'power_multiplier' => 5.0]
            ]
        ];

        foreach ($artifactRarities as $key => $rarity) {
            Taxonomy::create([
                'name' => $rarity['name'],
                'slug' => $key,
                'type' => 'artifact_rarity',
                'description' => $rarity['description'],
                'meta' => $rarity['meta'],
                'sort_order' => $rarity['meta']['order']
            ]);
        }

        // World Types
        $worldTypes = [
            'normal' => [
                'name' => 'Normal World',
                'description' => 'Standard game world',
                'meta' => ['icon' => 'globe', 'color' => '#3b82f6', 'order' => 1]
            ],
            'speed' => [
                'name' => 'Speed World',
                'description' => 'Faster-paced game world',
                'meta' => ['icon' => 'bolt', 'color' => '#f59e0b', 'order' => 2]
            ],
            'classic' => [
                'name' => 'Classic World',
                'description' => 'Traditional gameplay world',
                'meta' => ['icon' => 'clock', 'color' => '#6b7280', 'order' => 3]
            ],
            'tribe_wars' => [
                'name' => 'Tribe Wars',
                'description' => 'Alliance-focused world',
                'meta' => ['icon' => 'users', 'color' => '#ef4444', 'order' => 4]
            ],
            'no_hero' => [
                'name' => 'No Hero World',
                'description' => 'World without hero mechanics',
                'meta' => ['icon' => 'user-x', 'color' => '#374151', 'order' => 5]
            ],
            'hero_world' => [
                'name' => 'Hero World',
                'description' => 'World with hero mechanics',
                'meta' => ['icon' => 'user-check', 'color' => '#8b5cf6', 'order' => 6]
            ]
        ];

        foreach ($worldTypes as $key => $type) {
            Taxonomy::create([
                'name' => $type['name'],
                'slug' => $key,
                'type' => 'world_type',
                'description' => $type['description'],
                'meta' => $type['meta'],
                'sort_order' => $type['meta']['order']
            ]);
        }

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
            Taxonomy::create([
                'name' => $category['name'],
                'slug' => $key,
                'type' => 'achievement_category',
                'description' => $category['description'],
                'meta' => $category['meta'],
                'sort_order' => $category['meta']['order']
            ]);
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
            Taxonomy::create([
                'name' => $type['name'],
                'slug' => $key,
                'type' => 'report_type',
                'description' => $type['description'],
                'meta' => $type['meta'],
                'sort_order' => $type['meta']['order']
            ]);
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
            Taxonomy::create([
                'name' => $type['name'],
                'slug' => $key,
                'type' => 'battle_type',
                'description' => $type['description'],
                'meta' => $type['meta'],
                'sort_order' => $type['meta']['order']
            ]);
        }

        $this->command->info('Game taxonomies seeded successfully!');
    }
}
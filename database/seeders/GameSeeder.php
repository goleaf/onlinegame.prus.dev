<?php

namespace Database\Seeders;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\PlayerStatistic;
use App\Models\Game\Quest;
use App\Models\Game\Resource;
use App\Models\Game\Technology;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create test users
        $testUsers = User::factory(10)->create();

        // Create worlds
        $worlds = collect([
            World::factory()->create([
                'name' => 'Travian World 1',
                'description' => 'The main game world',
            ]),
            World::factory()->create([
                'name' => 'Speed World',
                'description' => 'Fast-paced world with 2x speed',
            ]),
        ]);

        // Create building types
        $buildingTypes = $this->createBuildingTypes();

        // Create unit types
        $unitTypes = $this->createUnitTypes();

        // Create technologies
        $technologies = $this->createTechnologies();

        // Create quests
        $quests = $this->createQuests();

        // Create players for each world
        foreach ($worlds as $world) {
            // Create admin player
            $adminPlayer = Player::factory()->create([
                'user_id' => $adminUser->id,
                'world_id' => $world->id,
                'name' => 'Admin Player',
                'tribe' => 'roman',
            ]);

            // Create test players
            $players = Player::factory(20)->create([
                'world_id' => $world->id,
            ]);

            // Create alliances
            $alliances = $this->createAlliances($world, $players);

            // Create villages for each player
            foreach ($players->take(15) as $player) {
                $this->createPlayerVillages($player, $buildingTypes, $unitTypes);
            }

            // Create admin player villages
            $this->createPlayerVillages($adminPlayer, $buildingTypes, $unitTypes);
        }
    }

    private function createBuildingTypes(): array
    {
        $buildingTypes = [
            [
                'name' => 'Woodcutter',
                'key' => 'woodcutter',
                'description' => 'Produces wood',
                'max_level' => 20,
                'costs' => ['wood' => 40, 'clay' => 100, 'iron' => 50, 'crop' => 60],
                'production' => ['wood' => 30],
            ],
            [
                'name' => 'Clay Pit',
                'key' => 'clay_pit',
                'description' => 'Produces clay',
                'max_level' => 20,
                'costs' => ['wood' => 80, 'clay' => 40, 'iron' => 80, 'crop' => 50],
                'production' => ['clay' => 30],
            ],
            [
                'name' => 'Iron Mine',
                'key' => 'iron_mine',
                'description' => 'Produces iron',
                'max_level' => 20,
                'costs' => ['wood' => 100, 'clay' => 80, 'iron' => 30, 'crop' => 60],
                'production' => ['iron' => 30],
            ],
            [
                'name' => 'Crop Field',
                'key' => 'crop_field',
                'description' => 'Produces crop',
                'max_level' => 20,
                'costs' => ['wood' => 70, 'clay' => 90, 'iron' => 70, 'crop' => 20],
                'production' => ['crop' => 20],
            ],
            [
                'name' => 'Main Building',
                'key' => 'main_building',
                'description' => 'Main building of the village',
                'max_level' => 20,
                'costs' => ['wood' => 70, 'clay' => 40, 'iron' => 60, 'crop' => 20],
            ],
            [
                'name' => 'Rally Point',
                'key' => 'rally_point',
                'description' => 'Command center for troops',
                'max_level' => 20,
                'costs' => ['wood' => 110, 'clay' => 160, 'iron' => 90, 'crop' => 70],
            ],
            [
                'name' => 'Marketplace',
                'key' => 'marketplace',
                'description' => 'Trade resources with other players',
                'max_level' => 20,
                'costs' => ['wood' => 80, 'clay' => 70, 'iron' => 120, 'crop' => 70],
            ],
            [
                'name' => 'Embassy',
                'key' => 'embassy',
                'description' => 'Join or create an alliance',
                'max_level' => 20,
                'costs' => ['wood' => 180, 'clay' => 130, 'iron' => 150, 'crop' => 80],
            ],
        ];

        $createdTypes = [];
        foreach ($buildingTypes as $type) {
            $createdTypes[] = BuildingType::factory()->create($type);
        }

        return $createdTypes;
    }

    private function createUnitTypes(): array
    {
        $unitTypes = [
            [
                'name' => 'Legionnaire',
                'key' => 'legionnaire',
                'tribe' => 'roman',
                'description' => 'Roman infantry unit',
                'attack' => 40,
                'defense_infantry' => 35,
                'defense_cavalry' => 50,
                'speed' => 6,
                'carry_capacity' => 50,
                'costs' => ['wood' => 120, 'clay' => 100, 'iron' => 150, 'crop' => 30],
            ],
            [
                'name' => 'Praetorian',
                'key' => 'praetorian',
                'tribe' => 'roman',
                'description' => 'Roman defensive unit',
                'attack' => 30,
                'defense_infantry' => 65,
                'defense_cavalry' => 35,
                'speed' => 5,
                'carry_capacity' => 20,
                'costs' => ['wood' => 100, 'clay' => 130, 'iron' => 160, 'crop' => 70],
            ],
        ];

        $createdTypes = [];
        foreach ($unitTypes as $type) {
            $createdTypes[] = UnitType::factory()->create($type);
        }

        return $createdTypes;
    }

    private function createTechnologies(): array
    {
        $technologies = [
            [
                'name' => 'Weaponry',
                'key' => 'weaponry',
                'description' => 'Increases attack power of troops',
                'costs' => ['wood' => 140, 'clay' => 160, 'iron' => 200, 'crop' => 40],
                'effects' => ['attack_bonus' => 0.1],
            ],
            [
                'name' => 'Armoury',
                'key' => 'armoury',
                'description' => 'Increases defense power of troops',
                'costs' => ['wood' => 170, 'clay' => 200, 'iron' => 380, 'crop' => 130],
                'effects' => ['defense_bonus' => 0.1],
            ],
        ];

        $createdTechnologies = [];
        foreach ($technologies as $tech) {
            $createdTechnologies[] = Technology::factory()->create($tech);
        }

        return $createdTechnologies;
    }

    private function createQuests(): array
    {
        $quests = [
            [
                'name' => 'Welcome to Travian',
                'key' => 'welcome',
                'description' => 'Complete your first village setup',
                'category' => 'tutorial',
                'requirements' => ['build_main_building' => 1],
                'rewards' => ['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000],
                'is_repeatable' => false,
            ],
            [
                'name' => 'Resource Production',
                'key' => 'resource_production',
                'description' => 'Build resource production buildings',
                'category' => 'building',
                'requirements' => ['build_woodcutter' => 1, 'build_clay_pit' => 1, 'build_iron_mine' => 1, 'build_crop_field' => 1],
                'rewards' => ['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000],
                'is_repeatable' => false,
            ],
        ];

        $createdQuests = [];
        foreach ($quests as $quest) {
            $createdQuests[] = Quest::factory()->create($quest);
        }

        return $createdQuests;
    }

    private function createAlliances($world, $players): array
    {
        $alliances = [];
        
        // Create 3-5 alliances
        for ($i = 0; $i < 4; $i++) {
            $leader = $players->random();
            $alliance = Alliance::factory()->create([
                'world_id' => $world->id,
                'leader_id' => $leader->id,
                'name' => $this->faker->company() . ' Alliance',
                'tag' => strtoupper($this->faker->lexify('???')),
            ]);

            // Add 3-8 members to each alliance
            $members = $players->random($this->faker->numberBetween(3, 8));
            foreach ($members as $member) {
                AllianceMember::factory()->create([
                    'alliance_id' => $alliance->id,
                    'player_id' => $member->id,
                    'rank' => $member->id === $leader->id ? 'leader' : $this->faker->randomElement(['member', 'elder']),
                ]);
            }

            $alliances[] = $alliance;
        }

        return $alliances;
    }

    private function createPlayerVillages($player, $buildingTypes, $unitTypes): void
    {
        // Create 1-3 villages per player
        $villageCount = $this->faker->numberBetween(1, 3);
        
        for ($i = 0; $i < $villageCount; $i++) {
            $isCapital = $i === 0;
            $village = Village::factory()->create([
                'player_id' => $player->id,
                'world_id' => $player->world_id,
                'name' => $this->faker->city() . ' Village',
                'is_capital' => $isCapital,
                'x_coordinate' => $this->faker->numberBetween(0, 400),
                'y_coordinate' => $this->faker->numberBetween(0, 400),
            ]);

            // Create resources for the village
            $this->createVillageResources($village);

            // Create buildings for the village
            $this->createVillageBuildings($village, $buildingTypes);

            // Create player statistics
            PlayerStatistic::factory()->create([
                'player_id' => $player->id,
                'world_id' => $player->world_id,
            ]);
        }
    }

    private function createVillageResources($village): void
    {
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
        
        foreach ($resourceTypes as $type) {
            Resource::factory()->create([
                'village_id' => $village->id,
                'type' => $type,
                'amount' => $this->faker->numberBetween(1000, 10000),
                'production_rate' => $this->faker->numberBetween(10, 50),
                'storage_capacity' => $this->faker->numberBetween(10000, 50000),
                'level' => $this->faker->numberBetween(1, 10),
            ]);
        }
    }

    private function createVillageBuildings($village, $buildingTypes): void
    {
        // Create main building
        Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => $buildingTypes[4]->id, // Main Building
            'name' => 'Main Building',
            'level' => $this->faker->numberBetween(1, 5),
            'x' => 0,
            'y' => 0,
        ]);

        // Create resource buildings
        $resourceBuildings = [
            ['type' => 0, 'name' => 'Woodcutter', 'x' => 1, 'y' => 0],
            ['type' => 1, 'name' => 'Clay Pit', 'x' => 2, 'y' => 0],
            ['type' => 2, 'name' => 'Iron Mine', 'x' => 3, 'y' => 0],
            ['type' => 3, 'name' => 'Crop Field', 'x' => 4, 'y' => 0],
        ];

        foreach ($resourceBuildings as $building) {
            Building::factory()->create([
                'village_id' => $village->id,
                'building_type_id' => $buildingTypes[$building['type']]->id,
                'name' => $building['name'],
                'level' => $this->faker->numberBetween(1, 10),
                'x' => $building['x'],
                'y' => $building['y'],
            ]);
        }

        // Create some additional buildings
        $additionalBuildings = [
            ['type' => 5, 'name' => 'Rally Point', 'x' => 0, 'y' => 1],
            ['type' => 6, 'name' => 'Marketplace', 'x' => 1, 'y' => 1],
            ['type' => 7, 'name' => 'Embassy', 'x' => 2, 'y' => 1],
        ];

        foreach ($additionalBuildings as $building) {
            if ($this->faker->boolean(70)) { // 70% chance to build
                Building::factory()->create([
                    'village_id' => $village->id,
                    'building_type_id' => $buildingTypes[$building['type']]->id,
                    'name' => $building['name'],
                    'level' => $this->faker->numberBetween(1, 5),
                    'x' => $building['x'],
                    'y' => $building['y'],
                ]);
            }
        }
    }
}
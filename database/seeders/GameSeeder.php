<?php

namespace Database\Seeders;

use App\Models\Game\Alliance;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\QuestTemplate;
use App\Models\Game\Resource;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GameSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌍 Creating game worlds...');
        $this->createWorlds();

        $this->command->info('👤 Creating admin user...');
        $this->createAdminUser();

        $this->command->info('🏗️ Creating building types...');
        $this->createBuildingTypes();

        $this->command->info('⚔️ Creating unit types...');
        $this->createUnitTypes();

        $this->command->info('🎯 Creating quest templates...');
        $this->createQuestTemplates();

        $this->command->info('👥 Creating players and villages...');
        $this->createPlayersAndVillages();

        $this->command->info('🏘️ Creating buildings and resources...');
        $this->createBuildingsAndResources();

        $this->command->info('🤝 Creating alliances...');
        $this->createAlliances();

        $this->command->info('✅ Game seeding completed successfully!');
    }

    private function createWorlds(): void
    {
        // Create main active world
        World::factory()->create([
            'name' => 'Travian World 1',
            'description' => 'The main game world for all players',
            'is_active' => true,
            'max_players' => 10000,
            'map_size' => 400,
            'speed' => 1,
            'has_plus' => false,
            'has_artifacts' => false,
            'start_date' => now()->subMonths(6),
            'end_date' => now()->addMonths(6),
        ]);

        // Create speed world
        World::factory()->create([
            'name' => 'Speed World',
            'description' => 'High-speed world with accelerated gameplay',
            'is_active' => true,
            'speed' => 3,
        ]);

        // Create classic world
        World::factory()->create([
            'name' => 'Classic World',
            'description' => 'Classic Travian experience',
            'is_active' => true,
            'speed' => 1,
            'has_plus' => false,
            'has_artifacts' => false,
        ]);
    }

    private function createAdminUser(): void
    {
        $adminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create admin player in main world
        $mainWorld = World::where('name', 'Travian World 1')->first();
        $adminPlayer = Player::create([
            'user_id' => $adminUser->id,
            'world_id' => $mainWorld->id,
            'name' => 'Admin',
            'tribe' => 'roman',
            'points' => 0,
            'is_online' => false,
            'last_active_at' => now(),
            'population' => 0,
            'villages_count' => 0,
            'is_active' => true,
            'last_login' => now(),
        ]);

        // Create admin's capital village
        $adminVillage = Village::create([
            'player_id' => $adminPlayer->id,
            'world_id' => $mainWorld->id,
            'name' => 'Admin Capital',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 0,
            'culture_points' => 0,
            'is_capital' => true,
            'is_active' => true,
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
            'wood_capacity' => 10000,
            'clay_capacity' => 10000,
            'iron_capacity' => 10000,
            'crop_capacity' => 10000,
        ]);

        // Create resources for admin village
        $this->createVillageResources($adminVillage);
    }

    private function createBuildingTypes(): void
    {
        $buildingTypes = [
            // Resource buildings
            [
                'name' => 'Woodcutter',
                'key' => 'woodcutter',
                'description' => 'Produces wood for your village',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10]),
                'production' => json_encode(['wood' => 10]),
                'population' => json_encode(['1' => 2, '5' => 3, '10' => 4, '15' => 5, '20' => 6]),
                'is_special' => false,
            ],
            [
                'name' => 'Clay Pit',
                'key' => 'clay_pit',
                'description' => 'Produces clay for your village',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10]),
                'production' => json_encode(['clay' => 10]),
                'population' => json_encode(['1' => 2, '5' => 3, '10' => 4, '15' => 5, '20' => 6]),
                'is_special' => false,
            ],
            [
                'name' => 'Iron Mine',
                'key' => 'iron_mine',
                'description' => 'Produces iron for your village',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10]),
                'production' => json_encode(['iron' => 10]),
                'population' => json_encode(['1' => 2, '5' => 3, '10' => 4, '15' => 5, '20' => 6]),
                'is_special' => false,
            ],
            [
                'name' => 'Crop Field',
                'key' => 'crop_field',
                'description' => 'Produces crop for your village',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10]),
                'production' => json_encode(['crop' => 10]),
                'population' => json_encode(['1' => 2, '5' => 3, '10' => 4, '15' => 5, '20' => 6]),
                'is_special' => false,
            ],
            // Storage buildings
            [
                'name' => 'Warehouse',
                'key' => 'warehouse',
                'description' => 'Increases storage capacity for wood, clay, and iron',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 40]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            [
                'name' => 'Granary',
                'key' => 'granary',
                'description' => 'Increases storage capacity for crop',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 40]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            // Military buildings
            [
                'name' => 'Barracks',
                'key' => 'barracks',
                'description' => 'Trains infantry units',
                'max_level' => 20,
                'requirements' => json_encode(['main_building' => 3]),
                'costs' => json_encode(['wood' => 200, 'clay' => 150, 'iron' => 100, 'crop' => 50]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            [
                'name' => 'Stable',
                'key' => 'stable',
                'description' => 'Trains cavalry units',
                'max_level' => 20,
                'requirements' => json_encode(['barracks' => 5, 'smithy' => 3]),
                'costs' => json_encode(['wood' => 300, 'clay' => 200, 'iron' => 150, 'crop' => 100]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            [
                'name' => 'Workshop',
                'key' => 'workshop',
                'description' => 'Trains siege units',
                'max_level' => 20,
                'requirements' => json_encode(['barracks' => 10, 'smithy' => 5]),
                'costs' => json_encode(['wood' => 500, 'clay' => 300, 'iron' => 200, 'crop' => 150]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            // Other buildings
            [
                'name' => 'Main Building',
                'key' => 'main_building',
                'description' => 'The heart of your village',
                'max_level' => 20,
                'requirements' => null,
                'costs' => json_encode(['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 40]),
                'production' => null,
                'population' => json_encode(['1' => 2, '5' => 3, '10' => 4, '15' => 5, '20' => 6]),
                'is_special' => false,
            ],
            [
                'name' => 'Smithy',
                'key' => 'smithy',
                'description' => 'Researches military technologies',
                'max_level' => 20,
                'requirements' => json_encode(['main_building' => 3, 'barracks' => 1]),
                'costs' => json_encode(['wood' => 200, 'clay' => 150, 'iron' => 100, 'crop' => 50]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
            [
                'name' => 'Rally Point',
                'key' => 'rally_point',
                'description' => 'Coordinates troop movements',
                'max_level' => 20,
                'requirements' => json_encode(['main_building' => 1]),
                'costs' => json_encode(['wood' => 150, 'clay' => 100, 'iron' => 80, 'crop' => 60]),
                'production' => null,
                'population' => json_encode(['1' => 1, '5' => 2, '10' => 3, '15' => 4, '20' => 5]),
                'is_special' => false,
            ],
        ];

        foreach ($buildingTypes as $buildingType) {
            BuildingType::create($buildingType);
        }
    }

    private function createUnitTypes(): void
    {
        $unitTypes = [
            // Roman units
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
                'costs' => json_encode(['wood' => 120, 'clay' => 100, 'iron' => 150, 'crop' => 30]),
                'requirements' => json_encode(['barracks' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Praetorian',
                'key' => 'praetorian',
                'tribe' => 'roman',
                'description' => 'Roman defensive infantry',
                'attack' => 30,
                'defense_infantry' => 65,
                'defense_cavalry' => 35,
                'speed' => 5,
                'carry_capacity' => 20,
                'costs' => json_encode(['wood' => 100, 'clay' => 130, 'iron' => 55, 'crop' => 100]),
                'requirements' => json_encode(['barracks' => 1, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Imperian',
                'key' => 'imperian',
                'tribe' => 'roman',
                'description' => 'Roman offensive infantry',
                'attack' => 70,
                'defense_infantry' => 40,
                'defense_cavalry' => 25,
                'speed' => 7,
                'carry_capacity' => 50,
                'costs' => json_encode(['wood' => 150, 'clay' => 160, 'iron' => 210, 'crop' => 80]),
                'requirements' => json_encode(['barracks' => 5, 'smithy' => 2]),
                'is_special' => false,
            ],
            [
                'name' => 'Equites Legati',
                'key' => 'equites_legati',
                'tribe' => 'roman',
                'description' => 'Roman scout cavalry',
                'attack' => 0,
                'defense_infantry' => 20,
                'defense_cavalry' => 10,
                'speed' => 16,
                'carry_capacity' => 0,
                'costs' => json_encode(['wood' => 140, 'clay' => 160, 'iron' => 20, 'crop' => 40]),
                'requirements' => json_encode(['stable' => 1, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Equites Imperatoris',
                'key' => 'equites_imperatoris',
                'tribe' => 'roman',
                'description' => 'Roman heavy cavalry',
                'attack' => 120,
                'defense_infantry' => 65,
                'defense_cavalry' => 50,
                'speed' => 14,
                'carry_capacity' => 100,
                'costs' => json_encode(['wood' => 550, 'clay' => 440, 'iron' => 320, 'crop' => 100]),
                'requirements' => json_encode(['stable' => 5, 'smithy' => 3]),
                'is_special' => false,
            ],
            [
                'name' => 'Equites Caesaris',
                'key' => 'equites_caesaris',
                'tribe' => 'roman',
                'description' => 'Roman elite cavalry',
                'attack' => 180,
                'defense_infantry' => 80,
                'defense_cavalry' => 105,
                'speed' => 10,
                'carry_capacity' => 70,
                'costs' => json_encode(['wood' => 550, 'clay' => 640, 'iron' => 800, 'crop' => 180]),
                'requirements' => json_encode(['stable' => 10, 'smithy' => 5]),
                'is_special' => false,
            ],
            // Teuton units
            [
                'name' => 'Clubswinger',
                'key' => 'clubswinger',
                'tribe' => 'teuton',
                'description' => 'Teuton infantry unit',
                'attack' => 40,
                'defense_infantry' => 20,
                'defense_cavalry' => 5,
                'speed' => 7,
                'carry_capacity' => 60,
                'costs' => json_encode(['wood' => 95, 'clay' => 75, 'iron' => 40, 'crop' => 40]),
                'requirements' => json_encode(['barracks' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Spearman',
                'key' => 'spearman',
                'tribe' => 'teuton',
                'description' => 'Teuton defensive infantry',
                'attack' => 10,
                'defense_infantry' => 35,
                'defense_cavalry' => 60,
                'speed' => 7,
                'carry_capacity' => 40,
                'costs' => json_encode(['wood' => 145, 'clay' => 70, 'iron' => 85, 'crop' => 40]),
                'requirements' => json_encode(['barracks' => 1, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Axeman',
                'key' => 'axeman',
                'tribe' => 'teuton',
                'description' => 'Teuton offensive infantry',
                'attack' => 60,
                'defense_infantry' => 30,
                'defense_cavalry' => 30,
                'speed' => 6,
                'carry_capacity' => 50,
                'costs' => json_encode(['wood' => 130, 'clay' => 120, 'iron' => 170, 'crop' => 70]),
                'requirements' => json_encode(['barracks' => 3, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Scout',
                'key' => 'scout',
                'tribe' => 'teuton',
                'description' => 'Teuton scout cavalry',
                'attack' => 0,
                'defense_infantry' => 10,
                'defense_cavalry' => 5,
                'speed' => 9,
                'carry_capacity' => 0,
                'costs' => json_encode(['wood' => 160, 'clay' => 100, 'iron' => 50, 'crop' => 50]),
                'requirements' => json_encode(['stable' => 1, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Paladin',
                'key' => 'paladin',
                'tribe' => 'teuton',
                'description' => 'Teuton heavy cavalry',
                'attack' => 55,
                'defense_infantry' => 100,
                'defense_cavalry' => 40,
                'speed' => 10,
                'carry_capacity' => 110,
                'costs' => json_encode(['wood' => 370, 'clay' => 270, 'iron' => 290, 'crop' => 75]),
                'requirements' => json_encode(['stable' => 3, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Teutonic Knight',
                'key' => 'teutonic_knight',
                'tribe' => 'teuton',
                'description' => 'Teuton elite cavalry',
                'attack' => 150,
                'defense_infantry' => 50,
                'defense_cavalry' => 75,
                'speed' => 9,
                'carry_capacity' => 80,
                'costs' => json_encode(['wood' => 450, 'clay' => 515, 'iron' => 480, 'crop' => 80]),
                'requirements' => json_encode(['stable' => 5, 'smithy' => 3]),
                'is_special' => false,
            ],
            // Gaul units
            [
                'name' => 'Phalanx',
                'key' => 'phalanx',
                'tribe' => 'gaul',
                'description' => 'Gaul infantry unit',
                'attack' => 15,
                'defense_infantry' => 40,
                'defense_cavalry' => 50,
                'speed' => 7,
                'carry_capacity' => 35,
                'costs' => json_encode(['wood' => 100, 'clay' => 130, 'iron' => 55, 'crop' => 30]),
                'requirements' => json_encode(['barracks' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Swordsman',
                'key' => 'swordsman',
                'tribe' => 'gaul',
                'description' => 'Gaul offensive infantry',
                'attack' => 65,
                'defense_infantry' => 35,
                'defense_cavalry' => 20,
                'speed' => 6,
                'carry_capacity' => 45,
                'costs' => json_encode(['wood' => 140, 'clay' => 150, 'iron' => 185, 'crop' => 60]),
                'requirements' => json_encode(['barracks' => 3, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Pathfinder',
                'key' => 'pathfinder',
                'tribe' => 'gaul',
                'description' => 'Gaul scout cavalry',
                'attack' => 0,
                'defense_infantry' => 20,
                'defense_cavalry' => 10,
                'speed' => 17,
                'carry_capacity' => 0,
                'costs' => json_encode(['wood' => 170, 'clay' => 150, 'iron' => 20, 'crop' => 40]),
                'requirements' => json_encode(['stable' => 1, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Theutates Thunder',
                'key' => 'theutates_thunder',
                'tribe' => 'gaul',
                'description' => 'Gaul heavy cavalry',
                'attack' => 90,
                'defense_infantry' => 25,
                'defense_cavalry' => 40,
                'speed' => 19,
                'carry_capacity' => 75,
                'costs' => json_encode(['wood' => 350, 'clay' => 450, 'iron' => 230, 'crop' => 60]),
                'requirements' => json_encode(['stable' => 3, 'smithy' => 1]),
                'is_special' => false,
            ],
            [
                'name' => 'Druidrider',
                'key' => 'druidrider',
                'tribe' => 'gaul',
                'description' => 'Gaul elite cavalry',
                'attack' => 45,
                'defense_infantry' => 115,
                'defense_cavalry' => 55,
                'speed' => 16,
                'carry_capacity' => 35,
                'costs' => json_encode(['wood' => 360, 'clay' => 330, 'iron' => 280, 'crop' => 120]),
                'requirements' => json_encode(['stable' => 5, 'smithy' => 3]),
                'is_special' => false,
            ],
            [
                'name' => 'Haeduan',
                'key' => 'haeduan',
                'tribe' => 'gaul',
                'description' => 'Gaul elite cavalry',
                'attack' => 140,
                'defense_infantry' => 60,
                'defense_cavalry' => 165,
                'speed' => 16,
                'carry_capacity' => 65,
                'costs' => json_encode(['wood' => 500, 'clay' => 620, 'iron' => 675, 'crop' => 170]),
                'requirements' => json_encode(['stable' => 10, 'smithy' => 5]),
                'is_special' => false,
            ],
        ];

        foreach ($unitTypes as $unitType) {
            UnitType::create($unitType);
        }
    }

    private function createQuestTemplates(): void
    {
        $questTemplates = [
            [
                'name' => 'Welcome to Travian',
                'key' => 'welcome',
                'description' => 'Welcome to the world of Travian! Complete this quest to get started.',
                'category' => 'tutorial',
                'difficulty' => 1,
                'requirements' => json_encode([]),
                'rewards' => json_encode(['resources' => ['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]]),
                'is_active' => true,
            ],
            [
                'name' => 'First Building',
                'key' => 'first_building',
                'description' => 'Build your first building to expand your village.',
                'category' => 'building',
                'difficulty' => 1,
                'requirements' => json_encode(['buildings' => ['main_building' => 1]]),
                'rewards' => json_encode(['resources' => ['wood' => 500, 'clay' => 500, 'iron' => 500, 'crop' => 500]]),
                'is_active' => true,
            ],
            [
                'name' => 'Resource Production',
                'key' => 'resource_production',
                'description' => 'Upgrade your resource buildings to increase production.',
                'category' => 'building',
                'difficulty' => 2,
                'requirements' => json_encode(['buildings' => ['woodcutter' => 5, 'clay_pit' => 5, 'iron_mine' => 5, 'crop_field' => 5]]),
                'rewards' => json_encode(['resources' => ['wood' => 2000, 'clay' => 2000, 'iron' => 2000, 'crop' => 2000]]),
                'is_active' => true,
            ],
            [
                'name' => 'Military Training',
                'key' => 'military_training',
                'description' => 'Train your first military units.',
                'category' => 'combat',
                'difficulty' => 2,
                'requirements' => json_encode(['buildings' => ['barracks' => 3], 'units' => ['legionnaire' => 10]]),
                'rewards' => json_encode(['resources' => ['wood' => 1000, 'clay' => 1000, 'iron' => 1000, 'crop' => 1000]]),
                'is_active' => true,
            ],
            [
                'name' => 'Village Expansion',
                'key' => 'village_expansion',
                'description' => 'Expand your village by building more structures.',
                'category' => 'building',
                'difficulty' => 3,
                'requirements' => json_encode(['buildings' => ['main_building' => 10, 'warehouse' => 5, 'granary' => 5]]),
                'rewards' => json_encode(['resources' => ['wood' => 5000, 'clay' => 5000, 'iron' => 5000, 'crop' => 5000]]),
                'is_active' => true,
            ],
        ];

        foreach ($questTemplates as $questTemplate) {
            QuestTemplate::create($questTemplate);
        }
    }

    private function createPlayersAndVillages(): void
    {
        $mainWorld = World::where('name', 'Travian World 1')->first();

        // Create 50 random players
        $players = Player::factory()
            ->count(50)
            ->create(['world_id' => $mainWorld->id]);

        foreach ($players as $player) {
            // Create 1-5 villages per player
            $villageCount = rand(1, 5);
            $villages = Village::factory()
                ->count($villageCount)
                ->create([
                    'player_id' => $player->id,
                    'world_id' => $mainWorld->id,
                ]);

            // Make first village the capital
            if ($villages->count() > 0) {
                $villages->first()->update(['is_capital' => true]);
            }

            // Create resources for each village
            foreach ($villages as $village) {
                $this->createVillageResources($village);
            }

            // Update player statistics
            $player->update([
                'villages_count' => $villages->count(),
                'population' => $villages->sum('population'),
            ]);
        }
    }

    private function createVillageResources(Village $village): void
    {
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];

        foreach ($resourceTypes as $type) {
            Resource::create([
                'village_id' => $village->id,
                'type' => $type,
                'amount' => rand(1000, 10000),
                'production_rate' => rand(10, 50),
                'storage_capacity' => rand(10000, 100000),
                'level' => rand(1, 10),
                'last_updated' => now(),
            ]);
        }
    }

    private function createBuildingsAndResources(): void
    {
        $villages = Village::with('player')->get();
        $buildingTypes = BuildingType::all();

        foreach ($villages as $village) {
            // Create 5-15 random buildings per village
            $buildingCount = rand(5, 15);
            $usedPositions = [];

            for ($i = 0; $i < $buildingCount; $i++) {
                // Find an unused position
                do {
                    $x = rand(0, 18);
                    $y = rand(0, 18);
                } while (in_array([$x, $y], $usedPositions));

                $usedPositions[] = [$x, $y];

                $buildingType = $buildingTypes->random();

                Building::create([
                    'village_id' => $village->id,
                    'building_type_id' => $buildingType->id,
                    'name' => $buildingType->name,
                    'level' => rand(1, min(10, $buildingType->max_level)),
                    'x' => $x,
                    'y' => $y,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createAlliances(): void
    {
        $mainWorld = World::where('name', 'Travian World 1')->first();
        $players = Player::where('world_id', $mainWorld->id)->get();

        // Create 5-10 alliances
        $allianceCount = rand(5, 10);

        for ($i = 0; $i < $allianceCount; $i++) {
            $leader = $players->random();

            $alliance = Alliance::create([
                'world_id' => $mainWorld->id,
                'tag' => strtoupper($this->faker->lexify('???')),
                'name' => $this->faker->company() . ' Alliance',
                'description' => $this->faker->paragraph(),
                'leader_id' => $leader->id,
                'points' => rand(1000, 100000),
                'villages_count' => rand(10, 100),
                'members_count' => rand(5, 50),
                'is_active' => true,
            ]);

            // Add 3-15 members to each alliance
            $memberCount = rand(3, 15);
            $allianceMembers = $players->random($memberCount);

            foreach ($allianceMembers as $index => $member) {
                $rank = $index === 0 ? 'leader' : ($index < 3 ? 'elder' : 'member');

                // Check if player already has an alliance membership
                if (!$member->allianceMembership()->exists()) {
                    $member->allianceMembership()->create([
                        'alliance_id' => $alliance->id,
                        'rank' => $rank,
                        'joined_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }
    }
}

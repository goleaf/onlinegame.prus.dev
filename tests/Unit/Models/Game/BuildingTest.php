<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingTest extends TestCase
{
    use RefreshDatabase;

    protected Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->building = new Building();
    }

    /**
     * @test
     */
    public function it_can_create_building()
    {
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $building = Building::create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Town Hall',
            'level' => 1,
            'x' => 10,
            'y' => 15,
            'is_active' => true,
            'metadata' => ['special' => 'upgraded'],
        ]);

        $this->assertInstanceOf(Building::class, $building);
        $this->assertEquals($village->id, $building->village_id);
        $this->assertEquals($buildingType->id, $building->building_type_id);
        $this->assertEquals('Town Hall', $building->name);
        $this->assertEquals(1, $building->level);
        $this->assertEquals(10, $building->x);
        $this->assertEquals(15, $building->y);
        $this->assertTrue($building->is_active);
        $this->assertEquals(['special' => 'upgraded'], $building->metadata);
    }

    /**
     * @test
     */
    public function it_casts_datetime_fields()
    {
        $now = now();
        $building = Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Test Building',
            'level' => 1,
            'upgrade_started_at' => $now,
            'upgrade_completed_at' => $now->addHour(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $building->upgrade_started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $building->upgrade_completed_at);
    }

    /**
     * @test
     */
    public function it_casts_metadata_to_array()
    {
        $building = Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Test Building',
            'level' => 1,
            'metadata' => ['key' => 'value', 'number' => 123],
        ]);

        $this->assertIsArray($building->metadata);
        $this->assertEquals(['key' => 'value', 'number' => 123], $building->metadata);
    }

    /**
     * @test
     */
    public function it_belongs_to_village()
    {
        $village = Village::factory()->create();
        $building = Building::create([
            'village_id' => $village->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Test Building',
            'level' => 1,
        ]);

        $this->assertInstanceOf(Village::class, $building->village);
        $this->assertEquals($village->id, $building->village->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_building_type()
    {
        $buildingType = BuildingType::factory()->create();
        $building = Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Test Building',
            'level' => 1,
        ]);

        $this->assertInstanceOf(BuildingType::class, $building->buildingType);
        $this->assertEquals($buildingType->id, $building->buildingType->id);
    }

    /**
     * @test
     */
    public function it_has_by_village_scope()
    {
        $village1 = Village::factory()->create();
        $village2 = Village::factory()->create();

        Building::create([
            'village_id' => $village1->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Village 1 Building',
            'level' => 1,
        ]);

        Building::create([
            'village_id' => $village2->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Village 2 Building',
            'level' => 1,
        ]);

        $village1Buildings = Building::byVillage($village1->id)->get();
        $this->assertCount(1, $village1Buildings);
        $this->assertEquals('Village 1 Building', $village1Buildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_type_scope()
    {
        $buildingType1 = BuildingType::factory()->create();
        $buildingType2 = BuildingType::factory()->create();

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => $buildingType1->id,
            'name' => 'Type 1 Building',
            'level' => 1,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => $buildingType2->id,
            'name' => 'Type 2 Building',
            'level' => 1,
        ]);

        $type1Buildings = Building::byType($buildingType1->id)->get();
        $this->assertCount(1, $type1Buildings);
        $this->assertEquals('Type 1 Building', $type1Buildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_active_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Active Building',
            'level' => 1,
            'is_active' => true,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Inactive Building',
            'level' => 1,
            'is_active' => false,
        ]);

        $activeBuildings = Building::active()->get();
        $this->assertCount(1, $activeBuildings);
        $this->assertEquals('Active Building', $activeBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_level_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 5 Building',
            'level' => 5,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 10 Building',
            'level' => 10,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 15 Building',
            'level' => 15,
        ]);

        $midLevelBuildings = Building::byLevel(5, 10)->get();
        $this->assertCount(2, $midLevelBuildings);
    }

    /**
     * @test
     */
    public function it_has_upgradeable_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Upgradeable Building',
            'level' => 1,
            'is_active' => true,
            'upgrade_started_at' => null,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Upgrading Building',
            'level' => 1,
            'is_active' => true,
            'upgrade_started_at' => now(),
        ]);

        $upgradeableBuildings = Building::upgradeable()->get();
        $this->assertCount(1, $upgradeableBuildings);
        $this->assertEquals('Upgradeable Building', $upgradeableBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_in_progress_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'In Progress Building',
            'level' => 1,
            'upgrade_started_at' => now(),
            'upgrade_completed_at' => null,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Completed Building',
            'level' => 1,
            'upgrade_started_at' => now()->subHour(),
            'upgrade_completed_at' => now(),
        ]);

        $inProgressBuildings = Building::inProgress()->get();
        $this->assertCount(1, $inProgressBuildings);
        $this->assertEquals('In Progress Building', $inProgressBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_completed_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Completed Building',
            'level' => 1,
            'upgrade_completed_at' => now(),
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'In Progress Building',
            'level' => 1,
            'upgrade_started_at' => now(),
            'upgrade_completed_at' => null,
        ]);

        $completedBuildings = Building::completed()->get();
        $this->assertCount(1, $completedBuildings);
        $this->assertEquals('Completed Building', $completedBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_top_level_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 5 Building',
            'level' => 5,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 10 Building',
            'level' => 10,
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Level 15 Building',
            'level' => 15,
        ]);

        $topBuildings = Building::topLevel(2)->get();
        $this->assertCount(2, $topBuildings);
        $this->assertEquals(15, $topBuildings->first()->level);
        $this->assertEquals(10, $topBuildings->last()->level);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Recent Building',
            'level' => 1,
            'created_at' => now()->subDays(3),
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Old Building',
            'level' => 1,
            'created_at' => now()->subDays(10),
        ]);

        $recentBuildings = Building::recent(7)->get();
        $this->assertCount(1, $recentBuildings);
        $this->assertEquals('Recent Building', $recentBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_has_search_scope()
    {
        $buildingType = BuildingType::factory()->create([
            'name' => 'Town Hall',
            'description' => 'Main building of the village',
        ]);

        Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Town Hall Building',
            'level' => 1,
        ]);

        $searchResults = Building::search('Town Hall')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Town Hall Building', $searchResults->first()->name);
    }

    /**
     * @test
     */
    public function it_has_with_building_type_info_scope()
    {
        $buildingType = BuildingType::factory()->create();
        $village = Village::factory()->create();

        Building::create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Test Building',
            'level' => 1,
        ]);

        $building = Building::withBuildingTypeInfo()->first();

        $this->assertTrue($building->relationLoaded('buildingType'));
        $this->assertTrue($building->relationLoaded('village'));
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $building = Building::create([
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Test Building',
            'level' => 1,
        ]);

        $this->assertNotNull($building->reference_number);
        $this->assertStringStartsWith('BLD-', $building->reference_number);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'village_id' => Village::factory()->create()->id,
            'building_type_id' => BuildingType::factory()->create()->id,
            'name' => 'Mass Assignment Test',
            'level' => 5,
            'x' => 20,
            'y' => 25,
            'is_active' => false,
            'metadata' => ['test' => 'value'],
        ];

        $building = Building::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $building->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_reference_trait()
    {
        $this->assertTrue(method_exists($this->building, 'generateReference'));
    }

    /**
     * @test
     */
    public function it_has_auditing_trait()
    {
        $this->assertTrue(method_exists($this->building, 'audits'));
    }

    /**
     * @test
     */
    public function it_can_get_cached_buildings()
    {
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        Building::create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Cached Building',
            'level' => 1,
        ]);

        $cachedBuildings = Building::getCachedBuildings($village->id);

        $this->assertCount(1, $cachedBuildings);
        $this->assertEquals('Cached Building', $cachedBuildings->first()->name);
    }

    /**
     * @test
     */
    public function it_can_get_cached_buildings_with_filters()
    {
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        Building::create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Active Building',
            'level' => 1,
            'is_active' => true,
        ]);

        Building::create([
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'name' => 'Inactive Building',
            'level' => 1,
            'is_active' => false,
        ]);

        $activeBuildings = Building::getCachedBuildings($village->id, ['active' => true]);

        $this->assertCount(1, $activeBuildings);
        $this->assertEquals('Active Building', $activeBuildings->first()->name);
    }
}

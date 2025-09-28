<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_buildings()
    {
        $user = User::factory()->create();
        Building::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/buildings');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'village_id',
                        'building_type_id',
                        'level',
                        'is_upgrading',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_building()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/buildings/{$building->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'village_id',
                'building_type_id',
                'level',
                'is_upgrading',
                'building_type',
                'village',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_building()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $buildingData = [
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
            'is_upgrading' => false,
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings', $buildingData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'building' => [
                    'id',
                    'village_id',
                    'building_type_id',
                    'level',
                    'is_upgrading',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('buildings', [
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_building()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();

        $updateData = [
            'level' => 2,
            'is_upgrading' => true,
        ];

        $response = $this->actingAs($user)->put("/api/game/buildings/{$building->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'building' => [
                    'id',
                    'level',
                    'is_upgrading',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'level' => 2,
            'is_upgrading' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_building()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/buildings/{$building->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    /**
     * @test
     */
    public function it_can_get_buildings_by_village()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();
        Building::factory()->count(2)->create(['village_id' => $village->id]);
        Building::factory()->count(1)->create();  // Other village

        $response = $this->actingAs($user)->get("/api/game/buildings/village/{$village->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_buildings_by_type()
    {
        $user = User::factory()->create();
        $buildingType = BuildingType::factory()->create();
        Building::factory()->count(2)->create(['building_type_id' => $buildingType->id]);
        Building::factory()->count(1)->create();  // Other type

        $response = $this->actingAs($user)->get("/api/game/buildings/type/{$buildingType->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_building_queue()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/buildings/queue/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'village_id',
                        'building_type_id',
                        'level',
                        'started_at',
                        'completion_time',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_start_building_construction()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $constructionData = [
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings/construct', $constructionData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'building_queue' => [
                    'id',
                    'village_id',
                    'building_type_id',
                    'level',
                    'started_at',
                    'completion_time',
                    'status',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_upgrade_building()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create(['level' => 1]);

        $response = $this->actingAs($user)->post("/api/game/buildings/{$building->id}/upgrade");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'building_queue' => [
                    'id',
                    'village_id',
                    'building_type_id',
                    'level',
                    'started_at',
                    'completion_time',
                    'status',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_cancel_building_construction()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create(['is_upgrading' => true]);

        $response = $this->actingAs($user)->post("/api/game/buildings/{$building->id}/cancel");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'is_upgrading' => false,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_statistics()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/buildings/statistics/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_buildings',
                'by_type',
                'by_level',
                'upgrading_count',
                'construction_queue',
                'resource_production',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_types()
    {
        $user = User::factory()->create();
        BuildingType::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/buildings/types');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'category',
                        'max_level',
                        'resource_cost',
                        'construction_time',
                        'effects',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_requirements()
    {
        $user = User::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/buildings/requirements/{$buildingType->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'building_type',
                'requirements' => [
                    'resources',
                    'buildings',
                    'technologies',
                    'level_requirements',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_building_effects()
    {
        $user = User::factory()->create();
        $building = Building::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/buildings/{$building->id}/effects");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'building',
                'effects' => [
                    'resource_production',
                    'storage_capacity',
                    'defense_bonus',
                    'attack_bonus',
                    'special_abilities',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/buildings');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_building_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/buildings', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id', 'building_type_id', 'level']);
    }

    /**
     * @test
     */
    public function it_validates_level_range()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $buildingData = [
            'village_id' => $village->id,
            'building_type_id' => $buildingType->id,
            'level' => 25,  // Invalid: exceeds max 20
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings', $buildingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level']);
    }

    /**
     * @test
     */
    public function it_validates_village_exists()
    {
        $user = User::factory()->create();
        $buildingType = BuildingType::factory()->create();

        $buildingData = [
            'village_id' => 999,  // Non-existent village
            'building_type_id' => $buildingType->id,
            'level' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings', $buildingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id']);
    }

    /**
     * @test
     */
    public function it_validates_building_type_exists()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $buildingData = [
            'village_id' => $village->id,
            'building_type_id' => 999,  // Non-existent building type
            'level' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings', $buildingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['building_type_id']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_building()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/buildings/999');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $player;
    protected $village;
    protected $buildingType;
    protected $building;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = $this->user->player()->create([
            'name' => 'TestPlayer',
            'world_id' => 1,
        ]);

        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => 1,
        ]);

        $this->buildingType = BuildingType::factory()->create([
            'name' => 'barracks',
            'display_name' => 'Barracks',
        ]);

        $this->building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type_id' => $this->buildingType->id,
            'level' => 5,
        ]);
    }

    public function test_can_get_village_buildings()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/villages/{$this->village->id}/buildings");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'village_id',
                        'building_type_id',
                        'level',
                        'building_type' => [
                            'id',
                            'name',
                            'display_name',
                        ],
                    ],
                ],
                'message',
            ]);
    }

    public function test_can_get_building_details()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/buildings/{$this->building->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'village_id',
                    'building_type_id',
                    'level',
                    'upgrade_cost',
                    'upgrade_time',
                    'building_type',
                    'village',
                ],
                'message',
            ]);
    }

    public function test_can_get_building_types()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/building-types');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'display_name',
                    ],
                ],
                'message',
            ]);
    }

    public function test_can_get_building_queue()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/villages/{$this->village->id}/building-queue");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    public function test_cannot_access_other_player_buildings()
    {
        $otherUser = User::factory()->create();
        $otherPlayer = $otherUser->player()->create([
            'name' => 'OtherPlayer',
            'world_id' => 1,
        ]);
        $otherVillage = Village::factory()->create([
            'player_id' => $otherPlayer->id,
            'world_id' => 1,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/villages/{$otherVillage->id}/buildings");

        $response->assertStatus(404);
    }

    public function test_cannot_access_other_player_building_details()
    {
        $otherUser = User::factory()->create();
        $otherPlayer = $otherUser->player()->create([
            'name' => 'OtherPlayer',
            'world_id' => 1,
        ]);
        $otherVillage = Village::factory()->create([
            'player_id' => $otherPlayer->id,
            'world_id' => 1,
        ]);
        $otherBuilding = Building::factory()->create([
            'village_id' => $otherVillage->id,
            'building_type_id' => $this->buildingType->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/buildings/{$otherBuilding->id}");

        $response->assertStatus(404);
    }
}

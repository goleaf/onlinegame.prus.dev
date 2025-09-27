<?php

namespace Tests\Feature\Game;

use App\Models\Game\Artifact;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArtifactControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected Village $village;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
    }

    /** @test */
    public function it_can_list_artifacts()
    {
        Artifact::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/game/artifacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'rarity',
                            'status',
                            'power_level',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_artifact()
    {
        $artifact = Artifact::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/game/artifacts/{$artifact->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function it_can_create_artifact()
    {
        $artifactData = [
            'name' => 'Test Artifact',
            'description' => 'A test artifact',
            'type' => 'weapon',
            'rarity' => 'rare',
            'power_level' => 75,
            'effects' => [
                ['type' => 'combat_bonus', 'magnitude' => 20.0]
            ],
            'requirements' => [
                ['type' => 'player_level', 'value' => 30]
            ],
            'is_server_wide' => false,
            'is_unique' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/game/artifacts', $artifactData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('artifacts', [
            'name' => 'Test Artifact',
            'type' => 'weapon',
            'rarity' => 'rare'
        ]);
    }

    /** @test */
    public function it_can_activate_artifact()
    {
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/game/artifacts/{$artifact->id}/activate", [
                'owner_id' => $this->player->id,
                'village_id' => $this->village->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'owner_id',
                    'village_id',
                    'activated_at'
                ]
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id,
            'status' => 'active',
            'owner_id' => $this->player->id,
            'village_id' => $this->village->id
        ]);
    }

    /** @test */
    public function it_can_deactivate_artifact()
    {
        $artifact = Artifact::factory()->create([
            'status' => 'active',
            'owner_id' => $this->player->id,
            'village_id' => $this->village->id
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/game/artifacts/{$artifact->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'activated_at'
                ]
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id,
            'status' => 'inactive'
        ]);
    }

    /** @test */
    public function it_can_get_server_wide_artifacts()
    {
        Artifact::factory()->create(['is_server_wide' => true, 'status' => 'active']);
        Artifact::factory()->create(['is_server_wide' => false, 'status' => 'active']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/game/artifacts/server-wide');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_server_wide',
                        'status'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_generate_random_artifact()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/game/artifacts/generate-random', [
                'type' => 'weapon',
                'rarity' => 'rare'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function it_can_update_artifact()
    {
        $artifact = Artifact::factory()->create();

        $updateData = [
            'name' => 'Updated Artifact Name',
            'power_level' => 90,
            'durability' => 85
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/game/artifacts/{$artifact->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'power_level',
                    'durability',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id,
            'name' => 'Updated Artifact Name',
            'power_level' => 90,
            'durability' => 85
        ]);
    }

    /** @test */
    public function it_can_delete_artifact()
    {
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/game/artifacts/{$artifact->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertDatabaseMissing('artifacts', [
            'id' => $artifact->id
        ]);
    }

    /** @test */
    public function it_cannot_delete_active_artifact()
    {
        $artifact = Artifact::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/game/artifacts/{$artifact->id}");

        $response->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id
        ]);
    }

    /** @test */
    public function it_can_filter_artifacts_by_type()
    {
        Artifact::factory()->create(['type' => 'weapon']);
        Artifact::factory()->create(['type' => 'armor']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/game/artifacts?type=weapon');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('weapon', $data[0]['type']);
    }

    /** @test */
    public function it_can_filter_artifacts_by_rarity()
    {
        Artifact::factory()->create(['rarity' => 'rare']);
        Artifact::factory()->create(['rarity' => 'common']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/game/artifacts?rarity=rare');

        $response->assertStatus(200);
        
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('rare', $data[0]['rarity']);
    }

    /** @test */
    public function it_validates_artifact_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/game/artifacts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'rarity']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_artifact()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/game/artifacts/99999');

        $response->assertStatus(404);
    }
}

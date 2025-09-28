<?php

namespace Tests\Feature\Game;

use App\Models\Game\Artifact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $player;

    protected $artifact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = $this->user->player()->create([
            'name' => 'TestPlayer',
            'world_id' => 1,
        ]);

        $this->artifact = Artifact::factory()->create([
            'name' => 'Test Artifact',
            'type' => 'weapon',
            'rarity' => 'rare',
            'status' => 'inactive',
        ]);
    }

    public function test_can_get_artifacts()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/artifacts');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'rarity',
                            'status',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
                'message',
            ]);
    }

    public function test_can_get_artifact_details()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/api/game/artifacts/{$this->artifact->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'durability',
                ],
                'message',
            ]);
    }

    public function test_can_create_artifact()
    {
        $artifactData = [
            'name' => 'New Artifact',
            'description' => 'A test artifact',
            'type' => 'weapon',
            'rarity' => 'epic',
            'power_level' => 75,
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/game/artifacts', $artifactData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                    'status',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('artifacts', [
            'name' => 'New Artifact',
            'type' => 'weapon',
            'rarity' => 'epic',
        ]);
    }

    public function test_can_update_artifact()
    {
        $updateData = [
            'name' => 'Updated Artifact',
            'power_level' => 90,
        ];

        $response = $this
            ->actingAs($this->user)
            ->putJson("/api/game/artifacts/{$this->artifact->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'power_level',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $this->artifact->id,
            'name' => 'Updated Artifact',
            'power_level' => 90,
        ]);
    }

    public function test_can_delete_inactive_artifact()
    {
        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/game/artifacts/{$this->artifact->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('artifacts', [
            'id' => $this->artifact->id,
        ]);
    }

    public function test_cannot_delete_active_artifact()
    {
        $this->artifact->update(['status' => 'active']);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/game/artifacts/{$this->artifact->id}");

        $response
            ->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    public function test_can_get_server_wide_artifacts()
    {
        Artifact::factory()->create([
            'is_server_wide' => true,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/game/artifacts/server-wide');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_server_wide',
                        'status',
                    ],
                ],
                'message',
            ]);
    }

    public function test_can_generate_random_artifact()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/game/artifacts/generate-random', [
                'type' => 'weapon',
                'rarity' => 'rare',
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'rarity',
                ],
                'message',
            ]);
    }

    public function test_validation_errors_on_create()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/game/artifacts', []);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'name',
                    'type',
                    'rarity',
                ],
            ]);
    }

    public function test_validation_errors_on_update()
    {
        $response = $this
            ->actingAs($this->user)
            ->putJson("/api/game/artifacts/{$this->artifact->id}", [
                'power_level' => 150,  // Invalid: max is 100
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'power_level',
                ],
            ]);
    }
}

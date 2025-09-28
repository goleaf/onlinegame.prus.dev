<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Artifact;
use App\Models\Game\ArtifactEffect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_artifacts()
    {
        $user = User::factory()->create();
        Artifact::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/artifacts');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'type',
                        'rarity',
                        'status',
                        'power_level',
                        'durability',
                        'max_durability',
                        'is_server_wide',
                        'is_unique',
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
    public function it_can_get_specific_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/artifacts/{$artifact->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'type',
                'rarity',
                'status',
                'power_level',
                'durability',
                'max_durability',
                'effects',
                'requirements',
                'owner',
                'village',
                'artifact_effects',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_artifact()
    {
        $user = User::factory()->create();

        $artifactData = [
            'name' => 'Sword of Power',
            'description' => 'A legendary weapon',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'power_level' => 85,
            'effects' => [
                ['type' => 'combat_bonus', 'magnitude' => 25.0],
            ],
            'requirements' => [
                ['type' => 'player_level', 'value' => 50],
            ],
            'is_server_wide' => false,
            'is_unique' => true,
        ];

        $response = $this->actingAs($user)->post('/api/game/artifacts', $artifactData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'artifact' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'durability',
                    'max_durability',
                    'is_server_wide',
                    'is_unique',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('artifacts', [
            'name' => 'Sword of Power',
            'type' => 'weapon',
            'rarity' => 'legendary',
        ]);
    }

    /**
     * @test
     */
    public function it_can_activate_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $activationData = [
            'owner_id' => 1,
            'village_id' => 1,
        ];

        $response = $this->actingAs($user)->post("/api/game/artifacts/{$artifact->id}/activate", $activationData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'artifact' => [
                    'id',
                    'status',
                    'activated_at',
                    'owner_id',
                    'village_id',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_deactivate_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post("/api/game/artifacts/{$artifact->id}/deactivate");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'artifact' => [
                    'id',
                    'status',
                    'activated_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_server_wide_artifacts()
    {
        $user = User::factory()->create();
        Artifact::factory()->count(2)->create(['is_server_wide' => true, 'status' => 'active']);

        $response = $this->actingAs($user)->get('/api/game/artifacts/server-wide');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'rarity',
                        'status',
                        'power_level',
                        'is_server_wide',
                        'effects',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_random_artifact()
    {
        $user = User::factory()->create();

        $options = [
            'type' => 'weapon',
            'rarity' => 'rare',
            'power_level_min' => 20,
            'power_level_max' => 80,
        ];

        $response = $this->actingAs($user)->post('/api/game/artifacts/generate-random', $options);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'artifact' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'rarity',
                    'status',
                    'power_level',
                    'durability',
                    'max_durability',
                    'effects',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_effects()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create();
        ArtifactEffect::factory()->count(2)->create(['artifact_id' => $artifact->id]);

        $response = $this->actingAs($user)->get("/api/game/artifacts/{$artifact->id}/effects");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'artifact_id',
                        'effect_type',
                        'target_type',
                        'magnitude',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create();

        $updateData = [
            'name' => 'Enhanced Sword of Power',
            'power_level' => 90,
            'durability' => 85,
        ];

        $response = $this->actingAs($user)->put("/api/game/artifacts/{$artifact->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'artifact' => [
                    'id',
                    'name',
                    'power_level',
                    'durability',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id,
            'name' => 'Enhanced Sword of Power',
            'power_level' => 90,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->delete("/api/game/artifacts/{$artifact->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('artifacts', ['id' => $artifact->id]);
    }

    /**
     * @test
     */
    public function it_cannot_delete_active_artifact()
    {
        $user = User::factory()->create();
        $artifact = Artifact::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->delete("/api/game/artifacts/{$artifact->id}");

        $response
            ->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/artifacts');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_artifact_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/artifacts', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'rarity']);
    }

    /**
     * @test
     */
    public function it_can_filter_artifacts_by_type()
    {
        $user = User::factory()->create();
        Artifact::factory()->count(2)->create(['type' => 'weapon']);
        Artifact::factory()->count(1)->create(['type' => 'armor']);

        $response = $this->actingAs($user)->get('/api/game/artifacts?type=weapon');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_artifacts_by_rarity()
    {
        $user = User::factory()->create();
        Artifact::factory()->count(2)->create(['rarity' => 'legendary']);
        Artifact::factory()->count(1)->create(['rarity' => 'rare']);

        $response = $this->actingAs($user)->get('/api/game/artifacts?rarity=legendary');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}

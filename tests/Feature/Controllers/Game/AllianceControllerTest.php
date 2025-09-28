<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_alliances()
    {
        $user = User::factory()->create();
        Alliance::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/alliances');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tag',
                        'description',
                        'leader_id',
                        'members_count',
                        'points',
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
    public function it_can_get_specific_alliance()
    {
        $user = User::factory()->create();
        $alliance = Alliance::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/alliances/{$alliance->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'tag',
                'description',
                'leader_id',
                'members_count',
                'points',
                'leader',
                'members',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $allianceData = [
            'name' => 'Test Alliance',
            'tag' => 'TA',
            'description' => 'A test alliance',
        ];

        $response = $this->actingAs($user)->post('/api/game/alliances', $allianceData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'alliance' => [
                    'id',
                    'name',
                    'tag',
                    'description',
                    'leader_id',
                    'members_count',
                    'points',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('alliances', [
            'name' => 'Test Alliance',
            'tag' => 'TA',
        ]);
    }

    /**
     * @test
     */
    public function it_can_join_alliance()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $alliance = Alliance::factory()->create();

        $response = $this->actingAs($user)->post("/api/game/alliances/{$alliance->id}/join");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('alliance_members', [
            'alliance_id' => $alliance->id,
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_leave_alliance()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $alliance = Alliance::factory()->create();
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $player->id,
        ]);

        $response = $this->actingAs($user)->post('/api/game/alliances/leave');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_members()
    {
        $user = User::factory()->create();
        $alliance = Alliance::factory()->create();
        AllianceMember::factory()->count(3)->create(['alliance_id' => $alliance->id]);

        $response = $this->actingAs($user)->get("/api/game/alliances/{$alliance->id}/members");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'alliance_id',
                        'player_id',
                        'role',
                        'joined_at',
                        'player',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_wars()
    {
        $user = User::factory()->create();
        $alliance = Alliance::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/alliances/{$alliance->id}/wars");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'aggressor_alliance_id',
                        'defender_alliance_id',
                        'status',
                        'start_date',
                        'end_date',
                        'war_score',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_diplomacy()
    {
        $user = User::factory()->create();
        $alliance = Alliance::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/alliances/{$alliance->id}/diplomacy");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'alliance_id_1',
                        'alliance_id_2',
                        'status',
                        'type',
                        'initiated_at',
                        'expires_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_alliance()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $alliance = Alliance::factory()->create(['leader_id' => $player->id]);

        $updateData = [
            'name' => 'Updated Alliance Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($user)->put("/api/game/alliances/{$alliance->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'alliance' => [
                    'id',
                    'name',
                    'description',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('alliances', [
            'id' => $alliance->id,
            'name' => 'Updated Alliance Name',
        ]);
    }

    /**
     * @test
     */
    public function it_can_disband_alliance()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $alliance = Alliance::factory()->create(['leader_id' => $player->id]);

        $response = $this->actingAs($user)->delete("/api/game/alliances/{$alliance->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('alliances', ['id' => $alliance->id]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/alliances');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_alliance_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/alliances', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'tag']);
    }

    /**
     * @test
     */
    public function it_prevents_duplicate_alliance_names()
    {
        $user = User::factory()->create();
        $existingAlliance = Alliance::factory()->create(['name' => 'Existing Alliance']);

        $allianceData = [
            'name' => 'Existing Alliance',
            'tag' => 'EA',
            'description' => 'Duplicate name test',
        ];

        $response = $this->actingAs($user)->post('/api/game/alliances', $allianceData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}

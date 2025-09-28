<?php

namespace Tests\Feature\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class AllianceControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Player $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);
    }

    /**
     * @test
     */
    public function it_can_get_alliances()
    {
        // Create test alliances
        Alliance::factory()->count(3)->create();

        $response = $this->getJson('/api/game/alliances');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
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
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_filter_alliances_by_search()
    {
        Alliance::factory()->create(['name' => 'Knights of the Round Table']);
        Alliance::factory()->create(['name' => 'Dark Legion']);

        $response = $this->getJson('/api/game/alliances?search=Knights');

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_get_specific_alliance()
    {
        $alliance = Alliance::factory()->create();

        $response = $this->getJson("/api/game/alliances/{$alliance->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
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
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $allianceData = [
            'name' => 'Test Alliance',
            'tag' => '[TEST]',
            'description' => 'A test alliance',
        ];

        $response = $this->postJson('/api/game/alliances', $allianceData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'tag',
                    'description',
                    'leader_id',
                    'members_count',
                    'points',
                    'created_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('alliances', [
            'name' => 'Test Alliance',
            'tag' => '[TEST]',
            'leader_id' => $this->player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_validates_alliance_creation_data()
    {
        $response = $this->postJson('/api/game/alliances', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'tag']);
    }

    /**
     * @test
     */
    public function it_prevents_creating_alliance_when_player_already_in_one()
    {
        $existingAlliance = Alliance::factory()->create();
        $this->player->update(['alliance_id' => $existingAlliance->id]);

        $allianceData = [
            'name' => 'Test Alliance',
            'tag' => '[TEST]',
            'description' => 'A test alliance',
        ];

        $response = $this->postJson('/api/game/alliances', $allianceData);

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_join_alliance()
    {
        $alliance = Alliance::factory()->create(['members_count' => 1]);

        $response = $this->postJson("/api/game/alliances/{$alliance->id}/join");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('alliance_members', [
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_joining_alliance_when_already_in_one()
    {
        $existingAlliance = Alliance::factory()->create();
        $this->player->update(['alliance_id' => $existingAlliance->id]);

        $newAlliance = Alliance::factory()->create();

        $response = $this->postJson("/api/game/alliances/{$newAlliance->id}/join");

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_leave_alliance()
    {
        $alliance = Alliance::factory()->create();
        $this->player->update(['alliance_id' => $alliance->id]);

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->postJson('/api/game/alliances/leave');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNull($this->player->fresh()->alliance_id);
    }

    /**
     * @test
     */
    public function it_prevents_leader_from_leaving_alliance()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->player->id]);
        $this->player->update(['alliance_id' => $alliance->id]);

        $response = $this->postJson('/api/game/alliances/leave');

        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_alliance_members()
    {
        $alliance = Alliance::factory()->create();
        $member = Player::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'player_id' => $member->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->getJson("/api/game/alliances/{$alliance->id}/members");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'alliance_id',
                        'player_id',
                        'role',
                        'joined_at',
                        'player' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_update_alliance()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->player->id]);

        $updateData = [
            'name' => 'Updated Alliance Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/game/alliances/{$alliance->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'updated_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('alliances', [
            'id' => $alliance->id,
            'name' => 'Updated Alliance Name',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_non_leader_from_updating_alliance()
    {
        $alliance = Alliance::factory()->create();
        $otherPlayer = Player::factory()->create();
        $alliance->update(['leader_id' => $otherPlayer->id]);

        $updateData = [
            'name' => 'Updated Alliance Name',
        ];

        $response = $this->putJson("/api/game/alliances/{$alliance->id}", $updateData);

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_disband_alliance()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->player->id]);

        $response = $this->deleteJson("/api/game/alliances/{$alliance->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertSoftDeleted('alliances', ['id' => $alliance->id]);
    }

    /**
     * @test
     */
    public function it_prevents_non_leader_from_disbanding_alliance()
    {
        $alliance = Alliance::factory()->create();
        $otherPlayer = Player::factory()->create();
        $alliance->update(['leader_id' => $otherPlayer->id]);

        $response = $this->deleteJson("/api/game/alliances/{$alliance->id}");

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_uses_caching_for_alliances()
    {
        // Mock CachingUtil
        $this->mock(CachingUtil::class, function ($mock): void {
            $mock
                ->shouldReceive('remember')
                ->once()
                ->andReturn(collect([]));
        });

        $response = $this->getJson('/api/game/alliances');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_uses_rate_limiting_for_alliance_creation()
    {
        // Mock RateLimiterUtil
        $this->mock(RateLimiterUtil::class, function ($mock): void {
            $mock
                ->shouldReceive('attempt')
                ->once()
                ->andReturn(false);
        });

        $allianceData = [
            'name' => 'Test Alliance',
            'tag' => '[TEST]',
            'description' => 'A test alliance',
        ];

        $response = $this->postJson('/api/game/alliances', $allianceData);

        $response->assertStatus(429);
    }

    /**
     * @test
     */
    public function it_logs_alliance_operations()
    {
        // Mock LoggingUtil
        $this->mock(LoggingUtil::class, function ($mock): void {
            $mock
                ->shouldReceive('info')
                ->once();
        });

        $response = $this->getJson('/api/game/alliances');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_clears_cache_on_alliance_updates()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->player->id]);

        // Mock CachingUtil
        $this->mock(CachingUtil::class, function ($mock): void {
            $mock
                ->shouldReceive('forget')
                ->once();
        });

        $updateData = [
            'name' => 'Updated Alliance Name',
        ];

        $response = $this->putJson("/api/game/alliances/{$alliance->id}", $updateData);

        $response->assertStatus(200);
    }
}

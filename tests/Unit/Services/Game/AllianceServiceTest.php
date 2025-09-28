<?php

namespace Tests\Unit\Services\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceDiplomacy;
use App\Models\Game\AllianceMember;
use App\Models\Game\AllianceWar;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Services\Game\AllianceService;
use App\Services\Game\RealTimeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class AllianceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AllianceService $allianceService;

    private World $world;

    private Player $leader;

    private Player $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allianceService = new AllianceService();
        $this->world = World::factory()->create();
        $this->leader = Player::factory()->create(['world_id' => $this->world->id]);
        $this->player = Player::factory()->create(['world_id' => $this->world->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $name = 'Test Alliance';
        $description = 'Test Description';
        $settings = ['max_members' => 50];

        $alliance = $this->allianceService->createAlliance(
            $this->leader->id,
            $name,
            $description,
            $settings
        );

        $this->assertInstanceOf(Alliance::class, $alliance);
        $this->assertEquals($name, $alliance->name);
        $this->assertEquals($description, $alliance->description);
        $this->assertEquals($this->leader->id, $alliance->leader_id);
        $this->assertEquals(1, $alliance->member_count);
        $this->assertTrue($alliance->is_active);
        $this->assertNotNull($alliance->tag);

        // Check leader is added as member
        $this->assertDatabaseHas('alliance_members', [
            'alliance_id' => $alliance->id,
            'player_id' => $this->leader->id,
            'rank' => 'leader',
        ]);

        // Check player's alliance is updated
        $this->assertEquals($alliance->id, $this->leader->fresh()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_invite_player()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->leader->id]);
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->leader->id,
            'rank' => 'leader',
        ]);

        $result = $this->allianceService->invitePlayer(
            $alliance->id,
            $this->leader->id,
            $this->player->id,
            'Join our alliance!'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('alliance_members', [
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'pending',
            'invitation_message' => 'Join our alliance!',
            'invited_by' => $this->leader->id,
        ]);
    }

    /**
     * @test
     */
    public function it_cannot_invite_player_without_permission()
    {
        $alliance = Alliance::factory()->create();
        $member = Player::factory()->create();
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $member->id,
            'rank' => 'member',
        ]);

        $result = $this->allianceService->invitePlayer(
            $alliance->id,
            $member->id,
            $this->player->id
        );

        $this->assertFalse($result);
        $this->assertDatabaseMissing('alliance_members', [
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'pending',
        ]);
    }

    /**
     * @test
     */
    public function it_cannot_invite_player_already_in_alliance()
    {
        $alliance = Alliance::factory()->create(['leader_id' => $this->leader->id]);
        $otherAlliance = Alliance::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->leader->id,
            'rank' => 'leader',
        ]);

        $this->player->update(['alliance_id' => $otherAlliance->id]);

        $result = $this->allianceService->invitePlayer(
            $alliance->id,
            $this->leader->id,
            $this->player->id
        );

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_accept_invitation()
    {
        $alliance = Alliance::factory()->create(['member_count' => 1]);
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'pending',
            'joined_at' => null,
        ]);

        $result = $this->allianceService->acceptInvitation($this->player->id, $alliance->id);

        $this->assertTrue($result);

        $member = AllianceMember::where('alliance_id', $alliance->id)
            ->where('player_id', $this->player->id)
            ->first();

        $this->assertEquals('member', $member->rank);
        $this->assertNotNull($member->joined_at);
        $this->assertEquals($alliance->id, $this->player->fresh()->alliance_id);
        $this->assertEquals(2, $alliance->fresh()->member_count);
    }

    /**
     * @test
     */
    public function it_cannot_accept_nonexistent_invitation()
    {
        $alliance = Alliance::factory()->create();

        $result = $this->allianceService->acceptInvitation($this->player->id, $alliance->id);

        $this->assertFalse($result);
        $this->assertNull($this->player->fresh()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_remove_player()
    {
        $alliance = Alliance::factory()->create(['member_count' => 2]);

        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->leader->id,
            'rank' => 'leader',
        ]);

        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'member',
        ]);

        $this->player->update(['alliance_id' => $alliance->id]);

        $result = $this->allianceService->removePlayer(
            $alliance->id,
            $this->leader->id,
            $this->player->id,
            'Inactive player'
        );

        $this->assertTrue($result);
        $this->assertDatabaseMissing('alliance_members', ['id' => $member->id]);
        $this->assertNull($this->player->fresh()->alliance_id);
        $this->assertEquals(1, $alliance->fresh()->member_count);
    }

    /**
     * @test
     */
    public function it_cannot_remove_player_without_permission()
    {
        $alliance = Alliance::factory()->create();
        $member1 = Player::factory()->create();
        $member2 = Player::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $member1->id,
            'rank' => 'member',
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $member2->id,
            'rank' => 'member',
        ]);

        $result = $this->allianceService->removePlayer(
            $alliance->id,
            $member1->id,
            $member2->id
        );

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_declare_war()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();
        $reason = 'Territory dispute';

        $war = $this->allianceService->declareWar($alliance1->id, $alliance2->id, $reason);

        $this->assertInstanceOf(AllianceWar::class, $war);
        $this->assertEquals($alliance1->id, $war->attacker_alliance_id);
        $this->assertEquals($alliance2->id, $war->defender_alliance_id);
        $this->assertEquals($reason, $war->reason);
        $this->assertEquals('active', $war->status);
        $this->assertNotNull($war->declared_at);
        $this->assertEquals(0, $war->attacker_score);
        $this->assertEquals(0, $war->defender_score);
        $this->assertNotNull($war->reference_number);
    }

    /**
     * @test
     */
    public function it_cannot_declare_war_on_same_alliance()
    {
        $alliance = Alliance::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid alliance war declaration');

        $this->allianceService->declareWar($alliance->id, $alliance->id);
    }

    /**
     * @test
     */
    public function it_cannot_declare_war_on_nonexistent_alliance()
    {
        $alliance = Alliance::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid alliance war declaration');

        $this->allianceService->declareWar($alliance->id, 999);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_stats()
    {
        $alliance = Alliance::factory()->create();

        $member1 = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'population' => 1000,
            'village_count' => 5,
            'points' => 5000,
        ]);

        $member2 = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'population' => 2000,
            'village_count' => 8,
            'points' => 8000,
        ]);

        $war = AllianceWar::factory()->create([
            'attacker_alliance_id' => $alliance->id,
            'status' => 'active',
        ]);

        $stats = $this->allianceService->getAllianceStats($alliance->id);

        $this->assertIsArray($stats);
        $this->assertEquals($alliance->id, $stats['alliance']->id);
        $this->assertEquals(2, $stats['member_count']);
        $this->assertEquals(3000, $stats['total_population']);
        $this->assertEquals(13, $stats['total_villages']);
        $this->assertEquals(13000, $stats['total_points']);
        $this->assertEquals(6500, $stats['average_points']);
        $this->assertEquals(1, $stats['war_count']);
        $this->assertIsArray($stats['diplomatic_relations']);
        $this->assertIsInt($stats['rank']);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_for_nonexistent_alliance_stats()
    {
        $stats = $this->allianceService->getAllianceStats(999);
        $this->assertEquals([], $stats);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_rankings()
    {
        $alliance1 = Alliance::factory()->create(['is_active' => true, 'total_points' => 10000]);
        $alliance2 = Alliance::factory()->create(['is_active' => true, 'total_points' => 15000]);
        $alliance3 = Alliance::factory()->create(['is_active' => true, 'total_points' => 5000]);
        $alliance4 = Alliance::factory()->create(['is_active' => false, 'total_points' => 20000]);

        $rankings = $this->allianceService->getAllianceRankings(3);

        $this->assertCount(3, $rankings);
        $this->assertEquals(1, $rankings[0]['rank']);
        $this->assertEquals($alliance2->id, $rankings[0]['alliance']->id);
        $this->assertEquals(2, $rankings[1]['rank']);
        $this->assertEquals($alliance1->id, $rankings[1]['alliance']->id);
        $this->assertEquals(3, $rankings[2]['rank']);
        $this->assertEquals($alliance3->id, $rankings[2]['alliance']->id);
    }

    /**
     * @test
     */
    public function it_generates_unique_tag()
    {
        // Create alliance to test uniqueness
        Alliance::factory()->create(['tag' => 'ABC']);

        $alliance = $this->allianceService->createAlliance(
            $this->leader->id,
            'Test Alliance',
            'Description'
        );

        $this->assertNotEquals('ABC', $alliance->tag);
        $this->assertEquals(3, strlen($alliance->tag));
        $this->assertTrue(ctype_upper($alliance->tag));
    }

    /**
     * @test
     */
    public function it_handles_database_transaction_rollback_on_alliance_creation()
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->allianceService->createAlliance(
            $this->leader->id,
            'Test Alliance',
            'Description'
        );
    }

    /**
     * @test
     */
    public function it_handles_database_transaction_rollback_on_invitation_acceptance()
    {
        $alliance = Alliance::factory()->create();
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'pending',
        ]);

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->allianceService->acceptInvitation($this->player->id, $alliance->id);
    }

    /**
     * @test
     */
    public function it_handles_database_transaction_rollback_on_player_removal()
    {
        $alliance = Alliance::factory()->create();
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->leader->id,
            'rank' => 'leader',
        ]);
        AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'member',
        ]);

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->allianceService->removePlayer(
            $alliance->id,
            $this->leader->id,
            $this->player->id
        );
    }

    /**
     * @test
     */
    public function it_handles_database_transaction_rollback_on_war_declaration()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->allianceService->declareWar($alliance1->id, $alliance2->id);
    }

    /**
     * @test
     */
    public function it_handles_broadcast_errors_gracefully()
    {
        Log::shouldReceive('error')->once();

        // Mock RealTimeGameService to throw exception
        $mock = Mockery::mock('alias:'.RealTimeGameService::class);
        $mock
            ->shouldReceive('broadcastUpdate')
            ->once()
            ->andThrow(new \Exception('Broadcast error'));

        $alliance = $this->allianceService->createAlliance(
            $this->leader->id,
            'Test Alliance',
            'Description'
        );

        // Alliance should still be created despite broadcast error
        $this->assertInstanceOf(Alliance::class, $alliance);
    }

    /**
     * @test
     */
    public function it_can_check_invitation_permissions()
    {
        $alliance = Alliance::factory()->create();

        // Leader can invite
        $leader = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'leader',
        ]);

        // Co-leader can invite
        $coLeader = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'co_leader',
        ]);

        // Elder can invite
        $elder = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'elder',
        ]);

        // Member cannot invite
        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'member',
        ]);

        $reflection = new \ReflectionClass($this->allianceService);
        $method = $reflection->getMethod('canInvitePlayers');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->allianceService, $leader));
        $this->assertTrue($method->invoke($this->allianceService, $coLeader));
        $this->assertTrue($method->invoke($this->allianceService, $elder));
        $this->assertFalse($method->invoke($this->allianceService, $member));
    }

    /**
     * @test
     */
    public function it_can_check_removal_permissions()
    {
        $alliance = Alliance::factory()->create();

        $leader = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'leader',
        ]);

        $elder = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'elder',
        ]);

        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'rank' => 'member',
        ]);

        $reflection = new \ReflectionClass($this->allianceService);
        $method = $reflection->getMethod('canRemovePlayers');
        $method->setAccessible(true);

        // Leader can remove anyone
        $this->assertTrue($method->invoke($this->allianceService, $leader, $elder));
        $this->assertTrue($method->invoke($this->allianceService, $leader, $member));

        // Elder can only remove members
        $this->assertFalse($method->invoke($this->allianceService, $elder, $leader));
        $this->assertTrue($method->invoke($this->allianceService, $elder, $member));

        // Member cannot remove anyone
        $this->assertFalse($method->invoke($this->allianceService, $member, $leader));
        $this->assertFalse($method->invoke($this->allianceService, $member, $elder));
    }

    /**
     * @test
     */
    public function it_can_get_diplomatic_relations()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();
        $alliance3 = Alliance::factory()->create();

        // Alliance1 has relations with Alliance2 and Alliance3
        AllianceDiplomacy::factory()->create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'relation_type' => 'ally',
        ]);

        AllianceDiplomacy::factory()->create([
            'alliance_id' => $alliance3->id,
            'target_alliance_id' => $alliance1->id,
            'relation_type' => 'enemy',
        ]);

        $reflection = new \ReflectionClass($this->allianceService);
        $method = $reflection->getMethod('getDiplomaticRelations');
        $method->setAccessible(true);

        $relations = $method->invoke($this->allianceService, $alliance1->id);

        $this->assertCount(2, $relations);
        $this->assertTrue($relations[0]['is_initiator']);
        $this->assertFalse($relations[1]['is_initiator']);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_rank()
    {
        $alliance1 = Alliance::factory()->create(['is_active' => true, 'total_points' => 1000]);
        $alliance2 = Alliance::factory()->create(['is_active' => true, 'total_points' => 2000]);
        $alliance3 = Alliance::factory()->create(['is_active' => true, 'total_points' => 3000]);
        $alliance4 = Alliance::factory()->create(['is_active' => false, 'total_points' => 4000]);

        $reflection = new \ReflectionClass($this->allianceService);
        $method = $reflection->getMethod('getAllianceRank');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke($this->allianceService, $alliance3->id));
        $this->assertEquals(2, $method->invoke($this->allianceService, $alliance2->id));
        $this->assertEquals(3, $method->invoke($this->allianceService, $alliance1->id));
        $this->assertEquals(0, $method->invoke($this->allianceService, 999));  // Non-existent
    }
}

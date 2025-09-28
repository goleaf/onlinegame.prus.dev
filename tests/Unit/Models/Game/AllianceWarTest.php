<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceWar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceWarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance_war()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => true,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => ['capture_territory', 'destroy_army'],
            'metadata' => ['source' => 'diplomacy', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliance_wars', [
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = new AllianceWar([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'ideological',
            'status' => 'pending',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Ideological differences',
            'casus_belli' => 'ideological_conflict',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => true,
            'peace_offers' => [],
            'war_goals' => ['spread_ideology', 'convert_population'],
            'metadata' => ['source' => 'ideology', 'version' => '1.1'],
        ]);

        $this->assertEquals($alliance1->id, $war->attacker_alliance_id);
        $this->assertEquals('ideological', $war->war_type);
        $this->assertEquals('pending', $war->status);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'test',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Test war',
            'casus_belli' => 'test',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => true,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($war->is_justified);
        $this->assertFalse($war->is_automatic);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'test',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => now()->addDays(30),
            'reason' => 'Test war',
            'casus_belli' => 'test',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $war->started_at);
        $this->assertInstanceOf('Carbon\Carbon', $war->ended_at);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'test',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Test war',
            'casus_belli' => 'test',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => ['offer1', 'offer2'],
            'war_goals' => ['goal1', 'goal2'],
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($war->peace_offers);
        $this->assertIsArray($war->war_goals);
        $this->assertIsArray($war->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_wars_by_attacker()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();
        $alliance3 = Alliance::factory()->create();

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        AllianceWar::create([
            'attacker_alliance_id' => $alliance3->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'ideological',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Ideological conflict',
            'casus_belli' => 'ideological_conflict',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $alliance1Wars = AllianceWar::byAttacker($alliance1->id)->get();
        $this->assertCount(1, $alliance1Wars);
        $this->assertEquals($alliance1->id, $alliance1Wars->first()->attacker_alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_wars_by_defender()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();
        $alliance3 = Alliance::factory()->create();

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance3->id,
            'war_type' => 'ideological',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Ideological conflict',
            'casus_belli' => 'ideological_conflict',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $alliance2Wars = AllianceWar::byDefender($alliance2->id)->get();
        $this->assertCount(1, $alliance2Wars);
        $this->assertEquals($alliance2->id, $alliance2Wars->first()->defender_alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_wars_by_type()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'ideological',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Ideological conflict',
            'casus_belli' => 'ideological_conflict',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $territorialWars = AllianceWar::byType('territorial')->get();
        $this->assertCount(1, $territorialWars);
        $this->assertEquals('territorial', $territorialWars->first()->war_type);
    }

    /**
     * @test
     */
    public function it_can_scope_wars_by_status()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'ideological',
            'status' => 'ended',
            'started_at' => now()->subDays(30),
            'ended_at' => now(),
            'reason' => 'Ideological conflict',
            'casus_belli' => 'ideological_conflict',
            'war_score' => 100,
            'attacker_war_score' => 60,
            'defender_war_score' => 40,
            'battles_fought' => 10,
            'attacker_victories' => 6,
            'defender_victories' => 4,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $activeWars = AllianceWar::byStatus('active')->get();
        $this->assertCount(1, $activeWars);
        $this->assertEquals('active', $activeWars->first()->status);
    }

    /**
     * @test
     */
    public function it_can_get_attacker_alliance_relationship()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'test',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Test war',
            'casus_belli' => 'test',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $war->attackerAlliance());
        $this->assertEquals($alliance1->id, $war->attackerAlliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_defender_alliance_relationship()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'test',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Test war',
            'casus_belli' => 'test',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $war->defenderAlliance());
        $this->assertEquals($alliance2->id, $war->defenderAlliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_war_summary()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $summary = $war->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('territorial', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_war_details()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $details = $war->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('war_type', $details);
        $this->assertArrayHasKey('status', $details);
    }

    /**
     * @test
     */
    public function it_can_get_war_statistics()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $stats = $war->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('war_score', $stats);
        $this->assertArrayHasKey('battles_fought', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_war_timeline()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $war = AllianceWar::create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
            'war_type' => 'territorial',
            'status' => 'active',
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'Territorial dispute',
            'casus_belli' => 'border_incident',
            'war_score' => 0,
            'attacker_war_score' => 0,
            'defender_war_score' => 0,
            'battles_fought' => 0,
            'attacker_victories' => 0,
            'defender_victories' => 0,
            'is_justified' => false,
            'is_automatic' => false,
            'peace_offers' => [],
            'war_goals' => [],
            'metadata' => [],
        ]);

        $timeline = $war->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('started_at', $timeline);
        $this->assertArrayHasKey('ended_at', $timeline);
    }
}

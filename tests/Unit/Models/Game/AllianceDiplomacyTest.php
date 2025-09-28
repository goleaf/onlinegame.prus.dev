<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceDiplomacy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceDiplomacyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance_diplomacy()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => ['duration' => 30, 'conditions' => ['no_war']],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => true,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty between alliances',
            'metadata' => ['source' => 'diplomacy', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliance_diplomacy', [
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
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

        $diplomacy = new AllianceDiplomacy([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'trade_agreement',
            'status' => 'pending',
            'terms' => ['duration' => 60, 'conditions' => ['mutual_trade']],
            'proposed_by' => $alliance1->id,
            'accepted_by' => null,
            'proposed_at' => now(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(60),
            'is_public' => false,
            'is_automatic' => true,
            'priority' => 2,
            'description' => 'Trade agreement between alliances',
            'metadata' => ['source' => 'trade', 'version' => '1.1'],
        ]);

        $this->assertEquals($alliance1->id, $diplomacy->alliance_id);
        $this->assertEquals('trade_agreement', $diplomacy->diplomacy_type);
        $this->assertEquals('pending', $diplomacy->status);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'test_diplomacy',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => true,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Test diplomacy',
            'metadata' => [],
        ]);

        $this->assertTrue($diplomacy->is_public);
        $this->assertFalse($diplomacy->is_automatic);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'test_diplomacy',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Test diplomacy',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $diplomacy->proposed_at);
        $this->assertInstanceOf('Carbon\Carbon', $diplomacy->accepted_at);
        $this->assertInstanceOf('Carbon\Carbon', $diplomacy->expires_at);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'test_diplomacy',
            'status' => 'active',
            'terms' => ['duration' => 30, 'conditions' => ['no_war', 'mutual_trade']],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Test diplomacy',
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($diplomacy->terms);
        $this->assertIsArray($diplomacy->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_diplomacy_by_alliance()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();
        $alliance3 = Alliance::factory()->create();

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty',
            'metadata' => [],
        ]);

        AllianceDiplomacy::create([
            'alliance_id' => $alliance3->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'trade_agreement',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance3->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(60),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Trade agreement',
            'metadata' => [],
        ]);

        $alliance1Diplomacy = AllianceDiplomacy::byAlliance($alliance1->id)->get();
        $this->assertCount(1, $alliance1Diplomacy);
        $this->assertEquals($alliance1->id, $alliance1Diplomacy->first()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_diplomacy_by_type()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty',
            'metadata' => [],
        ]);

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'trade_agreement',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(60),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Trade agreement',
            'metadata' => [],
        ]);

        $peaceTreaties = AllianceDiplomacy::byType('peace_treaty')->get();
        $this->assertCount(1, $peaceTreaties);
        $this->assertEquals('peace_treaty', $peaceTreaties->first()->diplomacy_type);
    }

    /**
     * @test
     */
    public function it_can_scope_diplomacy_by_status()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty',
            'metadata' => [],
        ]);

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'trade_agreement',
            'status' => 'pending',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => null,
            'proposed_at' => now(),
            'accepted_at' => null,
            'expires_at' => now()->addDays(60),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Trade agreement',
            'metadata' => [],
        ]);

        $activeDiplomacy = AllianceDiplomacy::byStatus('active')->get();
        $this->assertCount(1, $activeDiplomacy);
        $this->assertEquals('active', $activeDiplomacy->first()->status);
    }

    /**
     * @test
     */
    public function it_can_scope_public_diplomacy()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => true,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty',
            'metadata' => [],
        ]);

        AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'trade_agreement',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(60),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Trade agreement',
            'metadata' => [],
        ]);

        $publicDiplomacy = AllianceDiplomacy::public()->get();
        $this->assertCount(1, $publicDiplomacy);
        $this->assertTrue($publicDiplomacy->first()->is_public);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_relationship()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'test_diplomacy',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Test diplomacy',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $diplomacy->alliance());
        $this->assertEquals($alliance1->id, $diplomacy->alliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_target_alliance_relationship()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'test_diplomacy',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Test diplomacy',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $diplomacy->targetAlliance());
        $this->assertEquals($alliance2->id, $diplomacy->targetAlliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_diplomacy_summary()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty between alliances',
            'metadata' => [],
        ]);

        $summary = $diplomacy->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('peace_treaty', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_diplomacy_details()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty between alliances',
            'metadata' => [],
        ]);

        $details = $diplomacy->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('diplomacy_type', $details);
        $this->assertArrayHasKey('status', $details);
    }

    /**
     * @test
     */
    public function it_can_get_diplomacy_statistics()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty between alliances',
            'metadata' => [],
        ]);

        $stats = $diplomacy->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('priority', $stats);
        $this->assertArrayHasKey('status', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_diplomacy_timeline()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $diplomacy = AllianceDiplomacy::create([
            'alliance_id' => $alliance1->id,
            'target_alliance_id' => $alliance2->id,
            'diplomacy_type' => 'peace_treaty',
            'status' => 'active',
            'terms' => [],
            'proposed_by' => $alliance1->id,
            'accepted_by' => $alliance2->id,
            'proposed_at' => now(),
            'accepted_at' => now(),
            'expires_at' => now()->addDays(30),
            'is_public' => false,
            'is_automatic' => false,
            'priority' => 1,
            'description' => 'Peace treaty between alliances',
            'metadata' => [],
        ]);

        $timeline = $diplomacy->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('proposed_at', $timeline);
        $this->assertArrayHasKey('accepted_at', $timeline);
        $this->assertArrayHasKey('expires_at', $timeline);
    }
}

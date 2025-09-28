<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance_log()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
            'data' => ['member_id' => 123, 'member_name' => 'John Doe'],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => ['source' => 'web', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliance_logs', [
            'alliance_id' => $alliance->id,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $alliance = Alliance::factory()->create();

        $log = new AllianceLog([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'action' => 'member_left',
            'description' => 'Member left the alliance',
            'data' => ['member_id' => 456, 'member_name' => 'Jane Smith'],
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Chrome/91.0',
            'metadata' => ['source' => 'mobile', 'version' => '1.1'],
        ]);

        $this->assertEquals($alliance->id, $log->alliance_id);
        $this->assertEquals('member_left', $log->action);
        $this->assertEquals('Member left the alliance', $log->description);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'test_action',
            'description' => 'Test log entry',
            'data' => ['key1' => 'value1', 'key2' => 'value2'],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($log->data);
        $this->assertIsArray($log->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_logs_by_alliance()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceLog::create([
            'alliance_id' => $alliance1->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'Member joined alliance 1',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        AllianceLog::create([
            'alliance_id' => $alliance2->id,
            'user_id' => 2,
            'action' => 'member_joined',
            'description' => 'Member joined alliance 2',
            'data' => [],
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Chrome/91.0',
            'metadata' => [],
        ]);

        $alliance1Logs = AllianceLog::byAlliance($alliance1->id)->get();
        $this->assertCount(1, $alliance1Logs);
        $this->assertEquals($alliance1->id, $alliance1Logs->first()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_logs_by_action()
    {
        $alliance = Alliance::factory()->create();

        AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'Member joined',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'action' => 'member_left',
            'description' => 'Member left',
            'data' => [],
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Chrome/91.0',
            'metadata' => [],
        ]);

        $joinedLogs = AllianceLog::byAction('member_joined')->get();
        $this->assertCount(1, $joinedLogs);
        $this->assertEquals('member_joined', $joinedLogs->first()->action);
    }

    /**
     * @test
     */
    public function it_can_scope_logs_by_user()
    {
        $alliance = Alliance::factory()->create();

        AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'User 1 action',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'action' => 'member_left',
            'description' => 'User 2 action',
            'data' => [],
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Chrome/91.0',
            'metadata' => [],
        ]);

        $user1Logs = AllianceLog::byUser(1)->get();
        $this->assertCount(1, $user1Logs);
        $this->assertEquals(1, $user1Logs->first()->user_id);
    }

    /**
     * @test
     */
    public function it_can_scope_logs_by_date_range()
    {
        $alliance = Alliance::factory()->create();

        AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'Member joined',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $recentLogs = AllianceLog::byDateRange(now()->subDay(), now()->addDay())->get();
        $this->assertCount(1, $recentLogs);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_relationship()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'test_action',
            'description' => 'Test log entry',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $log->alliance());
        $this->assertEquals($alliance->id, $log->alliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_log_summary()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $summary = $log->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('member_joined', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_log_details()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $details = $log->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('action', $details);
        $this->assertArrayHasKey('description', $details);
    }

    /**
     * @test
     */
    public function it_can_get_log_statistics()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $stats = $log->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('action', $stats);
        $this->assertArrayHasKey('user_id', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_log_timeline()
    {
        $alliance = Alliance::factory()->create();

        $log = AllianceLog::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'action' => 'member_joined',
            'description' => 'New member joined the alliance',
            'data' => [],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'metadata' => [],
        ]);

        $timeline = $log->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('created_at', $timeline);
        $this->assertArrayHasKey('updated_at', $timeline);
    }
}

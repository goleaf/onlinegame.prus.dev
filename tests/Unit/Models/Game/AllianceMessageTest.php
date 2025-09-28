<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance_message()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
            'content' => 'We need to discuss our strategy for the upcoming war.',
            'is_important' => true,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => ['source' => 'web', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliance_messages', [
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $alliance = Alliance::factory()->create();

        $message = new AllianceMessage([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'message_type' => 'announcement',
            'subject' => 'Important Announcement',
            'content' => 'This is an important announcement for all members.',
            'is_important' => true,
            'is_pinned' => true,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => ['source' => 'mobile', 'version' => '1.1'],
        ]);

        $this->assertEquals($alliance->id, $message->alliance_id);
        $this->assertEquals('announcement', $message->message_type);
        $this->assertEquals('Important Announcement', $message->subject);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'test',
            'subject' => 'Test Message',
            'content' => 'This is a test message.',
            'is_important' => true,
            'is_pinned' => false,
            'is_edited' => true,
            'edited_at' => now(),
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($message->is_important);
        $this->assertFalse($message->is_pinned);
        $this->assertTrue($message->is_edited);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'test',
            'subject' => 'Test Message',
            'content' => 'This is a test message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => true,
            'edited_at' => now(),
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $message->edited_at);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'test',
            'subject' => 'Test Message',
            'content' => 'This is a test message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => ['file1.pdf', 'image1.jpg'],
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($message->attachments);
        $this->assertIsArray($message->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_messages_by_alliance()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceMessage::create([
            'alliance_id' => $alliance1->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance 1 Message',
            'content' => 'Message for alliance 1',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        AllianceMessage::create([
            'alliance_id' => $alliance2->id,
            'user_id' => 2,
            'message_type' => 'general',
            'subject' => 'Alliance 2 Message',
            'content' => 'Message for alliance 2',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $alliance1Messages = AllianceMessage::byAlliance($alliance1->id)->get();
        $this->assertCount(1, $alliance1Messages);
        $this->assertEquals($alliance1->id, $alliance1Messages->first()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_messages_by_type()
    {
        $alliance = Alliance::factory()->create();

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'General Message',
            'content' => 'This is a general message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'message_type' => 'announcement',
            'subject' => 'Announcement Message',
            'content' => 'This is an announcement.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $generalMessages = AllianceMessage::byType('general')->get();
        $this->assertCount(1, $generalMessages);
        $this->assertEquals('general', $generalMessages->first()->message_type);
    }

    /**
     * @test
     */
    public function it_can_scope_messages_by_user()
    {
        $alliance = Alliance::factory()->create();

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'User 1 Message',
            'content' => 'Message from user 1',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'message_type' => 'general',
            'subject' => 'User 2 Message',
            'content' => 'Message from user 2',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $user1Messages = AllianceMessage::byUser(1)->get();
        $this->assertCount(1, $user1Messages);
        $this->assertEquals(1, $user1Messages->first()->user_id);
    }

    /**
     * @test
     */
    public function it_can_scope_important_messages()
    {
        $alliance = Alliance::factory()->create();

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Important Message',
            'content' => 'This is an important message.',
            'is_important' => true,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'message_type' => 'general',
            'subject' => 'Regular Message',
            'content' => 'This is a regular message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $importantMessages = AllianceMessage::important()->get();
        $this->assertCount(1, $importantMessages);
        $this->assertTrue($importantMessages->first()->is_important);
    }

    /**
     * @test
     */
    public function it_can_scope_pinned_messages()
    {
        $alliance = Alliance::factory()->create();

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Pinned Message',
            'content' => 'This is a pinned message.',
            'is_important' => false,
            'is_pinned' => true,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'message_type' => 'general',
            'subject' => 'Regular Message',
            'content' => 'This is a regular message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $pinnedMessages = AllianceMessage::pinned()->get();
        $this->assertCount(1, $pinnedMessages);
        $this->assertTrue($pinnedMessages->first()->is_pinned);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_relationship()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'test',
            'subject' => 'Test Message',
            'content' => 'This is a test message.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $message->alliance());
        $this->assertEquals($alliance->id, $message->alliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_message_summary()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
            'content' => 'We need to discuss our strategy for the upcoming war.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $summary = $message->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('Alliance Meeting', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_message_details()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
            'content' => 'We need to discuss our strategy for the upcoming war.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $details = $message->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('subject', $details);
        $this->assertArrayHasKey('content', $details);
    }

    /**
     * @test
     */
    public function it_can_get_message_statistics()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
            'content' => 'We need to discuss our strategy for the upcoming war.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $stats = $message->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('message_type', $stats);
        $this->assertArrayHasKey('is_important', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_message_timeline()
    {
        $alliance = Alliance::factory()->create();

        $message = AllianceMessage::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'message_type' => 'general',
            'subject' => 'Alliance Meeting',
            'content' => 'We need to discuss our strategy for the upcoming war.',
            'is_important' => false,
            'is_pinned' => false,
            'is_edited' => false,
            'edited_at' => null,
            'reply_to' => null,
            'thread_id' => null,
            'attachments' => [],
            'metadata' => [],
        ]);

        $timeline = $message->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('created_at', $timeline);
        $this->assertArrayHasKey('updated_at', $timeline);
    }
}

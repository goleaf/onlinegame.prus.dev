<?php

namespace Tests\Feature\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->messageService = app(MessageService::class);

        $this->actingAs($this->user);
    }

    /**
     * @test
     */
    public function it_can_get_inbox_messages()
    {
        // Create test messages
        $sender = Player::factory()->create();
        Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
            'subject' => 'Test Message',
            'body' => 'Test body content',
        ]);

        $response = $this->getJson('/api/game/messages/inbox');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sender_id',
                        'recipient_id',
                        'subject',
                        'body',
                        'created_at',
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_sent_messages()
    {
        // Create test messages
        $recipient = Player::factory()->create();
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $recipient->id,
            'subject' => 'Sent Message',
            'body' => 'Sent body content',
        ]);

        $response = $this->getJson('/api/game/messages/sent');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sender_id',
                        'recipient_id',
                        'subject',
                        'body',
                        'created_at',
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_send_private_message()
    {
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => 'Test Subject',
            'body' => 'Test message body',
            'priority' => 'normal',
        ];

        $response = $this->postJson('/api/game/messages/send', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sender_id',
                    'recipient_id',
                    'subject',
                    'body',
                    'priority',
                    'created_at',
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->player->id,
            'recipient_id' => $recipient->id,
            'subject' => 'Test Subject',
        ]);
    }

    /**
     * @test
     */
    public function it_validates_message_data()
    {
        $response = $this->postJson('/api/game/messages/send', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id', 'subject', 'body', 'priority']);
    }

    /**
     * @test
     */
    public function it_can_mark_message_as_read()
    {
        $sender = Player::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
            'is_read' => false,
        ]);

        $response = $this->putJson("/api/game/messages/{$message->id}/read");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_message()
    {
        $sender = Player::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
        ]);

        $response = $this->deleteJson("/api/game/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertSoftDeleted('messages', ['id' => $message->id]);
    }

    /**
     * @test
     */
    public function it_can_get_message_statistics()
    {
        // Create test messages
        $sender = Player::factory()->create();
        Message::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
        ]);

        $response = $this->getJson('/api/game/messages/stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_messages',
                    'unread_messages',
                    'sent_messages',
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_bulk_mark_messages_as_read()
    {
        $sender = Player::factory()->create();
        $messages = Message::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
            'is_read' => false,
        ]);

        $messageIds = $messages->pluck('id')->toArray();

        $response = $this->putJson('/api/game/messages/bulk-read', [
            'message_ids' => $messageIds,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated_count',
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(3, $response->json('data.updated_count'));
    }

    /**
     * @test
     */
    public function it_can_bulk_delete_messages()
    {
        $sender = Player::factory()->create();
        $messages = Message::factory()->count(3)->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
        ]);

        $messageIds = $messages->pluck('id')->toArray();

        $response = $this->deleteJson('/api/game/messages/bulk-delete', [
            'message_ids' => $messageIds,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'deleted_count',
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(3, $response->json('data.deleted_count'));
    }

    /**
     * @test
     */
    public function it_can_get_players_for_message_composition()
    {
        // Create additional players
        $otherPlayer = Player::factory()->create();

        $response = $this->getJson('/api/game/messages/players');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_get_specific_message()
    {
        $sender = Player::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $this->player->id,
        ]);

        $response = $this->getJson("/api/game/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sender_id',
                    'recipient_id',
                    'subject',
                    'body',
                    'created_at',
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_prevents_access_to_unauthorized_messages()
    {
        $otherPlayer = Player::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $otherPlayer->id,
            'recipient_id' => $otherPlayer->id,  // Different player
        ]);

        $response = $this->getJson("/api/game/messages/{$message->id}");

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_uses_caching_for_inbox_messages()
    {
        // Mock CachingUtil
        $this->mock(CachingUtil::class, function ($mock) {
            $mock
                ->shouldReceive('remember')
                ->once()
                ->andReturn(collect([]));
        });

        $response = $this->getJson('/api/game/messages/inbox');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_uses_rate_limiting_for_sending_messages()
    {
        // Mock RateLimiterUtil
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock
                ->shouldReceive('attempt')
                ->once()
                ->andReturn(false);
        });

        $recipient = Player::factory()->create();
        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => 'Test Subject',
            'body' => 'Test message body',
            'priority' => 'normal',
        ];

        $response = $this->postJson('/api/game/messages/send', $messageData);

        $response->assertStatus(429);
    }

    /**
     * @test
     */
    public function it_logs_message_operations()
    {
        // Mock LoggingUtil
        $this->mock(LoggingUtil::class, function ($mock) {
            $mock
                ->shouldReceive('info')
                ->once();
        });

        $response = $this->getJson('/api/game/messages/inbox');

        $response->assertStatus(200);
    }
}

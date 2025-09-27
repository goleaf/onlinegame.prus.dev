<?php

namespace Tests\Feature\Game;

use App\Models\Game\ChatChannel;
use App\Models\Game\ChatMessage;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected World $world;
    protected ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->world = World::factory()->create(['is_active' => true]);
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'is_active' => true,
        ]);

        $this->channel = ChatChannel::factory()->create([
            'world_id' => $this->world->id,
            'channel_type' => 'global',
        ]);

        // Mock rate limiter
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock->shouldReceive('attempt')->andReturn(true);
        });
    }

    /**
     * @test
     */
    public function it_can_get_channel_messages()
    {
        // Create test messages
        ChatMessage::factory()->count(5)->create([
            'channel_id' => $this->channel->id,
            'player_id' => $this->player->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'channel_id',
                        'message',
                        'message_type',
                        'created_at',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_messages_by_type()
    {
        // Create messages of different types
        ChatMessage::factory()->create([
            'channel_type' => 'global',
            'player_id' => $this->player->id,
        ]);

        ChatMessage::factory()->create([
            'channel_type' => 'alliance',
            'player_id' => $this->player->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/chat/messages/global');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'channel_type',
                        'message',
                        'message_type',
                        'created_at',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_message()
    {
        $messageData = [
            'channel_id' => $this->channel->id,
            'channel_type' => 'global',
            'message' => 'Hello, world!',
            'message_type' => 'text',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/messages', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_id' => $this->channel->id,
            'channel_type' => 'global',
            'message' => 'Hello, world!',
            'message_type' => 'text',
        ]);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_when_sending_message()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/messages', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'channel_type',
                'message',
                'message_type',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_global_message()
    {
        $messageData = [
            'message' => 'Global announcement!',
            'message_type' => 'announcement',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/global', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_type' => 'global',
            'message' => 'Global announcement!',
            'message_type' => 'announcement',
        ]);
    }

    /**
     * @test
     */
    public function it_can_send_alliance_message()
    {
        // Set player alliance
        $this->player->update(['alliance_id' => 1]);

        $messageData = [
            'message' => 'Alliance message!',
            'message_type' => 'text',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/alliance', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_type' => 'alliance',
            'message' => 'Alliance message!',
            'message_type' => 'text',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_sending_alliance_message_without_alliance()
    {
        // Player has no alliance
        $this->player->update(['alliance_id' => null]);

        $messageData = [
            'message' => 'Alliance message!',
            'message_type' => 'text',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/alliance', $messageData);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Player is not in an alliance',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_private_message()
    {
        // Create another player
        $otherUser = User::factory()->create();
        $otherPlayer = Player::factory()->create([
            'user_id' => $otherUser->id,
            'world_id' => $this->world->id,
        ]);

        $messageData = [
            'recipient_id' => $otherPlayer->id,
            'message' => 'Private message!',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/private', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_type' => 'private',
            'message' => 'Private message!',
            'message_type' => 'text',
        ]);
    }

    /**
     * @test
     */
    public function it_validates_recipient_exists_for_private_message()
    {
        $messageData = [
            'recipient_id' => 99999,  // Non-existent player
            'message' => 'Private message!',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/private', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id']);
    }

    /**
     * @test
     */
    public function it_can_send_trade_message()
    {
        $messageData = [
            'message' => 'Trading resources!',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/trade', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_type' => 'trade',
            'message' => 'Trading resources!',
            'message_type' => 'text',
        ]);
    }

    /**
     * @test
     */
    public function it_can_send_diplomacy_message()
    {
        $messageData = [
            'message' => 'Diplomatic message!',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/diplomacy', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'channel_type',
                    'message',
                    'message_type',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'channel_type' => 'diplomacy',
            'message' => 'Diplomatic message!',
            'message_type' => 'text',
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_message()
    {
        $message = ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'channel_id' => $this->channel->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/game/api/chat/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Message deleted successfully.',
            ]);

        $this->assertSoftDeleted('chat_messages', [
            'id' => $message->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_available_channels()
    {
        // Create multiple channels
        ChatChannel::factory()->count(3)->create([
            'world_id' => $this->world->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/chat/channels');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'channel_type',
                        'world_id',
                        'is_active',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_channel_statistics()
    {
        // Create messages for the channel
        ChatMessage::factory()->count(10)->create([
            'channel_id' => $this->channel->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/stats");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'channel_id',
                    'total_messages',
                    'active_users',
                    'last_message_at',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_search_messages()
    {
        // Create messages with different content
        ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'message' => 'Hello world!',
            'channel_type' => 'global',
        ]);

        ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'message' => 'Goodbye world!',
            'channel_type' => 'global',
        ]);

        ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'message' => 'Hello there!',
            'channel_type' => 'alliance',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/chat/search?query=Hello');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'message',
                        'channel_type',
                        'created_at',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_search_results_by_channel_type()
    {
        // Create messages in different channels
        ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'message' => 'Hello world!',
            'channel_type' => 'global',
        ]);

        ChatMessage::factory()->create([
            'player_id' => $this->player->id,
            'message' => 'Hello alliance!',
            'channel_type' => 'alliance',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/chat/search?query=Hello&channel_type=global');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.channel_type', 'global');
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * @test
     */
    public function it_can_get_message_statistics()
    {
        // Create messages of different types
        ChatMessage::factory()->count(5)->create(['message_type' => 'text']);
        ChatMessage::factory()->count(3)->create(['message_type' => 'system']);
        ChatMessage::factory()->count(2)->create(['message_type' => 'announcement']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/chat/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_messages',
                    'messages_by_type',
                    'messages_by_channel',
                    'active_users',
                    'recent_activity',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_respects_rate_limiting()
    {
        // Mock rate limiter to return false
        $this->mock(RateLimiterUtil::class, function ($mock) {
            $mock->shouldReceive('attempt')->andReturn(false);
        });

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ]);
    }

    /**
     * @test
     */
    public function it_uses_caching_for_channel_messages()
    {
        ChatMessage::factory()->count(3)->create([
            'channel_id' => $this->channel->id,
        ]);

        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_clears_cache_when_sending_message()
    {
        // Create initial messages
        ChatMessage::factory()->count(2)->create([
            'channel_id' => $this->channel->id,
        ]);

        // First request to populate cache
        $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        // Send new message
        $messageData = [
            'channel_id' => $this->channel->id,
            'channel_type' => 'global',
            'message' => 'New message!',
            'message_type' => 'text',
        ];

        $this
            ->actingAs($this->user)
            ->postJson('/game/api/chat/messages', $messageData);

        // Next request should include the new message
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/chat/channels/{$this->channel->id}/messages");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }
}

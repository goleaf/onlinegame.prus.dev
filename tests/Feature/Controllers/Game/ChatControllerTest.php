<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\ChatChannel;
use App\Models\Game\ChatMessage;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_chat_messages()
    {
        $user = User::factory()->create();
        ChatMessage::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/chat/messages');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'message_type',
                        'channel_id',
                        'player_id',
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
    public function it_can_send_chat_message()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $channel = ChatChannel::factory()->create();

        $messageData = [
            'channel_id' => $channel->id,
            'channel_type' => 'global',
            'message' => 'Hello everyone!',
            'message_type' => 'text',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'message',
                    'message_type',
                    'channel_id',
                    'player_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'message' => 'Hello everyone!',
            'channel_id' => $channel->id,
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_chat_channels()
    {
        $user = User::factory()->create();
        ChatChannel::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/chat/channels');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'description',
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
    public function it_can_get_global_chat_messages()
    {
        $user = User::factory()->create();
        $globalChannel = ChatChannel::factory()->create(['type' => 'global']);
        ChatMessage::factory()->count(2)->create(['channel_id' => $globalChannel->id]);

        $response = $this->actingAs($user)->get('/api/game/chat/global');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'message_type',
                        'player',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_chat_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $allianceChannel = ChatChannel::factory()->create(['type' => 'alliance']);
        ChatMessage::factory()->count(2)->create(['channel_id' => $allianceChannel->id]);

        $response = $this->actingAs($user)->get('/api/game/chat/alliance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'message_type',
                        'player',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_private_chat_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $privateChannel = ChatChannel::factory()->create(['type' => 'private']);
        ChatMessage::factory()->count(2)->create(['channel_id' => $privateChannel->id]);

        $response = $this->actingAs($user)->get('/api/game/chat/private');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'message_type',
                        'player',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_private_message()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $recipient = Player::factory()->create();

        $messageData = [
            'channel_type' => 'private',
            'message' => 'Private message',
            'message_type' => 'text',
            'recipient_id' => $recipient->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'message',
                    'message_type',
                    'player_id',
                    'created_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_system_message()
    {
        $user = User::factory()->create();
        $channel = ChatChannel::factory()->create();

        $messageData = [
            'channel_id' => $channel->id,
            'channel_type' => 'global',
            'message' => 'System announcement',
            'message_type' => 'system',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'message',
                    'message_type',
                    'channel_id',
                    'created_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_emote_message()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $channel = ChatChannel::factory()->create();

        $messageData = [
            'channel_id' => $channel->id,
            'channel_type' => 'global',
            'message' => '/wave',
            'message_type' => 'emote',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'message',
                    'message_type',
                    'channel_id',
                    'player_id',
                    'created_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_chat_history()
    {
        $user = User::factory()->create();
        $channel = ChatChannel::factory()->create();
        ChatMessage::factory()->count(5)->create(['channel_id' => $channel->id]);

        $response = $this->actingAs($user)->get("/api/game/chat/history/{$channel->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'message_type',
                        'player',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_delete_chat_message()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $message = ChatMessage::factory()->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->delete("/api/game/chat/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('chat_messages', ['id' => $message->id]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/chat/messages');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_chat_message_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/chat/send', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel_type', 'message', 'message_type']);
    }

    /**
     * @test
     */
    public function it_validates_message_length()
    {
        $user = User::factory()->create();

        $messageData = [
            'channel_type' => 'global',
            'message' => str_repeat('a', 1001),  // Exceeds max 1000
            'message_type' => 'text',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * @test
     */
    public function it_validates_message_type()
    {
        $user = User::factory()->create();

        $messageData = [
            'channel_type' => 'global',
            'message' => 'Test message',
            'message_type' => 'invalid_type',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message_type']);
    }

    /**
     * @test
     */
    public function it_validates_channel_type()
    {
        $user = User::factory()->create();

        $messageData = [
            'channel_type' => 'invalid_type',
            'message' => 'Test message',
            'message_type' => 'text',
        ];

        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel_type']);
    }

    /**
     * @test
     */
    public function it_prevents_duplicate_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $channel = ChatChannel::factory()->create();

        $messageData = [
            'channel_id' => $channel->id,
            'channel_type' => 'global',
            'message' => 'Duplicate message',
            'message_type' => 'text',
        ];

        // Send first message
        $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        // Try to send duplicate message immediately
        $response = $this->actingAs($user)->post('/api/game/chat/send', $messageData);

        $response->assertStatus(429);  // Rate limited
    }
}

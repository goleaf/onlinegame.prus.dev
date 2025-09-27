<?php

namespace Tests\Feature\Game;

use Tests\TestCase;
use App\Models\Game\Player;
use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $player;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_get_channel_messages()
    {
        $channel = ChatChannel::factory()->create();
        ChatMessage::factory()->count(5)->create(['channel_id' => $channel->id]);

        $response = $this->getJson("/game/api/chat/channels/{$channel->id}/messages");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'channel_id',
                            'message',
                            'message_type',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_messages_by_type()
    {
        ChatMessage::factory()->count(3)->create(['channel_type' => 'global']);
        ChatMessage::factory()->count(2)->create(['channel_type' => 'alliance']);

        $response = $this->getJson('/game/api/chat/messages/global');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_send_message()
    {
        $messageData = [
            'channel_type' => 'global',
            'message' => 'Hello, world!',
            'message_type' => 'text'
        ];

        $response = $this->postJson('/game/api/chat/send', $messageData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'player_id',
                        'message',
                        'message_type'
                    ]
                ]);

        $this->assertDatabaseHas('chat_messages', [
            'player_id' => $this->player->id,
            'message' => 'Hello, world!',
            'message_type' => 'text'
        ]);
    }

    /** @test */
    public function it_can_send_global_message()
    {
        $messageData = [
            'message' => 'Global announcement!',
            'message_type' => 'announcement'
        ];

        $response = $this->postJson('/game/api/chat/global', $messageData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_send_alliance_message()
    {
        $this->player->update(['alliance_id' => 1]);

        $messageData = [
            'message' => 'Alliance message!',
            'message_type' => 'text'
        ];

        $response = $this->postJson('/game/api/chat/alliance', $messageData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function it_cannot_send_alliance_message_without_alliance()
    {
        $messageData = [
            'message' => 'Alliance message!',
            'message_type' => 'text'
        ];

        $response = $this->postJson('/game/api/chat/alliance', $messageData);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function it_can_send_private_message()
    {
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'message' => 'Private message!'
        ];

        $response = $this->postJson('/game/api/chat/private', $messageData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_delete_message()
    {
        $message = ChatMessage::factory()->create([
            'player_id' => $this->player->id
        ]);

        $response = $this->deleteJson("/game/api/chat/messages/{$message->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function it_can_get_available_channels()
    {
        ChatChannel::factory()->count(3)->create();

        $response = $this->getJson('/game/api/chat/channels');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_get_channel_statistics()
    {
        $channel = ChatChannel::factory()->create();
        ChatMessage::factory()->count(10)->create(['channel_id' => $channel->id]);

        $response = $this->getJson("/game/api/chat/channels/{$channel->id}/stats");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_search_messages()
    {
        ChatMessage::factory()->create(['message' => 'Hello world']);
        ChatMessage::factory()->create(['message' => 'Goodbye world']);

        $response = $this->getJson('/game/api/chat/search?query=world');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_can_get_message_statistics()
    {
        ChatMessage::factory()->count(20)->create();

        $response = $this->getJson('/game/api/chat/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    /** @test */
    public function it_validates_message_sending()
    {
        $response = $this->postJson('/game/api/chat/send', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'channel_type',
                    'message',
                    'message_type'
                ]);
    }

    /** @test */
    public function it_validates_private_message_sending()
    {
        $response = $this->postJson('/game/api/chat/private', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'recipient_id',
                    'message'
                ]);
    }

    /** @test */
    public function it_validates_search_parameters()
    {
        $response = $this->getJson('/game/api/chat/search?query=ab');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['query']);
    }
}
<?php

namespace Tests\Feature\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected Player $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->recipient = Player::factory()->create();
    }

    /** @test */
    public function it_can_list_messages()
    {
        $messages = Message::factory()->count(3)->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sender_id',
                        'recipient_id',
                        'subject',
                        'content',
                        'is_read',
                        'is_important',
                        'message_type',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_messages_by_type()
    {
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'message_type' => 'private',
        ]);

        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'message_type' => 'alliance',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages?message_type=private');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('private', $responseData[0]['message_type']);
    }

    /** @test */
    public function it_can_filter_messages_by_read_status()
    {
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'is_read' => false,
        ]);

        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages?is_read=false');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertFalse($responseData[0]['is_read']);
    }

    /** @test */
    public function it_can_create_a_message()
    {
        $messageData = [
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'subject' => 'Test Message',
            'content' => 'This is a test message content.',
            'is_read' => false,
            'is_important' => false,
            'message_type' => 'private',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/messages', $messageData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sender_id',
                    'recipient_id',
                    'subject',
                    'content',
                    'is_read',
                    'is_important',
                    'message_type',
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'subject' => 'Test Message',
            'message_type' => 'private',
        ]);
    }

    /** @test */
    public function it_validates_message_creation_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/messages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'sender_id',
                'recipient_id',
                'subject',
                'content',
                'message_type',
            ]);
    }

    /** @test */
    public function it_can_show_a_message()
    {
        $message = Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/game/api/messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sender_id',
                    'recipient_id',
                    'subject',
                    'content',
                    'is_read',
                    'is_important',
                    'message_type',
                    'sender',
                    'recipient',
                ]
            ]);
    }

    /** @test */
    public function it_can_update_a_message()
    {
        $message = Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'is_read' => false,
        ]);

        $updateData = [
            'is_read' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/game/api/messages/{$message->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_read' => true,
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function it_can_delete_a_message()
    {
        $message = Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/game/api/messages/{$message->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    /** @test */
    public function it_can_search_messages()
    {
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'subject' => 'Important Message',
            'content' => 'This is an important message',
        ]);

        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'subject' => 'Regular Message',
            'content' => 'This is a regular message',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages?search=Important');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertStringContainsString('Important', $responseData[0]['subject']);
    }

    /** @test */
    public function it_can_filter_messages_by_importance()
    {
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'is_important' => true,
        ]);

        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
            'is_important' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages?is_important=true');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertTrue($responseData[0]['is_important']);
    }

    /** @test */
    public function it_can_get_inbox_messages()
    {
        Message::factory()->create([
            'sender_id' => $this->recipient->id,
            'recipient_id' => $this->player->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages/inbox');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_can_get_sent_messages()
    {
        Message::factory()->create([
            'sender_id' => $this->player->id,
            'recipient_id' => $this->recipient->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/messages/sent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }
}

<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_messages()
    {
        $user = User::factory()->create();
        Message::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/messages');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'priority',
                        'sender_id',
                        'recipient_id',
                        'is_read',
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
    public function it_can_get_specific_message()
    {
        $user = User::factory()->create();
        $message = Message::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'subject',
                'body',
                'priority',
                'sender',
                'recipient',
                'is_read',
                'read_at',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_message()
    {
        $user = User::factory()->create();
        $sender = Player::factory()->create(['user_id' => $user->id]);
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => 'Test Message',
            'body' => 'This is a test message',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/messages', $messageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'subject',
                    'body',
                    'priority',
                    'sender_id',
                    'recipient_id',
                    'is_read',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'subject' => 'Test Message',
            'body' => 'This is a test message',
            'recipient_id' => $recipient->id,
            'sender_id' => $sender->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_mark_message_as_read()
    {
        $user = User::factory()->create();
        $message = Message::factory()->create(['is_read' => false]);

        $response = $this->actingAs($user)->post("/api/game/messages/{$message->id}/read");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'is_read',
                    'read_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_mark_message_as_unread()
    {
        $user = User::factory()->create();
        $message = Message::factory()->create(['is_read' => true]);

        $response = $this->actingAs($user)->post("/api/game/messages/{$message->id}/unread");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message' => [
                    'id',
                    'is_read',
                    'read_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => false,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_inbox_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Message::factory()->count(3)->create(['recipient_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game/messages/inbox');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'priority',
                        'sender',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_sent_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Message::factory()->count(3)->create(['sender_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game/messages/sent');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'priority',
                        'recipient',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_unread_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Message::factory()->count(2)->create(['recipient_id' => $player->id, 'is_read' => false]);
        Message::factory()->count(1)->create(['recipient_id' => $player->id, 'is_read' => true]);

        $response = $this->actingAs($user)->get('/api/game/messages/unread');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'subject',
                        'body',
                        'priority',
                        'sender',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_messages_by_priority()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Message::factory()->count(2)->create(['recipient_id' => $player->id, 'priority' => 'high']);
        Message::factory()->count(1)->create(['recipient_id' => $player->id, 'priority' => 'normal']);

        $response = $this->actingAs($user)->get('/api/game/messages?priority=high');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_search_messages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Message::factory()->create(['recipient_id' => $player->id, 'subject' => 'Important Message']);
        Message::factory()->create(['recipient_id' => $player->id, 'subject' => 'Regular Message']);

        $response = $this->actingAs($user)->get('/api/game/messages?search=Important');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_message()
    {
        $user = User::factory()->create();
        $message = Message::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/messages/{$message->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }

    /**
     * @test
     */
    public function it_can_bulk_mark_messages_as_read()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $message1 = Message::factory()->create(['recipient_id' => $player->id, 'is_read' => false]);
        $message2 = Message::factory()->create(['recipient_id' => $player->id, 'is_read' => false]);

        $response = $this->actingAs($user)->post('/api/game/messages/bulk-read', [
            'message_ids' => [$message1->id, $message2->id],
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'updated_count',
            ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message1->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $message2->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_bulk_delete_messages()
    {
        $user = User::factory()->create();
        $message1 = Message::factory()->create();
        $message2 = Message::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/messages/bulk-delete', [
            'message_ids' => [$message1->id, $message2->id],
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'deleted_count',
            ]);

        $this->assertDatabaseMissing('messages', ['id' => $message1->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message2->id]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/messages');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_message_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/messages', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id', 'subject', 'body', 'priority']);
    }

    /**
     * @test
     */
    public function it_validates_priority_enum()
    {
        $user = User::factory()->create();
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => 'Test Message',
            'body' => 'Test body',
            'priority' => 'invalid_priority',
        ];

        $response = $this->actingAs($user)->post('/api/game/messages', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    /**
     * @test
     */
    public function it_validates_recipient_exists()
    {
        $user = User::factory()->create();

        $messageData = [
            'recipient_id' => 999,  // Non-existent player
            'subject' => 'Test Message',
            'body' => 'Test body',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/messages', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_id']);
    }

    /**
     * @test
     */
    public function it_validates_subject_length()
    {
        $user = User::factory()->create();
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => str_repeat('a', 256),  // Exceeds max 255
            'body' => 'Test body',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/messages', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    /**
     * @test
     */
    public function it_validates_body_length()
    {
        $user = User::factory()->create();
        $recipient = Player::factory()->create();

        $messageData = [
            'recipient_id' => $recipient->id,
            'subject' => 'Test Message',
            'body' => str_repeat('a', 5001),  // Exceeds max 5000
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/messages', $messageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_message()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/messages/999');

        $response->assertStatus(404);
    }
}

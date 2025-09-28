<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebSocketControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_subscribe_to_real_time_updates()
    {
        $channels = ['user', 'village', 'alliance'];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/websocket/subscribe', [
                'channels' => $channels,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'channels',
                    'socket_url',
                    'auth_endpoint',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($channels, $response->json('data.channels'));
    }

    /**
     * @test
     */
    public function it_can_unsubscribe_from_real_time_updates()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/websocket/unsubscribe');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_pending_updates()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/websocket/updates?limit=50&clear=false');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updates',
                    'count',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_send_test_message()
    {
        $messageData = [
            'message' => 'Test message',
            'type' => 'info',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/websocket/send-test-message', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_get_real_time_statistics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/api/websocket/stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_validates_subscribe_channels()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/websocket/subscribe', [
                'channels' => ['invalid_channel'],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_validates_test_message_data()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/api/websocket/send-test-message', [
                'message' => '',  // Empty message
                'type' => 'invalid_type',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_subscribe()
    {
        $response = $this->postJson('/api/websocket/subscribe', [
            'channels' => ['user'],
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_unsubscribe()
    {
        $response = $this->postJson('/api/websocket/unsubscribe');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_updates()
    {
        $response = $this->getJson('/api/websocket/updates');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_test_message()
    {
        $response = $this->postJson('/api/websocket/send-test-message', [
            'message' => 'Test',
            'type' => 'info',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_stats()
    {
        $response = $this->getJson('/api/websocket/stats');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }
}

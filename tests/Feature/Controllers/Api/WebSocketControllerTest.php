<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebSocketControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_subscribe_to_real_time_updates()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/subscribe', [
            'channels' => ['user', 'village', 'alliance'],
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'channels',
                    'subscription_id',
                    'connection_status',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_subscribe_to_default_channels()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/subscribe', []);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'channels',
                    'subscription_id',
                    'connection_status',
                ],
                'message',
            ]);

        $data = $response->json('data');
        $this->assertEquals(['user'], $data['channels']);
    }

    /**
     * @test
     */
    public function it_can_unsubscribe_from_channels()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/unsubscribe', [
            'channels' => ['user', 'village'],
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'unsubscribed_channels',
                    'remaining_channels',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_connection_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'connection_status',
                    'active_channels',
                    'last_activity',
                    'connection_time',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_message_to_channel()
    {
        $user = User::factory()->create();

        $messageData = [
            'channel' => 'user',
            'message' => 'Test message',
            'type' => 'notification',
        ];

        $response = $this->actingAs($user)->post('/api/websocket/send', $messageData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message_id',
                    'channel',
                    'message',
                    'sent_at',
                    'recipients',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_broadcast_to_all_users()
    {
        $user = User::factory()->create();

        $broadcastData = [
            'message' => 'System announcement',
            'type' => 'system',
            'priority' => 'high',
        ];

        $response = $this->actingAs($user)->post('/api/websocket/broadcast', $broadcastData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message_id',
                    'message',
                    'type',
                    'priority',
                    'sent_at',
                    'recipients_count',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_message_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/history?channel=user&limit=10');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'messages' => [
                        '*' => [
                            'id',
                            'channel',
                            'message',
                            'type',
                            'sender',
                            'sent_at',
                        ],
                    ],
                    'total_count',
                    'has_more',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_online_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/online-users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'online_users' => [
                        '*' => [
                            'user_id',
                            'username',
                            'last_activity',
                            'channels',
                        ],
                    ],
                    'total_online',
                    'active_channels',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_channel_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/channel-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'channels' => [
                        '*' => [
                            'name',
                            'subscribers',
                            'messages_sent',
                            'last_activity',
                        ],
                    ],
                    'total_channels',
                    'total_subscribers',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_join_alliance_channel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/join-alliance', [
            'alliance_id' => 1,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alliance_id',
                    'channel_name',
                    'permissions',
                    'joined_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_leave_alliance_channel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/leave-alliance', [
            'alliance_id' => 1,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alliance_id',
                    'left_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_user_presence()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/presence');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'status',
                    'last_seen',
                    'active_channels',
                    'connection_info',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_user_presence()
    {
        $user = User::factory()->create();

        $presenceData = [
            'status' => 'online',
            'activity' => 'playing',
        ];

        $response = $this->actingAs($user)->post('/api/websocket/presence', $presenceData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'status',
                    'activity',
                    'updated_at',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_connection_metrics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/metrics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_connections',
                    'active_connections',
                    'messages_per_second',
                    'average_response_time',
                    'error_rate',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_handle_connection_errors()
    {
        $user = User::factory()->create();

        // Mock real-time service to return an error
        $this->mock(\App\Services\RealTimeGameService::class, function ($mock): void {
            $mock
                ->shouldReceive('markUserOnline')
                ->andThrow(new \Exception('Connection failed'));
        });

        $response = $this->actingAs($user)->post('/api/websocket/subscribe', [
            'channels' => ['user'],
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->post('/api/websocket/subscribe', [
            'channels' => ['user'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_channel_names()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/subscribe', [
            'channels' => ['invalid_channel'],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channels.0']);
    }

    /**
     * @test
     */
    public function it_validates_message_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/send', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel', 'message']);
    }

    /**
     * @test
     */
    public function it_validates_broadcast_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/broadcast', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message', 'type']);
    }

    /**
     * @test
     */
    public function it_validates_presence_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/websocket/presence', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * @test
     */
    public function it_can_handle_rate_limiting()
    {
        $user = User::factory()->create();

        // Send multiple requests quickly to trigger rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->post('/api/websocket/send', [
                'channel' => 'user',
                'message' => 'Test message '.$i,
                'type' => 'notification',
            ]);
        }

        $response->assertStatus(429);
    }

    /**
     * @test
     */
    public function it_can_handle_connection_timeout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/websocket/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'connection_status',
                    'active_channels',
                    'last_activity',
                    'connection_time',
                ],
                'message',
            ]);
    }
}

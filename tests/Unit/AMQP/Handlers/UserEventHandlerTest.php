<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\UserEventHandler;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_user_registration_event()
    {
        $handler = new UserEventHandler();
        $user = User::factory()->create();

        $message = [
            'event_type' => 'user_registered',
            'user_id' => $user->id,
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
                'registered_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_user_login_event()
    {
        $handler = new UserEventHandler();
        $user = User::factory()->create();

        $message = [
            'event_type' => 'user_login',
            'user_id' => $user->id,
            'data' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'login_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_user_logout_event()
    {
        $handler = new UserEventHandler();
        $user = User::factory()->create();

        $message = [
            'event_type' => 'user_logout',
            'user_id' => $user->id,
            'data' => [
                'session_duration' => 3600,
                'logout_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_user_profile_update_event()
    {
        $handler = new UserEventHandler();
        $user = User::factory()->create();

        $message = [
            'event_type' => 'user_profile_updated',
            'user_id' => $user->id,
            'data' => [
                'updated_fields' => ['name', 'email'],
                'updated_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_user_password_change_event()
    {
        $handler = new UserEventHandler();
        $user = User::factory()->create();

        $message = [
            'event_type' => 'user_password_changed',
            'user_id' => $user->id,
            'data' => [
                'changed_at' => now()->toISOString(),
                'ip_address' => '192.168.1.1',
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_invalid_event_type()
    {
        $handler = new UserEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'user_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_user_id()
    {
        $handler = new UserEventHandler();

        $message = [
            'event_type' => 'user_registered',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}

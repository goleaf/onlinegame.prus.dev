<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\AdminEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_admin_action_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'admin_action',
            'admin_id' => 1,
            'data' => [
                'action_type' => 'user_ban',
                'target_id' => 2,
                'target_type' => 'user',
                'reason' => 'Violation of terms of service',
                'action_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_system_maintenance_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'system_maintenance',
            'admin_id' => 1,
            'data' => [
                'maintenance_type' => 'scheduled',
                'start_time' => now()->toISOString(),
                'end_time' => now()->addHours(2)->toISOString(),
                'affected_services' => ['database', 'cache'],
                'maintenance_reason' => 'Database optimization',
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_security_alert_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'security_alert',
            'admin_id' => 1,
            'data' => [
                'alert_type' => 'suspicious_activity',
                'severity' => 'high',
                'description' => 'Multiple failed login attempts detected',
                'affected_user_id' => 2,
                'alert_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_system_backup_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'system_backup',
            'admin_id' => 1,
            'data' => [
                'backup_type' => 'full_database',
                'backup_size' => '2.5GB',
                'backup_location' => '/backups/db_backup_20231201.sql',
                'backup_status' => 'completed',
                'backed_up_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_system_update_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'system_update',
            'admin_id' => 1,
            'data' => [
                'update_type' => 'game_version',
                'old_version' => '1.0.0',
                'new_version' => '1.1.0',
                'update_notes' => 'Added new features and bug fixes',
                'updated_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_server_restart_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'server_restart',
            'admin_id' => 1,
            'data' => [
                'restart_type' => 'scheduled',
                'restart_reason' => 'Memory optimization',
                'restart_time' => now()->toISOString(),
                'estimated_downtime' => 300,
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_database_optimization_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'database_optimization',
            'admin_id' => 1,
            'data' => [
                'optimization_type' => 'index_rebuild',
                'tables_optimized' => ['users', 'players', 'villages'],
                'optimization_duration' => 1800,
                'optimized_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_cache_clear_event()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'cache_clear',
            'admin_id' => 1,
            'data' => [
                'cache_type' => 'application_cache',
                'cache_keys_cleared' => 1500,
                'cleared_at' => now()->toISOString(),
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
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'admin_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_admin_id()
    {
        $handler = new AdminEventHandler();

        $message = [
            'event_type' => 'admin_action',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}

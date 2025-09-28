<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\MessageCleanupCommand;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_cleanup_old_messages()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30)
                ->once()
                ->andReturn(['deleted' => 100, 'archived' => 50]);
        });

        $this
            ->artisan('message:cleanup')
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 100 old messages')
            ->expectsOutput('Archived 50 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_custom_days()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(7)
                ->once()
                ->andReturn(['deleted' => 25, 'archived' => 10]);
        });

        $this
            ->artisan('message:cleanup', ['--days' => '7'])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 25 old messages')
            ->expectsOutput('Archived 10 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_dry_run()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getOldMessagesCount')
                ->with(30)
                ->once()
                ->andReturn(['deletable' => 150, 'archivable' => 75]);
        });

        $this
            ->artisan('message:cleanup', ['--dry-run' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Dry run mode - no messages will be deleted')
            ->expectsOutput('Found 150 messages that can be deleted')
            ->expectsOutput('Found 75 messages that can be archived')
            ->expectsOutput('Dry run completed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_force_flag()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30, true)
                ->once()
                ->andReturn(['deleted' => 200, 'archived' => 100]);
        });

        $this
            ->artisan('message:cleanup', ['--force' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 200 old messages')
            ->expectsOutput('Archived 100 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_verbose_output()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30)
                ->once()
                ->andReturn(['deleted' => 50, 'archived' => 25, 'details' => 'Cleanup completed successfully']);
        });

        $this
            ->artisan('message:cleanup', ['--verbose' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Details: Cleanup completed successfully')
            ->expectsOutput('Deleted 50 old messages')
            ->expectsOutput('Archived 25 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_specific_types()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30, false, ['system', 'notification'])
                ->once()
                ->andReturn(['deleted' => 75, 'archived' => 30]);
        });

        $this
            ->artisan('message:cleanup', ['--types' => 'system,notification'])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 75 old messages')
            ->expectsOutput('Archived 30 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_user_filter()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30, false, [], [1, 2, 3])
                ->once()
                ->andReturn(['deleted' => 40, 'archived' => 20]);
        });

        $this
            ->artisan('message:cleanup', ['--users' => '1,2,3'])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 40 old messages')
            ->expectsOutput('Archived 20 messages')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_cleanup_failure()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30)
                ->once()
                ->andThrow(new \Exception('Cleanup failed'));
        });

        $this
            ->artisan('message:cleanup')
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Message cleanup failed: Cleanup failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_show_cleanup_statistics()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getMessageStatistics')
                ->once()
                ->andReturn([
                    'total_messages' => 1000,
                    'old_messages' => 200,
                    'archived_messages' => 50,
                    'recent_messages' => 750,
                ]);
        });

        $this
            ->artisan('message:cleanup', ['--stats' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Message Statistics:')
            ->expectsOutput('Total messages: 1000')
            ->expectsOutput('Old messages: 200')
            ->expectsOutput('Archived messages: 50')
            ->expectsOutput('Recent messages: 750')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_backup()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('cleanupOldMessages')
                ->with(30, false, [], [], true)
                ->once()
                ->andReturn(['deleted' => 60, 'archived' => 30, 'backup_created' => true]);
        });

        $this
            ->artisan('message:cleanup', ['--backup' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Cleaning up old messages...')
            ->expectsOutput('Deleted 60 old messages')
            ->expectsOutput('Archived 30 messages')
            ->expectsOutput('Backup created successfully')
            ->expectsOutput('Message cleanup completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_messages_with_confirmation()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getOldMessagesCount')
                ->with(30)
                ->once()
                ->andReturn(['deletable' => 100, 'archivable' => 50]);
        });

        $this
            ->artisan('message:cleanup', ['--confirm' => true])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Found 100 messages that can be deleted')
            ->expectsOutput('Found 50 messages that can be archived')
            ->expectsOutput('Please confirm the cleanup operation')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_invalid_days_parameter()
    {
        $this
            ->artisan('message:cleanup', ['--days' => 'invalid'])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Invalid days parameter. Using default value: 30')
            ->expectsOutput('Cleaning up old messages...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_negative_days_parameter()
    {
        $this
            ->artisan('message:cleanup', ['--days' => '-5'])
            ->expectsOutput('🧹 Message Cleanup Tool')
            ->expectsOutput('Invalid days parameter. Using default value: 30')
            ->expectsOutput('Cleaning up old messages...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new MessageCleanupCommand();
        $this->assertEquals('message:cleanup', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new MessageCleanupCommand();
        $this->assertEquals('Clean up old messages and archive them', $command->getDescription());
    }
}

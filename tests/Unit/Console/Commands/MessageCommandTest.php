<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\MessageCommand;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_send_message()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendMessage')
                ->with(1, 2, 'Test message', 'text')
                ->once()
                ->andReturn(['success' => true, 'message_id' => 123]);
        });

        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending message...')
            ->expectsOutput('Message sent successfully (ID: 123)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_send_message_with_priority()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendMessage')
                ->with(1, 2, 'Test message', 'text', 'high')
                ->once()
                ->andReturn(['success' => true, 'message_id' => 124]);
        });

        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'text',
                '--priority' => 'high',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending message...')
            ->expectsOutput('Message sent successfully (ID: 124)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_send_message_with_attachments()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendMessage')
                ->with(1, 2, 'Test message', 'text', 'normal', ['file1.jpg', 'file2.pdf'])
                ->once()
                ->andReturn(['success' => true, 'message_id' => 125]);
        });

        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'text',
                '--attachments' => 'file1.jpg,file2.pdf',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending message...')
            ->expectsOutput('Message sent successfully (ID: 125)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_send_bulk_message()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendBulkMessage')
                ->with([1, 2, 3], 'Bulk message', 'text')
                ->once()
                ->andReturn(['success' => true, 'sent' => 3, 'failed' => 0]);
        });

        $this
            ->artisan('message:bulk', [
                'recipients' => '1,2,3',
                'content' => 'Bulk message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending bulk message...')
            ->expectsOutput('Bulk message sent successfully')
            ->expectsOutput('Sent: 3, Failed: 0')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_list_messages()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getMessages')
                ->with(1, 10)
                ->once()
                ->andReturn([
                    ['id' => 1, 'content' => 'Message 1', 'sender' => 'User 1'],
                    ['id' => 2, 'content' => 'Message 2', 'sender' => 'User 2'],
                ]);
        });

        $this
            ->artisan('message:list', ['user_id' => '1'])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Listing messages for user 1...')
            ->expectsOutput('Found 2 messages')
            ->expectsOutput('ID: 1 - Message 1 (from User 1)')
            ->expectsOutput('ID: 2 - Message 2 (from User 2)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_list_messages_with_filters()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getMessages')
                ->with(1, 10, ['type' => 'text', 'status' => 'unread'])
                ->once()
                ->andReturn([
                    ['id' => 1, 'content' => 'Message 1', 'sender' => 'User 1'],
                ]);
        });

        $this
            ->artisan('message:list', [
                'user_id' => '1',
                '--type' => 'text',
                '--status' => 'unread',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Listing messages for user 1...')
            ->expectsOutput('Found 1 messages')
            ->expectsOutput('ID: 1 - Message 1 (from User 1)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_mark_message_as_read()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('markAsRead')
                ->with(123)
                ->once()
                ->andReturn(['success' => true]);
        });

        $this
            ->artisan('message:read', ['message_id' => '123'])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Marking message as read...')
            ->expectsOutput('Message marked as read successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_delete_message()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('deleteMessage')
                ->with(123)
                ->once()
                ->andReturn(['success' => true]);
        });

        $this
            ->artisan('message:delete', ['message_id' => '123'])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Deleting message...')
            ->expectsOutput('Message deleted successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_archive_message()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('archiveMessage')
                ->with(123)
                ->once()
                ->andReturn(['success' => true]);
        });

        $this
            ->artisan('message:archive', ['message_id' => '123'])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Archiving message...')
            ->expectsOutput('Message archived successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_message_statistics()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('getMessageStatistics')
                ->once()
                ->andReturn([
                    'total_messages' => 1000,
                    'unread_messages' => 50,
                    'sent_today' => 25,
                    'archived_messages' => 200,
                ]);
        });

        $this
            ->artisan('message:stats')
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Message Statistics:')
            ->expectsOutput('Total messages: 1000')
            ->expectsOutput('Unread messages: 50')
            ->expectsOutput('Sent today: 25')
            ->expectsOutput('Archived messages: 200')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_send_message_failure()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendMessage')
                ->with(1, 2, 'Test message', 'text')
                ->once()
                ->andThrow(new \Exception('Send failed'));
        });

        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending message...')
            ->expectsOutput('Failed to send message: Send failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_bulk_message_partial_failure()
    {
        $this->mock(MessageService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendBulkMessage')
                ->with([1, 2, 3], 'Bulk message', 'text')
                ->once()
                ->andReturn(['success' => true, 'sent' => 2, 'failed' => 1]);
        });

        $this
            ->artisan('message:bulk', [
                'recipients' => '1,2,3',
                'content' => 'Bulk message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Sending bulk message...')
            ->expectsOutput('Bulk message sent with some failures')
            ->expectsOutput('Sent: 2, Failed: 1')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_user_id()
    {
        $this
            ->artisan('message:send', [
                'sender_id' => 'invalid',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Invalid sender ID. Please provide a valid user ID.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_recipient_id()
    {
        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => 'invalid',
                'content' => 'Test message',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Invalid recipient ID. Please provide a valid user ID.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_empty_message_content()
    {
        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => '',
                'type' => 'text',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Message content cannot be empty.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_message_type()
    {
        $this
            ->artisan('message:send', [
                'sender_id' => '1',
                'recipient_id' => '2',
                'content' => 'Test message',
                'type' => 'invalid',
            ])
            ->expectsOutput('📨 Message System')
            ->expectsOutput('Invalid message type. Valid types: text, image, file, system')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new MessageCommand();
        $this->assertEquals('message:send', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new MessageCommand();
        $this->assertEquals('Send messages between users', $command->getDescription());
    }
}

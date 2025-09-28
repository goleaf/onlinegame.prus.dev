<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\ChatComponent;
use App\Models\Game\ChatChannel;
use App\Models\Game\ChatMessage;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ChatComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private World $world;

    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->world = World::factory()->create();
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
        ]);
        $this->channel = ChatChannel::factory()->create([
            'type' => 'global',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_mount_component()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('player.id', $this->player->id)
            ->assertSet('currentChannel.id', $this->channel->id)
            ->assertSet('isLoading', false)
            ->assertSet('showChannels', true)
            ->assertSet('autoScroll', true);
    }

    /**
     * @test
     */
    public function it_can_mount_with_specific_channel()
    {
        $this->actingAs($this->user);
        $specificChannel = ChatChannel::factory()->create(['is_active' => true]);

        Livewire::test(ChatComponent::class, ['channelId' => $specificChannel->id])
            ->assertSet('currentChannel.id', $specificChannel->id);
    }

    /**
     * @test
     */
    public function it_loads_player_on_mount()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('player.id', $this->player->id)
            ->assertSet('player.user_id', $this->user->id);
    }

    /**
     * @test
     */
    public function it_shows_error_when_player_not_found()
    {
        $userWithoutPlayer = User::factory()->create();
        $this->actingAs($userWithoutPlayer);

        Livewire::test(ChatComponent::class)
            ->assertSet('player', null);
    }

    /**
     * @test
     */
    public function it_loads_channels_on_mount()
    {
        $this->actingAs($this->user);

        $channel1 = ChatChannel::factory()->create(['is_active' => true, 'name' => 'Channel 1']);
        $channel2 = ChatChannel::factory()->create(['is_active' => true, 'name' => 'Channel 2']);
        $inactiveChannel = ChatChannel::factory()->create(['is_active' => false]);

        Livewire::test(ChatComponent::class)
            ->assertCount('channels', 3);  // 2 active + 1 global
    }

    /**
     * @test
     */
    public function it_loads_messages_for_current_channel()
    {
        $this->actingAs($this->user);

        $message1 = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'sender_id' => $this->player->id,
            'message' => 'Hello world!',
        ]);

        $message2 = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'sender_id' => $this->player->id,
            'message' => 'How are you?',
        ]);

        Livewire::test(ChatComponent::class)
            ->assertCount('messages', 2);
    }

    /**
     * @test
     */
    public function it_can_send_message()
    {
        $this->actingAs($this->user);

        $chatService = Mockery::mock(ChatService::class);
        $chatService
            ->shouldReceive('sendMessage')
            ->once()
            ->with(
                $this->player->id,
                $this->channel->id,
                'global',
                'Hello everyone!',
                'text'
            )
            ->andReturn(ChatMessage::factory()->make());

        $this->app->instance(ChatService::class, $chatService);

        Livewire::test(ChatComponent::class)
            ->set('newMessage', 'Hello everyone!')
            ->call('sendMessage')
            ->assertSet('newMessage', '');
    }

    /**
     * @test
     */
    public function it_cannot_send_empty_message()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('newMessage', '')
            ->call('sendMessage')
            ->assertSet('newMessage', '');
    }

    /**
     * @test
     */
    public function it_cannot_send_whitespace_only_message()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('newMessage', '   ')
            ->call('sendMessage')
            ->assertSet('newMessage', '   ');
    }

    /**
     * @test
     */
    public function it_can_join_channel()
    {
        $this->actingAs($this->user);

        $newChannel = ChatChannel::factory()->create(['is_active' => true]);

        $chatService = Mockery::mock(ChatService::class);
        $chatService
            ->shouldReceive('joinChannel')
            ->once()
            ->with($this->player->id, $newChannel->id);

        $this->app->instance(ChatService::class, $chatService);

        Livewire::test(ChatComponent::class)
            ->call('joinChannel', $newChannel->id)
            ->assertSet('currentChannel.id', $newChannel->id)
            ->assertDispatched('channelJoined', $newChannel->id);
    }

    /**
     * @test
     */
    public function it_cannot_join_nonexistent_channel()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->call('joinChannel', 999)
            ->assertSet('currentChannel.id', $this->channel->id);  // Should remain on current channel
    }

    /**
     * @test
     */
    public function it_can_leave_channel()
    {
        $this->actingAs($this->user);

        $chatService = Mockery::mock(ChatService::class);
        $chatService
            ->shouldReceive('leaveChannel')
            ->once()
            ->with($this->player->id, $this->channel->id);

        $this->app->instance(ChatService::class, $chatService);

        Livewire::test(ChatComponent::class)
            ->call('leaveChannel', $this->channel->id)
            ->assertDispatched('channelLeft', $this->channel->id);
    }

    /**
     * @test
     */
    public function it_can_filter_messages_by_type()
    {
        $this->actingAs($this->user);

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message_type' => 'text',
            'message' => 'Text message',
        ]);

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message_type' => 'system',
            'message' => 'System message',
        ]);

        Livewire::test(ChatComponent::class)
            ->set('filterByType', 'text')
            ->call('loadMessages')
            ->assertCount('messages', 1);
    }

    /**
     * @test
     */
    public function it_can_filter_messages_by_player()
    {
        $this->actingAs($this->user);

        $otherPlayer = Player::factory()->create();

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'sender_id' => $this->player->id,
            'message' => 'My message',
        ]);

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'sender_id' => $otherPlayer->id,
            'message' => 'Other message',
        ]);

        Livewire::test(ChatComponent::class)
            ->set('filterByPlayer', $this->player->id)
            ->call('loadMessages')
            ->assertCount('messages', 1);
    }

    /**
     * @test
     */
    public function it_can_search_messages()
    {
        $this->actingAs($this->user);

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message' => 'Hello world!',
        ]);

        ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message' => 'Goodbye everyone!',
        ]);

        Livewire::test(ChatComponent::class)
            ->set('searchQuery', 'Hello')
            ->call('loadMessages')
            ->assertCount('messages', 1);
    }

    /**
     * @test
     */
    public function it_can_toggle_channels_visibility()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('showChannels', true)
            ->call('toggleChannels')
            ->assertSet('showChannels', false)
            ->call('toggleChannels')
            ->assertSet('showChannels', true);
    }

    /**
     * @test
     */
    public function it_can_toggle_emojis_visibility()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('showEmojis', true)
            ->call('toggleEmojis')
            ->assertSet('showEmojis', false)
            ->call('toggleEmojis')
            ->assertSet('showEmojis', true);
    }

    /**
     * @test
     */
    public function it_can_toggle_auto_scroll()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('autoScroll', true)
            ->call('toggleAutoScroll')
            ->assertSet('autoScroll', false)
            ->call('toggleAutoScroll')
            ->assertSet('autoScroll', true);
    }

    /**
     * @test
     */
    public function it_can_toggle_real_time_updates()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    /**
     * @test
     */
    public function it_can_clear_messages()
    {
        $this->actingAs($this->user);

        ChatMessage::factory()->count(5)->create([
            'channel_id' => $this->channel->id,
        ]);

        Livewire::test(ChatComponent::class)
            ->assertCount('messages', 5)
            ->call('clearMessages')
            ->assertCount('messages', 0);
    }

    /**
     * @test
     */
    public function it_can_refresh_messages()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->call('refreshMessages')
            ->assertSet('isLoading', false);
    }

    /**
     * @test
     */
    public function it_can_add_emoji_to_message()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('newMessage', 'Hello')
            ->call('addEmoji', '😊')
            ->assertSet('newMessage', 'Hello😊');
    }

    /**
     * @test
     */
    public function it_can_handle_message_received_event()
    {
        $this->actingAs($this->user);

        $message = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message' => 'New message',
        ]);

        Livewire::test(ChatComponent::class)
            ->dispatch('messageReceived', [
                'channelId' => $this->channel->id,
                'message' => $message->toArray(),
            ]);
    }

    /**
     * @test
     */
    public function it_can_handle_channel_joined_event()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->dispatch('channelJoined', $this->channel->id);
    }

    /**
     * @test
     */
    public function it_can_handle_channel_left_event()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->dispatch('channelLeft', $this->channel->id);
    }

    /**
     * @test
     */
    public function it_can_handle_game_tick_processed_event()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->dispatch('gameTickProcessed');
    }

    /**
     * @test
     */
    public function it_handles_chat_service_errors_gracefully()
    {
        $this->actingAs($this->user);

        $chatService = Mockery::mock(ChatService::class);
        $chatService
            ->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new \Exception('Chat service error'));

        $this->app->instance(ChatService::class, $chatService);

        Livewire::test(ChatComponent::class)
            ->set('newMessage', 'Hello!')
            ->call('sendMessage')
            ->assertSet('newMessage', 'Hello!');  // Message should remain
    }

    /**
     * @test
     */
    public function it_handles_channel_loading_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Mock ChatChannel to throw exception
        $mock = Mockery::mock('alias:'.ChatChannel::class);
        $mock
            ->shouldReceive('where')
            ->andThrow(new \Exception('Database error'));

        Livewire::test(ChatComponent::class);
    }

    /**
     * @test
     */
    public function it_limits_messages_to_50()
    {
        $this->actingAs($this->user);

        ChatMessage::factory()->count(100)->create([
            'channel_id' => $this->channel->id,
        ]);

        Livewire::test(ChatComponent::class)
            ->assertCount('messages', 50);
    }

    /**
     * @test
     */
    public function it_orders_messages_by_creation_date()
    {
        $this->actingAs($this->user);

        $message1 = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message' => 'First message',
            'created_at' => now()->subMinutes(10),
        ]);

        $message2 = ChatMessage::factory()->create([
            'channel_id' => $this->channel->id,
            'message' => 'Second message',
            'created_at' => now()->subMinutes(5),
        ]);

        $component = Livewire::test(ChatComponent::class);
        $messages = $component->get('messages');

        $this->assertEquals('First message', $messages[0]['message']);
        $this->assertEquals('Second message', $messages[1]['message']);
    }

    /**
     * @test
     */
    public function it_can_set_message_type()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('selectedMessageType', 'system')
            ->assertSet('selectedMessageType', 'system');
    }

    /**
     * @test
     */
    public function it_can_set_refresh_interval()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('refreshInterval', 10)
            ->assertSet('refreshInterval', 10);
    }

    /**
     * @test
     */
    public function it_can_set_sort_options()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->set('sortBy', 'sender_id')
            ->set('sortOrder', 'asc')
            ->assertSet('sortBy', 'sender_id')
            ->assertSet('sortOrder', 'asc');
    }

    /**
     * @test
     */
    public function it_renders_successfully()
    {
        $this->actingAs($this->user);

        Livewire::test(ChatComponent::class)
            ->assertStatus(200);
    }
}

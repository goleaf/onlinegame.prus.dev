<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $eventType;
    public $data;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, string $eventType, array $data = [])
    {
        $this->userId = $userId;
        $this->eventType = $eventType;
        $this->data = $data;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.user.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.event';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->eventType,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Determine if this event should be queued.
     */
    public function shouldQueue(): bool
    {
        return true;
    }
}


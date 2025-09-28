<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\ChatChannel;
use App\Models\Game\ChatMessage;
use App\Models\Game\Player;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Get messages for a channel
     */
    public function getChannelMessages(int $channelId, int $limit = 50, int $offset = 0): array
    {
        $messages = ChatMessage::with(['sender'])
            ->byChannel($channelId)
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->reverse()
            ->values();

        return [
            'messages' => $messages,
            'total' => ChatMessage::byChannel($channelId)->notDeleted()->count(),
        ];
    }

    /**
     * Get messages by channel type
     */
    public function getMessagesByType(string $channelType, int $limit = 50, int $offset = 0): array
    {
        $messages = ChatMessage::with(['sender', 'channel'])
            ->byChannelType($channelType)
            ->notDeleted()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->reverse()
            ->values();

        return [
            'messages' => $messages,
            'total' => ChatMessage::byChannelType($channelType)->notDeleted()->count(),
        ];
    }

    /**
     * Send a message to a channel
     */
    public function sendMessage(int $senderId, ?int $channelId, string $channelType, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        return DB::transaction(function () use ($senderId, $channelId, $channelType, $message, $messageType) {
            $chatMessage = ChatMessage::createMessage(
                $senderId,
                $channelId,
                $channelType,
                $message,
                $messageType
            );

            // Send real-time notification
            GameNotificationService::sendChatNotification($senderId, $channelId, $channelType, $message);

            // Send real-time update
            GameIntegrationService::sendChatUpdate($senderId, $channelId, $channelType, $chatMessage);

            return $chatMessage->load(['sender', 'channel']);
        });
    }

    /**
     * Send a global message
     */
    public function sendGlobalMessage(int $senderId, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        return $this->sendMessage($senderId, null, ChatMessage::CHANNEL_GLOBAL, $message, $messageType);
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(int $senderId, int $allianceId, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        $channel = ChatChannel::where('type', ChatMessage::CHANNEL_ALLIANCE)
            ->where('alliance_id', $allianceId)
            ->first();

        if (! $channel) {
            $channel = ChatChannel::createChannel(
                'Alliance Chat',
                ChatMessage::CHANNEL_ALLIANCE,
                $allianceId,
                $senderId
            );
        }

        return $this->sendMessage($senderId, $channel->id, ChatMessage::CHANNEL_ALLIANCE, $message, $messageType);
    }

    /**
     * Send a private message
     */
    public function sendPrivateMessage(int $senderId, int $recipientId, string $message): ChatMessage
    {
        // Create or find private channel between two players
        $channel = $this->getOrCreatePrivateChannel($senderId, $recipientId);

        return $this->sendMessage($senderId, $channel->id, ChatMessage::CHANNEL_PRIVATE, $message);
    }

    /**
     * Send a trade message
     */
    public function sendTradeMessage(int $senderId, string $message): ChatMessage
    {
        $channel = ChatChannel::where('type', ChatMessage::CHANNEL_TRADE)->first();

        if (! $channel) {
            $channel = ChatChannel::createChannel(
                'Trade Chat',
                ChatMessage::CHANNEL_TRADE,
                null,
                $senderId
            );
        }

        return $this->sendMessage($senderId, $channel->id, ChatMessage::CHANNEL_TRADE, $message);
    }

    /**
     * Send a diplomacy message
     */
    public function sendDiplomacyMessage(int $senderId, string $message): ChatMessage
    {
        $channel = ChatChannel::where('type', ChatMessage::CHANNEL_DIPLOMACY)->first();

        if (! $channel) {
            $channel = ChatChannel::createChannel(
                'Diplomacy Chat',
                ChatMessage::CHANNEL_DIPLOMACY,
                null,
                $senderId
            );
        }

        return $this->sendMessage($senderId, $channel->id, ChatMessage::CHANNEL_DIPLOMACY, $message);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId, int $playerId): bool
    {
        $message = ChatMessage::find($messageId);

        if (! $message || ! $message->canBeDeletedBy($playerId)) {
            return false;
        }

        return $message->softDelete();
    }

    /**
     * Get or create a private channel between two players
     */
    private function getOrCreatePrivateChannel(int $player1Id, int $player2Id): ChatChannel
    {
        // Look for existing private channel
        $channel = ChatChannel::where('type', ChatMessage::CHANNEL_PRIVATE)
            ->where('settings->players', function ($query) use ($player1Id, $player2Id): void {
                $query->whereJsonContains('players', [$player1Id, $player2Id]);
            })
            ->first();

        if (! $channel) {
            $channel = ChatChannel::createChannel(
                'Private Chat',
                ChatMessage::CHANNEL_PRIVATE,
                null,
                $player1Id,
                ['players' => [$player1Id, $player2Id]]
            );
        }

        return $channel;
    }

    /**
     * Get available channels for a player
     */
    public function getAvailableChannels(int $playerId): array
    {
        $player = Player::find($playerId);
        $channels = [];

        // Global channel
        $globalChannel = ChatChannel::global()->active()->first();
        if ($globalChannel) {
            $channels[] = $globalChannel;
        }

        // Alliance channel
        if ($player && $player->alliance_id) {
            $allianceChannel = ChatChannel::alliance()
                ->where('alliance_id', $player->alliance_id)
                ->active()
                ->first();
            if ($allianceChannel) {
                $channels[] = $allianceChannel;
            }
        }

        // Trade channel
        $tradeChannel = ChatChannel::trade()->active()->first();
        if ($tradeChannel) {
            $channels[] = $tradeChannel;
        }

        // Diplomacy channel
        $diplomacyChannel = ChatChannel::diplomacy()->active()->first();
        if ($diplomacyChannel) {
            $channels[] = $diplomacyChannel;
        }

        return $channels;
    }

    /**
     * Get channel statistics
     */
    public function getChannelStats(int $channelId): array
    {
        $channel = ChatChannel::find($channelId);
        if (! $channel) {
            return [];
        }

        return [
            'total_messages' => $channel->getMessageCount(),
            'recent_messages' => $channel->getRecentMessageCount(60),
            'last_message' => $channel->getLastMessage(),
            'active_users' => $this->getActiveUsers($channelId),
        ];
    }

    /**
     * Get active users in a channel
     */
    private function getActiveUsers(int $channelId): int
    {
        return ChatMessage::byChannel($channelId)
            ->recent(30)
            ->distinct('sender_id')
            ->count('sender_id');
    }

    /**
     * Clean up old messages
     */
    public function cleanupOldMessages(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);

        return ChatMessage::where('created_at', '<', $cutoffDate)
            ->where('is_deleted', true)
            ->delete();
    }

    /**
     * Get message statistics
     */
    public function getMessageStats(): array
    {
        return [
            'total_messages' => ChatMessage::notDeleted()->count(),
            'global_messages' => ChatMessage::global()->notDeleted()->count(),
            'alliance_messages' => ChatMessage::alliance()->notDeleted()->count(),
            'private_messages' => ChatMessage::private()->notDeleted()->count(),
            'trade_messages' => ChatMessage::trade()->notDeleted()->count(),
            'diplomacy_messages' => ChatMessage::diplomacy()->notDeleted()->count(),
            'recent_messages' => ChatMessage::recent(60)->notDeleted()->count(),
        ];
    }

    /**
     * Search messages
     */
    public function searchMessages(string $query, ?string $channelType = null, int $limit = 50): array
    {
        $searchQuery = ChatMessage::with(['sender', 'channel'])
            ->notDeleted()
            ->where('message', 'like', '%'.$query.'%');

        if ($channelType) {
            $searchQuery->byChannelType($channelType);
        }

        $messages = $searchQuery->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'messages' => $messages,
            'total' => $searchQuery->count(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Models\Game\Player;
use App\Models\Game\Alliance;
use App\Services\RealTimeGameService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    /**
     * Send a message to a channel
     */
    public function sendMessage(int $senderId, int $channelId, string $channelType, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        return DB::transaction(function () use ($senderId, $channelId, $channelType, $message, $messageType) {
            $chatMessage = ChatMessage::create([
                'sender_id' => $senderId,
                'channel_id' => $channelId,
                'channel_type' => $channelType,
                'message' => $message,
                'message_type' => $messageType,
                'reference_number' => $this->generateReferenceNumber(),
            ]);

            // Load relationships
            $chatMessage->load(['sender']);

            // Send real-time update
            $this->broadcastMessage($chatMessage);

            return $chatMessage;
        });
    }

    /**
     * Send a global message
     */
    public function sendGlobalMessage(int $senderId, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        $globalChannel = ChatChannel::getGlobalChannel();
        
        return $this->sendMessage($senderId, $globalChannel->id, ChatMessage::CHANNEL_GLOBAL, $message, $messageType);
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(int $senderId, int $allianceId, string $message, string $messageType = ChatMessage::TYPE_TEXT): ChatMessage
    {
        $allianceChannel = ChatChannel::getAllianceChannel($allianceId);
        
        if (!$allianceChannel) {
            // Create alliance channel if it doesn't exist
            $allianceChannel = ChatChannel::create([
                'name' => 'Alliance Chat',
                'description' => 'Alliance internal chat',
                'channel_type' => ChatChannel::TYPE_ALLIANCE,
                'alliance_id' => $allianceId,
                'is_public' => false,
                'is_active' => true,
                'created_by' => $senderId,
            ]);
        }
        
        return $this->sendMessage($senderId, $allianceChannel->id, ChatMessage::CHANNEL_ALLIANCE, $message, $messageType);
    }

    /**
     * Send a private message
     */
    public function sendPrivateMessage(int $senderId, int $recipientId, string $message): ChatMessage
    {
        $privateChannel = ChatChannel::getPrivateChannel($senderId, $recipientId);
        
        return $this->sendMessage($senderId, $privateChannel->id, ChatMessage::CHANNEL_PRIVATE, $message);
    }

    /**
     * Get messages for a channel
     */
    public function getChannelMessages(int $channelId, string $channelType, int $limit = 50, int $offset = 0): array
    {
        return ChatMessage::getChannelMessages($channelId, $channelType, $limit, $offset);
    }

    /**
     * Get global messages
     */
    public function getGlobalMessages(int $limit = 50, int $offset = 0): array
    {
        return ChatMessage::getGlobalMessages($limit, $offset);
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(int $allianceId, int $limit = 50, int $offset = 0): array
    {
        return ChatMessage::getAllianceMessages($allianceId, $limit, $offset);
    }

    /**
     * Get private messages between two players
     */
    public function getPrivateMessages(int $playerId, int $otherPlayerId, int $limit = 50, int $offset = 0): array
    {
        return ChatMessage::getPrivateMessages($playerId, $otherPlayerId, $limit, $offset);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId, int $playerId): bool
    {
        $message = ChatMessage::find($messageId);
        
        if (!$message || !$message->canBeDeletedBy($playerId)) {
            return false;
        }

        $message->softDelete();
        
        // Broadcast deletion
        $this->broadcastMessageDeletion($message);
        
        return true;
    }

    /**
     * Get available channels for a player
     */
    public function getAvailableChannels(int $playerId): array
    {
        return ChatChannel::getAvailableChannels($playerId);
    }

    /**
     * Create a custom channel
     */
    public function createChannel(int $creatorId, string $name, string $description, bool $isPublic = true, int $maxMembers = null): ChatChannel
    {
        return DB::transaction(function () use ($creatorId, $name, $description, $isPublic, $maxMembers) {
            $channel = ChatChannel::create([
                'name' => $name,
                'description' => $description,
                'channel_type' => ChatChannel::TYPE_CUSTOM,
                'is_public' => $isPublic,
                'is_active' => true,
                'max_members' => $maxMembers,
                'created_by' => $creatorId,
                'settings' => [
                    'allow_emotes' => true,
                    'allow_commands' => false,
                    'moderation_level' => 'normal',
                ],
            ]);

            // Send system message
            $this->sendMessage(
                $creatorId,
                $channel->id,
                ChatChannel::TYPE_CUSTOM,
                "Channel '{$name}' has been created!",
                ChatMessage::TYPE_SYSTEM
            );

            return $channel;
        });
    }

    /**
     * Join a channel
     */
    public function joinChannel(int $playerId, int $channelId): bool
    {
        $channel = ChatChannel::find($channelId);
        
        if (!$channel || !$channel->canJoin($playerId)) {
            return false;
        }

        // Send join message
        $player = Player::find($playerId);
        if ($player) {
            $this->sendMessage(
                $playerId,
                $channelId,
                $channel->channel_type,
                "{$player->name} joined the channel",
                ChatMessage::TYPE_SYSTEM
            );
        }

        return true;
    }

    /**
     * Leave a channel
     */
    public function leaveChannel(int $playerId, int $channelId): bool
    {
        $channel = ChatChannel::find($channelId);
        
        if (!$channel) {
            return false;
        }

        // Send leave message
        $player = Player::find($playerId);
        if ($player) {
            $this->sendMessage(
                $playerId,
                $channelId,
                $channel->channel_type,
                "{$player->name} left the channel",
                ChatMessage::TYPE_SYSTEM
            );
        }

        return true;
    }

    /**
     * Get chat statistics
     */
    public function getChatStats(): array
    {
        return [
            'message_stats' => ChatMessage::getMessageStats(),
            'channel_stats' => ChatChannel::getChannelStats(),
        ];
    }

    /**
     * Cleanup old messages
     */
    public function cleanupOldMessages(int $days = 30): int
    {
        return ChatMessage::cleanupOldMessages($days);
    }

    /**
     * Broadcast message to channel members
     */
    private function broadcastMessage(ChatMessage $message): void
    {
        try {
            $channel = $message->channel;
            
            if (!$channel) {
                return;
            }

            $userIds = $this->getChannelMemberIds($channel);
            
            if (empty($userIds)) {
                return;
            }

            RealTimeGameService::broadcastUpdate($userIds, 'chat_message', [
                'message_id' => $message->id,
                'channel_id' => $message->channel_id,
                'channel_type' => $message->channel_type,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name ?? 'Unknown',
                'message' => $message->message,
                'message_type' => $message->message_type,
                'created_at' => $message->created_at->toISOString(),
                'reference_number' => $message->reference_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast chat message', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast message deletion
     */
    private function broadcastMessageDeletion(ChatMessage $message): void
    {
        try {
            $channel = $message->channel;
            
            if (!$channel) {
                return;
            }

            $userIds = $this->getChannelMemberIds($channel);
            
            if (empty($userIds)) {
                return;
            }

            RealTimeGameService::broadcastUpdate($userIds, 'chat_message_deleted', [
                'message_id' => $message->id,
                'channel_id' => $message->channel_id,
                'channel_type' => $message->channel_type,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast chat message deletion', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get channel member user IDs
     */
    private function getChannelMemberIds(ChatChannel $channel): array
    {
        switch ($channel->channel_type) {
            case ChatChannel::TYPE_GLOBAL:
                // All online users
                return RealTimeGameService::getOnlineUsers();
                
            case ChatChannel::TYPE_ALLIANCE:
                if ($channel->alliance_id) {
                    $alliance = Alliance::with('members')->find($channel->alliance_id);
                    return $alliance ? $alliance->members->pluck('user_id')->toArray() : [];
                }
                break;
                
            case ChatChannel::TYPE_PRIVATE:
                // Extract player IDs from channel name
                $parts = explode('_', $channel->name);
                if (count($parts) === 2) {
                    $player1 = Player::find($parts[0]);
                    $player2 = Player::find($parts[1]);
                    $userIds = [];
                    if ($player1) $userIds[] = $player1->user_id;
                    if ($player2) $userIds[] = $player2->user_id;
                    return $userIds;
                }
                break;
                
            case ChatChannel::TYPE_CUSTOM:
                // For custom channels, you'd need to implement membership tracking
                // For now, return online users
                return RealTimeGameService::getOnlineUsers();
        }
        
        return [];
    }

    /**
     * Generate a unique reference number
     */
    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'CHT-' . strtoupper(Str::random(8));
        } while (ChatMessage::where('reference_number', $reference)->exists());

        return $reference;
    }
}

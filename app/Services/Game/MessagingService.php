<?php

namespace App\Services\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Models\Game\Alliance;
use App\Models\Game\Battle;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class MessagingService
{
    /**
     * Send a private message
     */
    public function sendPrivateMessage(Player $sender, Player $recipient, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): array
    {
        // Validate message
        $validation = $this->validateMessage($sender, $recipient, $subject, $body);
        if (!$validation['valid']) {
            return $validation;
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'subject' => $subject,
            'body' => $body,
            'message_type' => Message::TYPE_PRIVATE,
            'priority' => $priority,
            'is_read' => false,
        ]);

        // Clear cache
        $this->clearMessageCache($recipient);

        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'message_id' => $message->id,
        ];
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(Player $sender, Alliance $alliance, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): array
    {
        if ($sender->alliance_id !== $alliance->id) {
            return [
                'success' => false,
                'message' => 'Player is not a member of this alliance',
            ];
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'alliance_id' => $alliance->id,
            'subject' => $subject,
            'body' => $body,
            'message_type' => Message::TYPE_ALLIANCE,
            'priority' => $priority,
            'is_read' => false,
        ]);

        // Clear cache for all alliance members
        $this->clearAllianceMessageCache($alliance);

        return [
            'success' => true,
            'message' => 'Alliance message sent successfully',
            'message_id' => $message->id,
        ];
    }

    /**
     * Send a system message
     */
    public function sendSystemMessage(Player $recipient, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): array
    {
        $message = Message::create([
            'sender_id' => null, // System message
            'recipient_id' => $recipient->id,
            'subject' => $subject,
            'body' => $body,
            'message_type' => Message::TYPE_SYSTEM,
            'priority' => $priority,
            'is_read' => false,
        ]);

        // Clear cache
        $this->clearMessageCache($recipient);

        return [
            'success' => true,
            'message' => 'System message sent successfully',
            'message_id' => $message->id,
        ];
    }

    /**
     * Send a battle report message
     */
    public function sendBattleReportMessage(Player $recipient, Battle $battle, string $subject, string $body): array
    {
        $message = Message::create([
            'sender_id' => null, // System generated
            'recipient_id' => $recipient->id,
            'subject' => $subject,
            'body' => $body,
            'message_type' => Message::TYPE_BATTLE_REPORT,
            'priority' => Message::PRIORITY_HIGH,
            'is_read' => false,
        ]);

        // Clear cache
        $this->clearMessageCache($recipient);

        return [
            'success' => true,
            'message' => 'Battle report sent successfully',
            'message_id' => $message->id,
        ];
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Message $message, Player $player): array
    {
        if ($message->recipient_id !== $player->id) {
            return [
                'success' => false,
                'message' => 'Unauthorized to mark this message as read',
            ];
        }

        $message->markAsRead();

        // Clear cache
        $this->clearMessageCache($player);

        return [
            'success' => true,
            'message' => 'Message marked as read',
        ];
    }

    /**
     * Mark multiple messages as read
     */
    public function markMultipleAsRead(array $messageIds, Player $player): array
    {
        $updated = Message::whereIn('id', $messageIds)
            ->where('recipient_id', $player->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Clear cache
        $this->clearMessageCache($player);

        return [
            'success' => true,
            'message' => "{$updated} messages marked as read",
            'updated_count' => $updated,
        ];
    }

    /**
     * Delete message
     */
    public function deleteMessage(Message $message, Player $player): array
    {
        if (!$message->canBeDeleted($player->id)) {
            return [
                'success' => false,
                'message' => 'Unauthorized to delete this message',
            ];
        }

        $message->markAsDeleted($player->id);

        // Clear cache
        $this->clearMessageCache($player);

        return [
            'success' => true,
            'message' => 'Message deleted',
        ];
    }

    /**
     * Get inbox for player
     */
    public function getInbox(Player $player, int $limit = 50, int $offset = 0): array
    {
        $cacheKey = "inbox:{$player->id}:{$limit}:{$offset}";

        return SmartCache::remember($cacheKey, 300, function () use ($player, $limit, $offset) {
            return Message::with(['sender', 'recipient'])
                ->where('recipient_id', $player->id)
                ->where('is_deleted_by_recipient', false)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'reference_number' => $message->reference_number,
                        'sender' => $message->sender ? $message->sender->name : 'System',
                        'subject' => $message->subject,
                        'body' => $message->body,
                        'message_type' => $message->message_type,
                        'priority' => $message->priority,
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at,
                        'expires_at' => $message->expires_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get sent messages for player
     */
    public function getSentMessages(Player $player, int $limit = 50, int $offset = 0): array
    {
        $cacheKey = "sent_messages:{$player->id}:{$limit}:{$offset}";

        return SmartCache::remember($cacheKey, 300, function () use ($player, $limit, $offset) {
            return Message::with(['sender', 'recipient'])
                ->where('sender_id', $player->id)
                ->where('is_deleted_by_sender', false)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'reference_number' => $message->reference_number,
                        'recipient' => $message->recipient ? $message->recipient->name : 'System',
                        'subject' => $message->subject,
                        'body' => $message->body,
                        'message_type' => $message->message_type,
                        'priority' => $message->priority,
                        'created_at' => $message->created_at,
                        'expires_at' => $message->expires_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(Alliance $alliance, int $limit = 50, int $offset = 0): array
    {
        $cacheKey = "alliance_messages:{$alliance->id}:{$limit}:{$offset}";

        return SmartCache::remember($cacheKey, 300, function () use ($alliance, $limit, $offset) {
            return Message::with(['sender'])
                ->where('alliance_id', $alliance->id)
                ->where('message_type', Message::TYPE_ALLIANCE)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'reference_number' => $message->reference_number,
                        'sender' => $message->sender ? $message->sender->name : 'System',
                        'subject' => $message->subject,
                        'body' => $message->body,
                        'priority' => $message->priority,
                        'created_at' => $message->created_at,
                        'expires_at' => $message->expires_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get conversation between two players
     */
    public function getConversation(Player $player1, Player $player2, int $limit = 50): array
    {
        $cacheKey = "conversation:{$player1->id}:{$player2->id}:{$limit}";

        return SmartCache::remember($cacheKey, 300, function () use ($player1, $player2, $limit) {
            return Message::with(['sender', 'recipient'])
                ->where(function ($query) use ($player1, $player2) {
                    $query->where('sender_id', $player1->id)
                        ->where('recipient_id', $player2->id);
                })
                ->orWhere(function ($query) use ($player1, $player2) {
                    $query->where('sender_id', $player2->id)
                        ->where('recipient_id', $player1->id);
                })
                ->where('message_type', Message::TYPE_PRIVATE)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'reference_number' => $message->reference_number,
                        'sender' => $message->sender ? $message->sender->name : 'System',
                        'recipient' => $message->recipient ? $message->recipient->name : 'System',
                        'subject' => $message->subject,
                        'body' => $message->body,
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(Player $player): int
    {
        $cacheKey = "unread_count:{$player->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($player) {
            return Message::where('recipient_id', $player->id)
                ->where('is_read', false)
                ->where('is_deleted_by_recipient', false)
                ->count();
        });
    }

    /**
     * Get message statistics
     */
    public function getMessageStatistics(Player $player): array
    {
        $cacheKey = "message_stats:{$player->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($player) {
            return [
                'total_messages' => Message::forPlayer($player->id)->count(),
                'unread_messages' => Message::where('recipient_id', $player->id)
                    ->where('is_read', false)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'sent_messages' => Message::where('sender_id', $player->id)
                    ->where('is_deleted_by_sender', false)
                    ->count(),
                'received_messages' => Message::where('recipient_id', $player->id)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'alliance_messages' => Message::where('recipient_id', $player->id)
                    ->where('message_type', Message::TYPE_ALLIANCE)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
                'system_messages' => Message::where('recipient_id', $player->id)
                    ->where('message_type', Message::TYPE_SYSTEM)
                    ->where('is_deleted_by_recipient', false)
                    ->count(),
            ];
        });
    }

    /**
     * Cleanup expired messages
     */
    public function cleanupExpiredMessages(): int
    {
        return Message::cleanupExpiredMessages();
    }

    /**
     * Validate message
     */
    private function validateMessage(Player $sender, Player $recipient, string $subject, string $body): array
    {
        if (empty($subject) || strlen($subject) > 255) {
            return [
                'valid' => false,
                'message' => 'Subject must be between 1 and 255 characters',
            ];
        }

        if (empty($body) || strlen($body) > 10000) {
            return [
                'valid' => false,
                'message' => 'Body must be between 1 and 10000 characters',
            ];
        }

        if ($sender->id === $recipient->id) {
            return [
                'valid' => false,
                'message' => 'Cannot send message to yourself',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Clear message cache
     */
    private function clearMessageCache(Player $player): void
    {
        SmartCache::forget("unread_count:{$player->id}");
        SmartCache::forget("message_stats:{$player->id}");
        // Clear other message-related caches as needed
    }

    /**
     * Clear alliance message cache
     */
    private function clearAllianceMessageCache(Alliance $alliance): void
    {
        SmartCache::forget("alliance_messages:{$alliance->id}:50:0");
        // Clear other alliance message caches as needed
    }
}

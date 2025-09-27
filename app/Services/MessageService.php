<?php

namespace App\Services;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Models\Game\Alliance;
use App\Services\RealTimeGameService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MessageService
{
    protected $realTimeService;

    public function __construct()
    {
        $this->realTimeService = new RealTimeGameService();
    }

    /**
     * Send a private message between players
     */
    public function sendPrivateMessage(int $senderId, int $recipientId, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): Message
    {
        DB::beginTransaction();

        try {
            $message = Message::create([
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => Message::TYPE_PRIVATE,
                'priority' => $priority,
                'is_read' => false,
            ]);

            // Clear recipient's unread count cache
            Cache::forget("unread_messages_count:{$recipientId}");

            // Send real-time notification
            $this->realTimeService::sendUpdate($recipientId, 'new_message', [
                'message_id' => $message->id,
                'sender_id' => $senderId,
                'subject' => $subject,
                'priority' => $priority,
            ]);

            DB::commit();

            Log::info('Private message sent', [
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'message_id' => $message->id,
            ]);

            return $message;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send private message', [
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(int $senderId, int $allianceId, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): Message
    {
        DB::beginTransaction();

        try {
            $message = Message::create([
                'sender_id' => $senderId,
                'alliance_id' => $allianceId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => Message::TYPE_ALLIANCE,
                'priority' => $priority,
                'is_read' => false,
            ]);

            // Get all alliance members
            $allianceMembers = Player::where('alliance_id', $allianceId)
                                   ->where('id', '!=', $senderId) // Don't send to sender
                                   ->pluck('id')
                                   ->toArray();

            // Clear unread count cache for all members
            foreach ($allianceMembers as $memberId) {
                Cache::forget("unread_messages_count:{$memberId}");
            }

            // Send real-time notifications to all alliance members
            $this->realTimeService::broadcastUpdate($allianceMembers, 'new_alliance_message', [
                'message_id' => $message->id,
                'sender_id' => $senderId,
                'alliance_id' => $allianceId,
                'subject' => $subject,
                'priority' => $priority,
            ]);

            DB::commit();

            Log::info('Alliance message sent', [
                'sender_id' => $senderId,
                'alliance_id' => $allianceId,
                'message_id' => $message->id,
                'recipients' => count($allianceMembers),
            ]);

            return $message;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send alliance message', [
                'sender_id' => $senderId,
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a system message
     */
    public function sendSystemMessage(int $recipientId, string $subject, string $body, string $priority = Message::PRIORITY_NORMAL): Message
    {
        DB::beginTransaction();

        try {
            $message = Message::create([
                'sender_id' => null, // System message
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => Message::TYPE_SYSTEM,
                'priority' => $priority,
                'is_read' => false,
            ]);

            // Clear recipient's unread count cache
            Cache::forget("unread_messages_count:{$recipientId}");

            // Send real-time notification
            $this->realTimeService::sendUpdate($recipientId, 'new_system_message', [
                'message_id' => $message->id,
                'subject' => $subject,
                'priority' => $priority,
            ]);

            DB::commit();

            Log::info('System message sent', [
                'recipient_id' => $recipientId,
                'message_id' => $message->id,
            ]);

            return $message;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send system message', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send a battle report message
     */
    public function sendBattleReportMessage(int $recipientId, int $battleId, string $subject, string $body): Message
    {
        DB::beginTransaction();

        try {
            $message = Message::create([
                'sender_id' => null, // System generated
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => Message::TYPE_BATTLE_REPORT,
                'priority' => Message::PRIORITY_HIGH,
                'is_read' => false,
            ]);

            // Clear recipient's unread count cache
            Cache::forget("unread_messages_count:{$recipientId}");

            // Send real-time notification
            $this->realTimeService::sendUpdate($recipientId, 'new_battle_report', [
                'message_id' => $message->id,
                'battle_id' => $battleId,
                'subject' => $subject,
            ]);

            DB::commit();

            Log::info('Battle report message sent', [
                'recipient_id' => $recipientId,
                'battle_id' => $battleId,
                'message_id' => $message->id,
            ]);

            return $message;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send battle report message', [
                'recipient_id' => $recipientId,
                'battle_id' => $battleId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(int $messageId, int $playerId): bool
    {
        try {
            $message = Message::where('id', $messageId)
                            ->where('recipient_id', $playerId)
                            ->first();

            if (!$message) {
                return false;
            }

            $message->markAsRead();

            // Send real-time update
            $this->realTimeService::sendUpdate($playerId, 'message_read', [
                'message_id' => $messageId,
            ]);

            Log::info('Message marked as read', [
                'message_id' => $messageId,
                'player_id' => $playerId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark message as read', [
                'message_id' => $messageId,
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a message for a player
     */
    public function deleteMessage(int $messageId, int $playerId): bool
    {
        try {
            $message = Message::where('id', $messageId)
                            ->where(function ($q) use ($playerId) {
                                $q->where('sender_id', $playerId)
                                  ->orWhere('recipient_id', $playerId);
                            })
                            ->first();

            if (!$message) {
                return false;
            }

            $message->markAsDeleted($playerId);

            // Clear cache
            Cache::forget("unread_messages_count:{$playerId}");

            // Send real-time update
            $this->realTimeService::sendUpdate($playerId, 'message_deleted', [
                'message_id' => $messageId,
            ]);

            Log::info('Message deleted', [
                'message_id' => $messageId,
                'player_id' => $playerId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete message', [
                'message_id' => $messageId,
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get inbox for a player
     */
    public function getInbox(int $playerId, int $limit = 50, int $offset = 0): array
    {
        try {
            $messages = Message::with(['sender', 'recipient'])
                             ->where('recipient_id', $playerId)
                             ->where('is_deleted_by_recipient', false)
                             ->orderBy('created_at', 'desc')
                             ->offset($offset)
                             ->limit($limit)
                             ->get();

            $total = Message::where('recipient_id', $playerId)
                          ->where('is_deleted_by_recipient', false)
                          ->count();

            return [
                'messages' => $messages,
                'total' => $total,
                'unread_count' => $messages->where('is_read', false)->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get inbox', [
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'messages' => collect(),
                'total' => 0,
                'unread_count' => 0,
            ];
        }
    }

    /**
     * Get sent messages for a player
     */
    public function getSentMessages(int $playerId, int $limit = 50, int $offset = 0): array
    {
        try {
            $messages = Message::with(['sender', 'recipient'])
                             ->where('sender_id', $playerId)
                             ->where('is_deleted_by_sender', false)
                             ->orderBy('created_at', 'desc')
                             ->offset($offset)
                             ->limit($limit)
                             ->get();

            $total = Message::where('sender_id', $playerId)
                          ->where('is_deleted_by_sender', false)
                          ->count();

            return [
                'messages' => $messages,
                'total' => $total,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get sent messages', [
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'messages' => collect(),
                'total' => 0,
            ];
        }
    }

    /**
     * Get conversation between two players
     */
    public function getConversation(int $playerId, int $otherPlayerId, int $limit = 50): array
    {
        try {
            $messages = Message::with(['sender', 'recipient'])
                             ->where(function ($q) use ($playerId, $otherPlayerId) {
                                 $q->where('sender_id', $playerId)
                                   ->where('recipient_id', $otherPlayerId);
                             })
                             ->orWhere(function ($q) use ($playerId, $otherPlayerId) {
                                 $q->where('sender_id', $otherPlayerId)
                                   ->where('recipient_id', $playerId);
                             })
                             ->where('message_type', Message::TYPE_PRIVATE)
                             ->orderBy('created_at', 'asc')
                             ->limit($limit)
                             ->get();

            return [
                'messages' => $messages,
                'total' => $messages->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get conversation', [
                'player_id' => $playerId,
                'other_player_id' => $otherPlayerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'messages' => collect(),
                'total' => 0,
            ];
        }
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(int $allianceId, int $limit = 50, int $offset = 0): array
    {
        try {
            $messages = Message::with(['sender', 'alliance'])
                             ->where('alliance_id', $allianceId)
                             ->where('message_type', Message::TYPE_ALLIANCE)
                             ->orderBy('created_at', 'desc')
                             ->offset($offset)
                             ->limit($limit)
                             ->get();

            $total = Message::where('alliance_id', $allianceId)
                          ->where('message_type', Message::TYPE_ALLIANCE)
                          ->count();

            return [
                'messages' => $messages,
                'total' => $total,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get alliance messages', [
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'messages' => collect(),
                'total' => 0,
            ];
        }
    }

    /**
     * Get message statistics for a player
     */
    public function getMessageStats(int $playerId): array
    {
        return Cache::remember("message_stats:{$playerId}", 600, function () use ($playerId) {
            return [
                'total_messages' => Message::forPlayer($playerId)->count(),
                'unread_messages' => Message::where('recipient_id', $playerId)
                                          ->where('is_read', false)
                                          ->where('is_deleted_by_recipient', false)
                                          ->count(),
                'sent_messages' => Message::where('sender_id', $playerId)
                                        ->where('is_deleted_by_sender', false)
                                        ->count(),
                'received_messages' => Message::where('recipient_id', $playerId)
                                            ->where('is_deleted_by_recipient', false)
                                            ->count(),
                'alliance_messages' => Message::where('recipient_id', $playerId)
                                            ->where('message_type', Message::TYPE_ALLIANCE)
                                            ->where('is_deleted_by_recipient', false)
                                            ->count(),
                'system_messages' => Message::where('recipient_id', $playerId)
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
        try {
            $deleted = Message::cleanupExpiredMessages();

            Log::info('Expired messages cleaned up', [
                'deleted_count' => $deleted,
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired messages', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Bulk mark messages as read
     */
    public function bulkMarkAsRead(array $messageIds, int $playerId): int
    {
        try {
            $updated = Message::whereIn('id', $messageIds)
                            ->where('recipient_id', $playerId)
                            ->where('is_read', false)
                            ->update(['is_read' => true]);

            // Clear cache
            Cache::forget("unread_messages_count:{$playerId}");

            // Send real-time update
            $this->realTimeService::sendUpdate($playerId, 'messages_bulk_read', [
                'message_ids' => $messageIds,
                'count' => $updated,
            ]);

            Log::info('Messages bulk marked as read', [
                'player_id' => $playerId,
                'message_ids' => $messageIds,
                'updated_count' => $updated,
            ]);

            return $updated;

        } catch (\Exception $e) {
            Log::error('Failed to bulk mark messages as read', [
                'player_id' => $playerId,
                'message_ids' => $messageIds,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Bulk delete messages
     */
    public function bulkDeleteMessages(array $messageIds, int $playerId): int
    {
        try {
            $deleted = 0;

            foreach ($messageIds as $messageId) {
                if ($this->deleteMessage($messageId, $playerId)) {
                    $deleted++;
                }
            }

            Log::info('Messages bulk deleted', [
                'player_id' => $playerId,
                'message_ids' => $messageIds,
                'deleted_count' => $deleted,
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to bulk delete messages', [
                'player_id' => $playerId,
                'message_ids' => $messageIds,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}

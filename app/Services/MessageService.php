<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Message;
use App\Models\Game\Player;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageService
{
    /**
     * Get inbox messages for a player
     */
    public function getInbox(int $playerId, int $limit = 50, int $offset = 0): array
    {
        $messages = Message::with(['sender', 'alliance'])
            ->where('recipient_id', $playerId)
            ->where('is_deleted_by_recipient', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'messages' => $messages,
            'total' => Message::where('recipient_id', $playerId)
                ->where('is_deleted_by_recipient', false)
                ->count(),
            'unread_count' => Message::where('recipient_id', $playerId)
                ->where('is_deleted_by_recipient', false)
                ->where('is_read', false)
                ->count(),
        ];
    }

    /**
     * Get sent messages for a player
     */
    public function getSentMessages(int $playerId, int $limit = 50, int $offset = 0): array
    {
        $messages = Message::with(['recipient', 'alliance'])
            ->where('sender_id', $playerId)
            ->where('is_deleted_by_sender', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'messages' => $messages,
            'total' => Message::where('sender_id', $playerId)
                ->where('is_deleted_by_sender', false)
                ->count(),
        ];
    }

    /**
     * Get conversation between two players
     */
    public function getConversation(int $playerId, int $otherPlayerId, int $limit = 50): array
    {
        $messages = Message::with(['sender', 'recipient'])
            ->where(function ($q) use ($playerId, $otherPlayerId): void {
                $q
                    ->where('sender_id', $playerId)
                    ->where('recipient_id', $otherPlayerId);
            })
            ->orWhere(function ($q) use ($playerId, $otherPlayerId): void {
                $q
                    ->where('sender_id', $otherPlayerId)
                    ->where('recipient_id', $playerId);
            })
            ->where('message_type', 'private')
            ->where(function ($q) use ($playerId): void {
                $q
                    ->where(function ($subQ) use ($playerId): void {
                        $subQ
                            ->where('sender_id', $playerId)
                            ->where('is_deleted_by_sender', false);
                    })
                    ->orWhere(function ($subQ) use ($playerId): void {
                        $subQ
                            ->where('recipient_id', $playerId)
                            ->where('is_deleted_by_recipient', false);
                    });
            })
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return [
            'messages' => $messages,
            'total' => Message::where(function ($q) use ($playerId, $otherPlayerId): void {
                $q
                    ->where('sender_id', $playerId)
                    ->where('recipient_id', $otherPlayerId);
            })
                ->orWhere(function ($q) use ($playerId, $otherPlayerId): void {
                    $q
                        ->where('sender_id', $otherPlayerId)
                        ->where('recipient_id', $playerId);
                })
                ->where('message_type', 'private')
                ->count(),
        ];
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(int $allianceId, int $limit = 50, int $offset = 0): array
    {
        $messages = Message::with(['sender'])
            ->where('alliance_id', $allianceId)
            ->where('message_type', 'alliance')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return [
            'messages' => $messages,
            'total' => Message::where('alliance_id', $allianceId)
                ->where('message_type', 'alliance')
                ->count(),
        ];
    }

    /**
     * Send a private message
     */
    public function sendPrivateMessage(int $senderId, int $recipientId, string $subject, string $body, string $priority = 'normal'): Message
    {
        return DB::transaction(function () use ($senderId, $recipientId, $subject, $body, $priority) {
            $message = Message::create([
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => 'private',
                'priority' => $priority,
                'reference_number' => $this->generateReferenceNumber(),
            ]);

            // Mark as read if sender and recipient are the same (draft)
            if ($senderId === $recipientId) {
                $message->update(['is_read' => true]);
            }

            return $message->load(['sender', 'recipient']);
        });
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(int $senderId, int $allianceId, string $subject, string $body, string $priority = 'normal'): Message
    {
        return DB::transaction(function () use ($senderId, $allianceId, $subject, $body, $priority) {
            $message = Message::create([
                'sender_id' => $senderId,
                'alliance_id' => $allianceId,
                'subject' => $subject,
                'body' => $body,
                'message_type' => 'alliance',
                'priority' => $priority,
                'reference_number' => $this->generateReferenceNumber(),
            ]);

            return $message->load(['sender', 'alliance']);
        });
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(int $messageId, int $playerId): bool
    {
        $message = Message::where('id', $messageId)
            ->where('recipient_id', $playerId)
            ->where('is_deleted_by_recipient', false)
            ->first();

        if ($message && ! $message->is_read) {
            $message->update(['is_read' => true]);

            return true;
        }

        return false;
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId, int $playerId): bool
    {
        $message = Message::where('id', $messageId)
            ->where(function ($q) use ($playerId): void {
                $q
                    ->where('sender_id', $playerId)
                    ->orWhere('recipient_id', $playerId);
            })
            ->first();

        if (! $message) {
            return false;
        }

        if ($message->sender_id === $playerId) {
            $message->update(['is_deleted_by_sender' => true]);
        }

        if ($message->recipient_id === $playerId) {
            $message->update(['is_deleted_by_recipient' => true]);
        }

        // Permanently delete if both parties deleted it
        if ($message->is_deleted_by_sender && $message->is_deleted_by_recipient) {
            $message->delete();
        }

        return true;
    }

    /**
     * Get message statistics for a player
     */
    public function getMessageStats(int $playerId): array
    {
        return [
            'total_inbox' => Message::where('recipient_id', $playerId)
                ->where('is_deleted_by_recipient', false)
                ->count(),
            'unread_inbox' => Message::where('recipient_id', $playerId)
                ->where('is_deleted_by_recipient', false)
                ->where('is_read', false)
                ->count(),
            'total_sent' => Message::where('sender_id', $playerId)
                ->where('is_deleted_by_sender', false)
                ->count(),
            'alliance_messages' => Message::whereHas('alliance', function ($q) use ($playerId): void {
                $q->whereHas('members', function ($memberQ) use ($playerId): void {
                    $memberQ->where('player_id', $playerId);
                });
            })->count(),
        ];
    }

    /**
     * Bulk mark messages as read
     */
    public function bulkMarkAsRead(array $messageIds, int $playerId): int
    {
        return Message::whereIn('id', $messageIds)
            ->where('recipient_id', $playerId)
            ->where('is_deleted_by_recipient', false)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Bulk delete messages
     */
    public function bulkDeleteMessages(array $messageIds, int $playerId): int
    {
        $updated = 0;

        foreach ($messageIds as $messageId) {
            if ($this->deleteMessage($messageId, $playerId)) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Generate a unique reference number
     */
    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'MSG-'.strtoupper(Str::random(8));
        } while (Message::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Send system message
     */
    public function sendSystemMessage(int $recipientId, string $subject, string $body, string $priority = 'normal'): Message
    {
        return Message::create([
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => 'system',
            'priority' => $priority,
            'reference_number' => $this->generateReferenceNumber(),
        ]);
    }

    /**
     * Send battle report message
     */
    public function sendBattleReport(int $recipientId, string $subject, string $body, array $battleData = []): Message
    {
        return Message::create([
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => 'battle_report',
            'priority' => 'high',
            'reference_number' => $this->generateReferenceNumber(),
        ]);
    }

    /**
     * Send trade offer message
     */
    public function sendTradeOffer(int $senderId, int $recipientId, string $subject, string $body, array $tradeData = []): Message
    {
        return Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => 'trade_offer',
            'priority' => 'normal',
            'reference_number' => $this->generateReferenceNumber(),
        ]);
    }

    /**
     * Send diplomacy message
     */
    public function sendDiplomacyMessage(int $senderId, int $recipientId, string $subject, string $body, array $diplomacyData = []): Message
    {
        return Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'message_type' => 'diplomacy',
            'priority' => 'high',
            'reference_number' => $this->generateReferenceNumber(),
        ]);
    }

    /**
     * Send geographic alert message to nearby players
     */
    public function sendGeographicAlert(int $villageId, string $alertType, array $alertData = []): array
    {
        $geoService = app(GeographicService::class);
        $sentMessages = [];

        // Get the source village
        $sourceVillage = \App\Models\Game\Village::with('player')
            ->where('id', $villageId)
            ->first();

        if (! $sourceVillage || ! $sourceVillage->latitude || ! $sourceVillage->longitude) {
            return $sentMessages;
        }

        // Find nearby villages within alert radius
        $alertRadius = $alertData['radius'] ?? 25; // Default 25km
        $nearbyVillages = \App\Models\Game\Village::with('player')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $villageId)
            ->get()
            ->filter(function ($village) use ($sourceVillage, $geoService, $alertRadius) {
                $distance = $geoService->calculateDistance(
                    $sourceVillage->latitude,
                    $sourceVillage->longitude,
                    $village->latitude,
                    $village->longitude
                );

                return $distance <= $alertRadius;
            });

        // Send alert messages to nearby players
        foreach ($nearbyVillages as $village) {
            if ($village->player && $village->player->id !== $sourceVillage->player_id) {
                $distance = $geoService->calculateDistance(
                    $sourceVillage->latitude,
                    $sourceVillage->longitude,
                    $village->latitude,
                    $village->longitude
                );

                $bearing = $geoService->calculateBearing(
                    $village->latitude,
                    $village->longitude,
                    $sourceVillage->latitude,
                    $sourceVillage->longitude
                );

                $subject = $this->getGeographicAlertSubject($alertType, $sourceVillage->name);
                $body = $this->getGeographicAlertBody($alertType, $sourceVillage, $distance, $bearing, $alertData);

                $message = Message::create([
                    'sender_id' => $sourceVillage->player_id,
                    'recipient_id' => $village->player->id,
                    'subject' => $subject,
                    'body' => $body,
                    'message_type' => 'geographic_alert',
                    'priority' => 'high',
                    'reference_number' => $this->generateReferenceNumber(),
                ]);

                $sentMessages[] = $message;
            }
        }

        return $sentMessages;
    }

    /**
     * Get geographic alert subject
     */
    private function getGeographicAlertSubject(string $alertType, string $villageName): string
    {
        return match ($alertType) {
            'attack' => "🚨 Attack Alert from {$villageName}",
            'raid' => "⚔️ Raid Alert from {$villageName}",
            'support' => "🛡️ Support Request from {$villageName}",
            'alliance_war' => "🏰 Alliance War Alert from {$villageName}",
            default => "📍 Geographic Alert from {$villageName}",
        };
    }

    /**
     * Get geographic alert body
     */
    private function getGeographicAlertBody(\App\Models\Game\Village $sourceVillage, float $distance, float $bearing, array $alertData): string
    {
        $direction = $this->getDirectionFromBearing($bearing);

        $body = "Geographic Alert from {$sourceVillage->name}\n\n";
        $body .= '📍 Distance: '.number_format($distance, 2)." km\n";
        $body .= "🧭 Direction: {$direction} ({$bearing}°)\n";
        $body .= "🏘️ Village: {$sourceVillage->name}\n";
        $body .= "👤 Player: {$sourceVillage->player->name}\n";

        if (! empty($alertData['details'])) {
            $body .= "\n📋 Details: {$alertData['details']}\n";
        }

        $body .= "\n⏰ Time: ".now()->format('Y-m-d H:i:s')."\n";
        $body .= "\nStay alert and prepare your defenses!";

        return $body;
    }

    /**
     * Convert bearing to direction
     */
    private function getDirectionFromBearing(float $bearing): string
    {
        $directions = [
            'N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE',
            'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW',
        ];

        $index = round($bearing / 22.5) % 16;

        return $directions[$index];
    }
}

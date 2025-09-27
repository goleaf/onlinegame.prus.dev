<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllianceMessage extends Model
{
    protected $fillable = [
        'alliance_id',
        'sender_id',
        'type',
        'title',
        'content',
        'is_pinned',
        'is_important',
        'read_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
        'read_by' => 'array',
    ];

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'sender_id');
    }

    // Scopes
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUnreadBy($query, $playerId)
    {
        return $query->whereJsonDoesntContain('read_by', $playerId);
    }

    public function scopeReadBy($query, $playerId)
    {
        return $query->whereJsonContains('read_by', $playerId);
    }

    // Helper methods
    public function isReadBy($playerId): bool
    {
        return in_array($playerId, $this->read_by ?? []);
    }

    public function markAsReadBy($playerId): void
    {
        $readBy = $this->read_by ?? [];
        if (!in_array($playerId, $readBy)) {
            $readBy[] = $playerId;
            $this->update(['read_by' => $readBy]);
        }
    }

    public function markAsUnreadBy($playerId): void
    {
        $readBy = $this->read_by ?? [];
        $readBy = array_filter($readBy, fn($id) => $id !== $playerId);
        $this->update(['read_by' => array_values($readBy)]);
    }

    public function getReadCount(): int
    {
        return count($this->read_by ?? []);
    }

    public function getUnreadCount(): int
    {
        $totalMembers = $this->alliance->members()->count();
        return $totalMembers - $this->getReadCount();
    }
}
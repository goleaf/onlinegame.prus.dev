<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class GameEvent extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait;

    protected $fillable = [
        'player_id',
        'village_id',
        'event_type',
        'event_data',
        'occurred_at',
        'is_read',
        'reference_number',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            game_events.*,
            (SELECT COUNT(*) FROM game_events ge2 WHERE ge2.player_id = game_events.player_id) as total_events,
            (SELECT COUNT(*) FROM game_events ge3 WHERE ge3.player_id = game_events.player_id AND ge3.is_read = 0) as unread_events,
            (SELECT COUNT(*) FROM game_events ge4 WHERE ge4.player_id = game_events.player_id AND ge4.event_type = game_events.event_type) as events_of_type
        ');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByType($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('event_type', $type);
        });
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->where('occurred_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('occurred_at', '>=', now()->startOfMonth());
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('event_type', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'player:id,name',
            'village:id,name,x_coordinate,y_coordinate',
        ]);
    }
}

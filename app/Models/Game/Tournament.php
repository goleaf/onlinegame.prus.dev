<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'format',
        'status',
        'max_participants',
        'min_participants',
        'entry_fee',
        'prizes',
        'rules',
        'requirements',
        'registration_start',
        'registration_end',
        'start_time',
        'end_time',
        'round_duration_minutes',
        'is_public',
        'allow_spectators',
    ];

    protected $casts = [
        'prizes' => 'array',
        'rules' => 'array',
        'requirements' => 'array',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_public' => 'boolean',
        'allow_spectators' => 'boolean',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeRegistration($query)
    {
        return $query->where('status', 'registration');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeOpenForRegistration($query)
    {
        return $query->where('status', 'registration')
                    ->where('registration_start', '<=', now())
                    ->where('registration_end', '>=', now());
    }

    // Helper methods
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration' &&
               $this->registration_start <= now() &&
               $this->registration_end >= now();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->start_time <= now() &&
               ($this->end_time === null || $this->end_time >= now());
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' ||
               ($this->end_time !== null && $this->end_time < now());
    }

    public function canRegister(Player $player): bool
    {
        if (!$this->isRegistrationOpen()) {
            return false;
        }

        if ($this->participants()->where('player_id', $player->id)->exists()) {
            return false;
        }

        if ($this->participants()->count() >= $this->max_participants) {
            return false;
        }

        return $this->meetsRequirements($player);
    }

    public function meetsRequirements(Player $player): bool
    {
        $requirements = $this->requirements ?? [];
        
        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'min_level':
                    if ($player->level < $value) {
                        return false;
                    }
                    break;
                case 'min_points':
                    if ($player->points < $value) {
                        return false;
                    }
                    break;
                case 'min_villages':
                    if ($player->villages->count() < $value) {
                        return false;
                    }
                    break;
                case 'required_tribe':
                    if (!in_array($player->tribe, $value)) {
                        return false;
                    }
                    break;
                case 'min_gold':
                    if ($player->gold < $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    public function startTournament(): bool
    {
        if ($this->status !== 'registration') {
            return false;
        }

        if ($this->participants()->count() < $this->min_participants) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'start_time' => now(),
        ]);

        return true;
    }

    public function endTournament(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'end_time' => now(),
        ]);

        // Award prizes
        $this->awardPrizes();

        return true;
    }

    protected function awardPrizes(): void
    {
        $prizes = $this->prizes ?? [];
        $participants = $this->participants()
            ->orderBy('final_rank')
            ->get();

        foreach ($participants as $participant) {
            $rank = $participant->final_rank;
            if (isset($prizes[$rank])) {
                $participant->update(['rewards' => $prizes[$rank]]);
                // TODO: Actually give rewards to player
            }
        }
    }

    public function getTypeDisplayNameAttribute(): string
    {
        return match($this->type) {
            'pvp' => 'Player vs Player',
            'pve' => 'Player vs Environment',
            'raid' => 'Raid Tournament',
            'defense' => 'Defense Challenge',
            'speed' => 'Speed Competition',
            'endurance' => 'Endurance Test',
            'resource_race' => 'Resource Race',
            'building_contest' => 'Building Contest',
            default => ucfirst($this->type)
        };
    }

    public function getFormatDisplayNameAttribute(): string
    {
        return match($this->format) {
            'single_elimination' => 'Single Elimination',
            'double_elimination' => 'Double Elimination',
            'round_robin' => 'Round Robin',
            'swiss' => 'Swiss System',
            'bracket' => 'Bracket System',
            'race' => 'Race Format',
            default => ucfirst($this->format)
        };
    }

    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'upcoming' => 'Upcoming',
            'registration' => 'Open for Registration',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status)
        };
    }

    public function getParticipantCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getRemainingSlotsAttribute(): int
    {
        return max(0, $this->max_participants - $this->participant_count);
    }

    public function getRegistrationProgressAttribute(): float
    {
        return ($this->participant_count / $this->max_participants) * 100;
    }

    public function getTimeRemainingAttribute(): ?int
    {
        if ($this->status === 'registration' && $this->registration_end) {
            return max(0, $this->registration_end->diffInMinutes(now()));
        }

        if ($this->status === 'active' && $this->end_time) {
            return max(0, $this->end_time->diffInMinutes(now()));
        }

        return null;
    }

    public function getTimeRemainingFormattedAttribute(): ?string
    {
        $minutes = $this->time_remaining;
        
        if ($minutes === null) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours} hours";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days} days";
    }
}
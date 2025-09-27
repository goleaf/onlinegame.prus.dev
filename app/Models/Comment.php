<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class Comment extends Model implements Auditable
{
    use HasFactory, AuditableTrait, Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $commentable_id;
    public string $commentable_type;
    public int $user_id;
    public ?int $parent_id;
    public string $content;
    public bool $is_approved;
    public bool $is_pinned;
    public ?array $metadata;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'user_id',
        'parent_id',
        'content',
        'is_approved',
        'is_pinned',
        'metadata',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_pinned' => 'boolean',
        'metadata' => 'array',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function approvedReplies(): HasMany
    {
        return $this->replies()->where('is_approved', true);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('commentable_type', get_class($model))
                    ->where('commentable_id', $model->id);
    }

    // Helper methods
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        
        return $depth;
    }

    public function canBeRepliedTo(): bool
    {
        return $this->getDepth() < 3; // Limit nesting to 3 levels
    }
}

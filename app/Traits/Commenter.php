<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Commenter
{
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->approved();
    }

    public function getCommentsCount(): int
    {
        return $this->approvedComments()->count();
    }

    public function getRecentComments(int $limit = 10)
    {
        return $this
            ->approvedComments()
            ->with(['commentable', 'parent'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function canComment(): bool
    {
        return true;  // Override in specific models if needed
    }

    public function hasCommentedOn($model): bool
    {
        return $this
            ->comments()
            ->where('commentable_type', get_class($model))
            ->where('commentable_id', $model->id)
            ->exists();
    }
}

<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Commentable
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'desc');
    }

    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved();
    }

    public function topLevelComments(): MorphMany
    {
        return $this->approvedComments()->topLevel();
    }

    public function pinnedComments(): MorphMany
    {
        return $this->comments()->pinned();
    }

    public function getCommentsCount(): int
    {
        return $this->approvedComments()->count();
    }

    public function getRepliesCount(): int
    {
        return $this->approvedComments()
            ->whereNotNull('parent_id')
            ->count();
    }

    public function getTotalCommentsCount(): int
    {
        return $this->getCommentsCount() + $this->getRepliesCount();
    }

    public function addComment(string $content, int $userId, ?int $parentId = null): Comment
    {
        return $this->comments()->create([
            'user_id' => $userId,
            'parent_id' => $parentId,
            'content' => $content,
            'is_approved' => true,
        ]);
    }

    public function canBeCommentedOn(): bool
    {
        return true; // Override in specific models if needed
    }
}

<?php

namespace App\Livewire;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class CommentList extends Component
{
    public Model $model;
    public $comments = [];

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->loadComments();
    }

    public function loadComments()
    {
        $this->comments = $this->model->topLevelComments()
            ->with(['user', 'approvedReplies.user'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);
        
        // Check if user can delete this comment
        if ($comment->user_id !== auth()->id() && !auth()->user()->can('delete-comments')) {
            $this->dispatch('error', 'You are not authorized to delete this comment.');
            return;
        }

        $comment->delete();
        $this->loadComments();
        $this->dispatch('comment-deleted');
    }

    public function togglePin($commentId)
    {
        if (!auth()->user()->can('pin-comments')) {
            $this->dispatch('error', 'You are not authorized to pin comments.');
            return;
        }

        $comment = Comment::findOrFail($commentId);
        $comment->update(['is_pinned' => !$comment->is_pinned]);
        $this->loadComments();
    }

    protected $listeners = ['comment-added' => 'loadComments', 'reply-added' => 'loadComments'];

    public function render()
    {
        return view('livewire.comment-list');
    }
}

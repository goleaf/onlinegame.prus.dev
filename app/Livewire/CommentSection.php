<?php

namespace App\Livewire;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CommentSection extends Component
{
    public Model $model;

    public $comments = [];

    public $newComment = '';

    public $replyingTo = null;

    public $showReplyForm = false;

    #[Validate('required|string|max:2000')]
    public $replyContent = '';

    public function mount(Model $model)
    {
        $this->model = $model;
        $this->loadComments();
    }

    public function loadComments()
    {
        $this->comments = $this
            ->model
            ->topLevelComments()
            ->with(['user', 'approvedReplies.user'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function addComment()
    {
        $this->validate(['newComment' => 'required|string|max:2000']);

        $this->model->addComment(
            $this->newComment,
            auth()->id(),
            $this->replyingTo
        );

        $this->newComment = '';
        $this->replyingTo = null;
        $this->showReplyForm = false;
        $this->loadComments();

        $this->dispatch('comment-added');
    }

    public function replyTo($commentId)
    {
        $this->replyingTo = $commentId;
        $this->showReplyForm = true;
        $this->replyContent = '';
    }

    public function cancelReply()
    {
        $this->replyingTo = null;
        $this->showReplyForm = false;
        $this->replyContent = '';
    }

    public function addReply()
    {
        $this->validate(['replyContent' => 'required|string|max:2000']);

        $this->model->addComment(
            $this->replyContent,
            auth()->id(),
            $this->replyingTo
        );

        $this->replyContent = '';
        $this->replyingTo = null;
        $this->showReplyForm = false;
        $this->loadComments();

        $this->dispatch('reply-added');
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // Check if user can delete this comment
        if ($comment->user_id !== auth()->id() && ! auth()->user()->can('delete-comments')) {
            $this->dispatch('error', 'You are not authorized to delete this comment.');

            return;
        }

        $comment->delete();
        $this->loadComments();
        $this->dispatch('comment-deleted');
    }

    public function togglePin($commentId)
    {
        if (! auth()->user()->can('pin-comments')) {
            $this->dispatch('error', 'You are not authorized to pin comments.');

            return;
        }

        $comment = Comment::findOrFail($commentId);
        $comment->update(['is_pinned' => ! $comment->is_pinned]);
        $this->loadComments();
    }

    public function render()
    {
        return view('livewire.comment-section');
    }
}

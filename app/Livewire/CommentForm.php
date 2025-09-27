<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CommentForm extends Component
{
    public Model $model;
    public $content = '';
    public $parentId = null;

    #[Validate('required|string|max:2000')]
    public $commentContent = '';

    public function mount(Model $model, $parentId = null)
    {
        $this->model = $model;
        $this->parentId = $parentId;
    }

    public function submit()
    {
        $this->validate();

        $this->model->addComment(
            $this->commentContent,
            auth()->id(),
            $this->parentId
        );

        $this->commentContent = '';
        $this->dispatch('comment-added');
    }

    public function render()
    {
        return view('livewire.comment-form');
    }
}

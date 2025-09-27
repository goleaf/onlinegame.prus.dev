<?php

namespace App\Livewire\Game;

use App\Models\Game\Task;
use Livewire\Component;

class TaskComments extends Component
{
    public Task $task;

    public function mount(Task $task)
    {
        $this->task = $task;
    }

    public function render()
    {
        return view('livewire.game.task-comments');
    }
}

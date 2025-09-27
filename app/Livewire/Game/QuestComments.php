<?php

namespace App\Livewire\Game;

use App\Models\Game\Quest;
use Livewire\Component;

class QuestComments extends Component
{
    public Quest $quest;

    public function mount(Quest $quest)
    {
        $this->quest = $quest;
    }

    public function render()
    {
        return view('livewire.game.quest-comments');
    }
}

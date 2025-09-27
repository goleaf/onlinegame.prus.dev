<?php

namespace App\Livewire\Game;

use App\Models\Game\Battle;
use Livewire\Component;

class BattleDetail extends Component
{
    public Battle $battle;

    public function mount(Battle $battle)
    {
        $this->battle = $battle;
    }

    public function render()
    {
        return view('livewire.game.battle-detail');
    }
}

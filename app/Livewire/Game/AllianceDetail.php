<?php

namespace App\Livewire\Game;

use App\Models\Game\Alliance;
use Livewire\Component;

class AllianceDetail extends Component
{
    public Alliance $alliance;

    public function mount(Alliance $alliance)
    {
        $this->alliance = $alliance;
    }

    public function render()
    {
        return view('livewire.game.alliance-detail');
    }
}

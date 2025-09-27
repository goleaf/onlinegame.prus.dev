<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use Livewire\Component;

class VillageDetail extends Component
{
    public Village $village;

    public function mount(Village $village)
    {
        $this->village = $village;
    }

    public function render()
    {
        return view('livewire.game.village-detail');
    }
}

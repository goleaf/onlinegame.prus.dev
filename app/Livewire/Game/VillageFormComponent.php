<?php

namespace App\Livewire\Game;

use App\Forms\VillageForm;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Support\Facades\Auth;
use JonPurvis\Squeaky\Rules\Clean;
use Livewire\Component;

class VillageFormComponent extends Component
{
    public $form;
    public $player;
    public $worlds;
    public $showForm = false;

    public function mount()
    {
        $this->player = Player::where('user_id', Auth::id())->first();
        $this->worlds = World::all();
        $this->form = new VillageForm(Village::class);
    }

    public function showCreateForm()
    {
        $this->showForm = true;
        $this->form = new VillageForm(Village::class);
    }

    public function hideForm()
    {
        $this->showForm = false;
    }

    public function store()
    {
        $validated = $this->validate([
            'form.name' => 'required|string|max:50',
            'form.player_id' => 'required|exists:players,id',
            'form.world_id' => 'required|exists:worlds,id',
            'form.x_coordinate' => 'required|integer|min:0|max:1000',
            'form.y_coordinate' => 'required|integer|min:0|max:1000',
            'form.population' => 'required|integer|min:0',
            'form.is_capital' => 'boolean',
            'form.is_active' => 'boolean',
        ]);

        $village = Village::create($validated['form']);

        session()->flash('success', 'Village created successfully!');
        $this->showForm = false;

        return redirect()->route('game.village', $village->id);
    }

    public function render()
    {
        return view('livewire.game.village-form-component');
    }
}

<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Battle;
use App\Models\Game\Movement;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Title('Player Details')]
#[Layout('layouts.app')]
class PlayerDetail extends Component
{
    public Player $player;
    public $villages = [];
    public $recentBattles = [];
    public $recentMovements = [];
    public $playerStats = [];

    public function mount(Player $player)
    {
        $this->player = $player->load(['villages', 'alliance']);
        $this->loadPlayerData();
    }

    public function loadPlayerData()
    {
        // Load player villages
        $this->villages = $this->player->villages()
            ->with(['coordinates'])
            ->orderBy('is_capital', 'desc')
            ->orderBy('name')
            ->get();

        // Load recent battles
        $this->recentBattles = Battle::where('attacker_id', $this->player->id)
            ->orWhere('defender_id', $this->player->id)
            ->with(['attacker', 'defender', 'village'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Load recent movements
        $this->recentMovements = Movement::where('player_id', $this->player->id)
            ->with(['player', 'targetVillage', 'originVillage'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate player stats
        $this->playerStats = [
            'total_villages' => $this->villages->count(),
            'total_points' => $this->player->points,
            'total_rank' => $this->player->rank,
            'alliance_name' => $this->player->alliance?->name ?? 'No Alliance',
            'tribe' => $this->player->tribe,
            'level' => $this->player->level,
            'last_active' => $this->player->last_active_at?->diffForHumans(),
        ];
    }

    public function render()
    {
        return view('livewire.game.player-detail');
    }
}

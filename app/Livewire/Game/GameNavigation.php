<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GameNavigation extends Component
{
    public $player;
    public $currentVillage;
    public $villages = [];
    public $gameStats = [];
    public $notifications = [];

    protected $listeners = ['refreshNavigation', 'villageChanged', 'statsUpdated'];

    public function mount()
    {
        $this->loadPlayerData();
    }

    public function loadPlayerData()
    {
        $user = Auth::user();
        $this->player = Player::where('user_id', $user->id)->first();

        if ($this->player) {
            $this->villages = $this->player->villages()->get();
            $this->currentVillage = $this->villages->first();
            $this->loadGameStats();
            $this->loadNotifications();
        }
    }

    public function loadGameStats()
    {
        if ($this->player) {
            $this->gameStats = [
                'total_villages' => $this->player->villages()->count(),
                'total_points' => $this->player->points ?? 0,
                'alliance_name' => $this->player->alliance?->name ?? 'No Alliance',
                'online_status' => $this->player->is_online ? 'Online' : 'Offline',
            ];
        }
    }

    public function loadNotifications()
    {
        // Load recent game events, battles, trades, etc.
        $this->notifications = [
            'new_messages' => 0,
            'battle_reports' => 0,
            'trade_offers' => 0,
            'quest_updates' => 0,
        ];
    }

    public function selectVillage($villageId)
    {
        $this->currentVillage = $this->villages->find($villageId);
        $this->dispatch('villageChanged', ['village' => $this->currentVillage]);
    }

    public function refreshNavigation()
    {
        $this->loadPlayerData();
    }

    public function render()
    {
        return view('livewire.game.game-navigation', [
            'player' => $this->player,
            'currentVillage' => $this->currentVillage,
            'villages' => $this->villages,
            'gameStats' => $this->gameStats,
            'notifications' => $this->notifications,
        ]);
    }
}

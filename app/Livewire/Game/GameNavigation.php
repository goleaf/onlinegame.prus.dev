<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Services\QueryOptimizationService;
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
        $this->player = Player::where('user_id', $user->id)
            ->with(['villages' => function ($query) {
                $query->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as troop_count,
                    (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
                ');
            }, 'alliance:id,name'])
            ->first();

        if ($this->player) {
            $this->villages = $this->player->villages;
            $this->currentVillage = $this->villages->first();
            $this->loadGameStats();
            $this->loadNotifications();
        }
    }

    public function loadGameStats()
    {
        if ($this->player) {
            // Use optimized query with selectRaw for village stats
            $villageStats = $this
                ->player
                ->villages()
                ->selectRaw('
                    COUNT(*) as total_villages,
                    SUM(population) as total_population,
                    AVG(population) as avg_population,
                    SUM(culture_points) as total_culture_points
                ')
                ->first();

            $this->gameStats = [
                'total_villages' => $villageStats->total_villages ?? 0,
                'total_population' => $villageStats->total_population ?? 0,
                'avg_population' => round($villageStats->avg_population ?? 0, 2),
                'total_culture_points' => $villageStats->total_culture_points ?? 0,
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

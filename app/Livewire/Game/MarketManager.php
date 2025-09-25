<?php

namespace App\Livewire\Game;

use App\Models\Game\MarketTrade;
use App\Models\Game\Resource;
use App\Models\Game\TradeOffer;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MarketManager extends Component
{
    use WithPagination;

    public $village;
    public $resources = [];
    public $marketTrades = [];
    public $myTrades = [];
    public $showCreateTradeModal = false;

    public $newTrade = [
        'offer_type' => 'wood',
        'offer_amount' => 0,
        'demand_type' => 'clay',
        'demand_amount' => 0,
        'ratio' => 1.0
    ];

    protected $listeners = ['refreshMarket', 'tradeCreated', 'tradeAccepted'];

    public function mount()
    {
        $user = Auth::user();
        $player = $user->player;

        if ($player) {
            $this->village = $player->villages()->with(['resources'])->first();
            $this->loadMarketData();
        }
    }

    public function loadMarketData()
    {
        if ($this->village) {
            $this->resources = $this->village->resources;
            $this->loadMarketTrades();
            $this->loadMyTrades();
        }
    }

    public function loadMarketTrades()
    {
        $this->marketTrades = MarketTrade::with(['player', 'village'])
            ->where('status', 'active')
            ->where('village_id', '!=', $this->village->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function loadMyTrades()
    {
        $this->myTrades = MarketTrade::with(['village'])
            ->where('player_id', $this->village->player_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createTrade()
    {
        $this->validate([
            'newTrade.offer_type' => 'required|in:wood,clay,iron,crop',
            'newTrade.offer_amount' => 'required|integer|min:1',
            'newTrade.demand_type' => 'required|in:wood,clay,iron,crop',
            'newTrade.demand_amount' => 'required|integer|min:1',
        ]);

        // Check if player has enough resources
        $offerResource = $this->resources->where('type', $this->newTrade['offer_type'])->first();
        if (!$offerResource || $offerResource->amount < $this->newTrade['offer_amount']) {
            $this->dispatch('tradeError', ['message' => 'Not enough ' . $this->newTrade['offer_type'] . ' resources']);
            return;
        }

        try {
            // Calculate ratio
            $ratio = $this->newTrade['demand_amount'] / $this->newTrade['offer_amount'];

            // Create trade
            $trade = MarketTrade::create([
                'player_id' => $this->village->player_id,
                'village_id' => $this->village->id,
                'offer_type' => $this->newTrade['offer_type'],
                'offer_amount' => $this->newTrade['offer_amount'],
                'demand_type' => $this->newTrade['demand_type'],
                'demand_amount' => $this->newTrade['demand_amount'],
                'ratio' => $ratio,
                'status' => 'active',
                'expires_at' => now()->addDays(7)
            ]);

            // Deduct offered resources
            $offerResource->decrement('amount', $this->newTrade['offer_amount']);

            $this->showCreateTradeModal = false;
            $this->newTrade = [
                'offer_type' => 'wood',
                'offer_amount' => 0,
                'demand_type' => 'clay',
                'demand_amount' => 0,
                'ratio' => 1.0
            ];

            $this->loadMarketData();
            $this->dispatch('tradeCreated', ['message' => 'Trade created successfully!']);
        } catch (\Exception $e) {
            $this->dispatch('tradeError', ['message' => $e->getMessage()]);
        }
    }

    public function acceptTrade($tradeId)
    {
        $trade = MarketTrade::find($tradeId);
        if (!$trade || $trade->status !== 'active') {
            return;
        }

        // Check if player has enough demanded resources
        $demandResource = $this->resources->where('type', $trade->demand_type)->first();
        if (!$demandResource || $demandResource->amount < $trade->demand_amount) {
            $this->dispatch('tradeError', ['message' => 'Not enough ' . $trade->demand_type . ' resources']);
            return;
        }

        try {
            // Create trade offer
            $tradeOffer = TradeOffer::create([
                'market_trade_id' => $trade->id,
                'buyer_id' => $this->village->player_id,
                'seller_id' => $trade->player_id,
                'amount_traded' => $trade->offer_amount,
                'resources_exchanged' => [
                    'offered' => [$trade->offer_type => $trade->offer_amount],
                    'demanded' => [$trade->demand_type => $trade->demand_amount]
                ],
                'completed_at' => now()
            ]);

            // Exchange resources
            $demandResource->decrement('amount', $trade->demand_amount);
            $offerResource = $this->resources->where('type', $trade->offer_type)->first();
            if ($offerResource) {
                $offerResource->increment('amount', $trade->offer_amount);
            }

            // Update trade status
            $trade->update(['status' => 'completed']);

            $this->loadMarketData();
            $this->dispatch('tradeAccepted', ['message' => 'Trade completed successfully!']);
        } catch (\Exception $e) {
            $this->dispatch('tradeError', ['message' => $e->getMessage()]);
        }
    }

    public function cancelTrade($tradeId)
    {
        $trade = MarketTrade::find($tradeId);
        if ($trade && $trade->player_id === $this->village->player_id && $trade->status === 'active') {
            // Return resources
            $offerResource = $this->resources->where('type', $trade->offer_type)->first();
            if ($offerResource) {
                $offerResource->increment('amount', $trade->offer_amount);
            }

            $trade->update(['status' => 'cancelled']);
            $this->loadMarketData();
        }
    }

    public function updatedNewTradeOfferType()
    {
        $this->calculateRatio();
    }

    public function updatedNewTradeOfferAmount()
    {
        $this->calculateRatio();
    }

    public function updatedNewTradeDemandType()
    {
        $this->calculateRatio();
    }

    public function updatedNewTradeDemandAmount()
    {
        $this->calculateRatio();
    }

    public function calculateRatio()
    {
        if ($this->newTrade['offer_amount'] > 0 && $this->newTrade['demand_amount'] > 0) {
            $this->newTrade['ratio'] = round($this->newTrade['demand_amount'] / $this->newTrade['offer_amount'], 4);
        }
    }

    public function refreshMarket()
    {
        $this->loadMarketData();
    }

    public function render()
    {
        return view('livewire.game.market-manager', [
            'village' => $this->village,
            'resources' => $this->resources,
            'marketTrades' => $this->marketTrades,
            'myTrades' => $this->myTrades,
            'newTrade' => $this->newTrade
        ]);
    }
}

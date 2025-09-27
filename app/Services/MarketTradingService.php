<?php

namespace App\Services;

use App\Models\Game\MarketOffer;
use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MarketTradingService
{
    /**
     * Create a new market offer
     */
    public function createOffer(Village $village, array $offerData): MarketOffer
    {
        return DB::transaction(function () use ($village, $offerData) {
            // Validate offer data
            $this->validateOfferData($offerData);

            // Check if village has enough resources
            if (!$this->hasEnoughResources($village, $offerData['offering'])) {
                throw new \Exception('Insufficient resources for offer');
            }

            // Calculate market fee (5% of total value)
            $fee = $this->calculateMarketFee($offerData['offering'], $offerData['requesting']);

            // Create market offer
            $offer = MarketOffer::create([
                'village_id' => $village->id,
                'player_id' => $village->player_id,
                'offering' => $offerData['offering'],
                'requesting' => $offerData['requesting'],
                'ratio' => $offerData['ratio'] ?? 1.0,
                'fee' => $fee,
                'status' => 'active',
                'expires_at' => now()->addDays(7), // 7 days expiration
            ]);

            // Generate reference number
            $offer->generateReference();

            // Deduct resources from village
            $this->deductResources($village, $offerData['offering']);

            // Add market fee to village resources
            $this->addResources($village, ['crop' => $fee]);

            // Send notification about new market offer
            GameNotificationService::sendNotification(
                [$village->player->user_id],
                'market_offer_created',
                [
                    'offer_id' => $offer->id,
                    'reference' => $offer->reference_number,
                    'offering' => $offerData['offering'],
                    'requesting' => $offerData['requesting'],
                    'village_name' => $village->name,
                ]
            );

            Log::info('Market offer created', [
                'village_id' => $village->id,
                'offer_id' => $offer->id,
                'reference' => $offer->reference_number,
                'offering' => $offerData['offering'],
                'requesting' => $offerData['requesting'],
            ]);

            return $offer;
        });
    }

    /**
     * Accept a market offer
     */
    public function acceptOffer(MarketOffer $offer, Village $buyerVillage, int $quantity = 1): void
    {
        DB::transaction(function () use ($offer, $buyerVillage, $quantity) {
            // Check if offer is still active
            if ($offer->status !== 'active') {
                throw new \Exception('Offer is no longer active');
            }

            // Check if offer has expired
            if ($offer->expires_at && $offer->expires_at->isPast()) {
                throw new \Exception('Offer has expired');
            }

            // Calculate required resources
            $requiredResources = [];
            foreach ($offer->requesting as $resource => $amount) {
                $requiredResources[$resource] = $amount * $quantity;
            }

            // Check if buyer has enough resources
            if (!$this->hasEnoughResources($buyerVillage, $requiredResources)) {
                throw new \Exception('Insufficient resources to accept offer');
            }

            // Calculate offered resources
            $offeredResources = [];
            foreach ($offer->offering as $resource => $amount) {
                $offeredResources[$resource] = $amount * $quantity;
            }

            // Transfer resources
            $this->deductResources($buyerVillage, $requiredResources);
            $this->addResources($buyerVillage, $offeredResources);

            // Update offer status
            $offer->update([
                'status' => 'completed',
                'completed_at' => now(),
                'buyer_village_id' => $buyerVillage->id,
                'quantity_traded' => $quantity,
            ]);

            Log::info('Market offer accepted', [
                'offer_id' => $offer->id,
                'reference' => $offer->reference_number,
                'buyer_village_id' => $buyerVillage->id,
                'quantity' => $quantity,
                'traded_resources' => $offeredResources,
                'paid_resources' => $requiredResources,
            ]);
        });
    }

    /**
     * Cancel a market offer
     */
    public function cancelOffer(MarketOffer $offer): void
    {
        DB::transaction(function () use ($offer) {
            if ($offer->status !== 'active') {
                throw new \Exception('Cannot cancel inactive offer');
            }

            $village = $offer->village;

            // Refund resources (minus market fee)
            $refundResources = $offer->offering;
            $this->addResources($village, $refundResources);

            // Update offer status
            $offer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::info('Market offer cancelled', [
                'offer_id' => $offer->id,
                'reference' => $offer->reference_number,
                'village_id' => $village->id,
                'refunded_resources' => $refundResources,
            ]);
        });
    }

    /**
     * Get available market offers
     */
    public function getAvailableOffers(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = MarketOffer::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->with(['village', 'player']);

        // Apply filters
        if (isset($filters['offering_resource'])) {
            $query->whereJsonContains('offering->' . $filters['offering_resource'], '>', 0);
        }

        if (isset($filters['requesting_resource'])) {
            $query->whereJsonContains('requesting->' . $filters['requesting_resource'], '>', 0);
        }

        if (isset($filters['min_ratio'])) {
            $query->where('ratio', '>=', $filters['min_ratio']);
        }

        if (isset($filters['max_ratio'])) {
            $query->where('ratio', '<=', $filters['max_ratio']);
        }

        if (isset($filters['player_id'])) {
            $query->where('player_id', '!=', $filters['player_id']); // Exclude own offers
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get market statistics
     */
    public function getMarketStats(): array
    {
        $totalOffers = MarketOffer::count();
        $activeOffers = MarketOffer::where('status', 'active')->count();
        $completedOffers = MarketOffer::where('status', 'completed')->count();
        $cancelledOffers = MarketOffer::where('status', 'cancelled')->count();

        $totalVolume = MarketOffer::where('status', 'completed')
            ->sum('quantity_traded');

        $recentOffers = MarketOffer::where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_offers' => $totalOffers,
            'active_offers' => $activeOffers,
            'completed_offers' => $completedOffers,
            'cancelled_offers' => $cancelledOffers,
            'total_volume' => $totalVolume,
            'recent_offers' => $recentOffers,
        ];
    }

    /**
     * Process expired offers
     */
    public function processExpiredOffers(): int
    {
        $expiredOffers = MarketOffer::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        $processed = 0;
        foreach ($expiredOffers as $offer) {
            try {
                $this->cancelOffer($offer);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process expired offer', [
                    'offer_id' => $offer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Calculate market fee
     */
    private function calculateMarketFee(array $offering, array $requesting): int
    {
        // Calculate total value of offering (using base resource values)
        $baseValues = [
            'wood' => 1,
            'clay' => 1,
            'iron' => 1,
            'crop' => 1,
        ];

        $totalValue = 0;
        foreach ($offering as $resource => $amount) {
            $totalValue += $amount * ($baseValues[$resource] ?? 1);
        }

        // Market fee is 5% of total value, minimum 1 crop
        $fee = max(1, (int) ($totalValue * 0.05));

        return $fee;
    }

    /**
     * Validate offer data
     */
    private function validateOfferData(array $data): void
    {
        if (!isset($data['offering']) || !is_array($data['offering'])) {
            throw new \Exception('Invalid offering data');
        }

        if (!isset($data['requesting']) || !is_array($data['requesting'])) {
            throw new \Exception('Invalid requesting data');
        }

        $validResources = ['wood', 'clay', 'iron', 'crop'];

        foreach ($data['offering'] as $resource => $amount) {
            if (!in_array($resource, $validResources) || $amount <= 0) {
                throw new \Exception('Invalid offering resource or amount');
            }
        }

        foreach ($data['requesting'] as $resource => $amount) {
            if (!in_array($resource, $validResources) || $amount <= 0) {
                throw new \Exception('Invalid requesting resource or amount');
            }
        }
    }

    /**
     * Check if village has enough resources
     */
    private function hasEnoughResources(Village $village, array $resources): bool
    {
        foreach ($resources as $resource => $amount) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct resources from village
     */
    private function deductResources(Village $village, array $resources): void
    {
        foreach ($resources as $resource => $amount) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }
    }

    /**
     * Add resources to village
     */
    private function addResources(Village $village, array $resources): void
    {
        foreach ($resources as $resource => $amount) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->increment('amount', $amount);
            }
        }
    }
}

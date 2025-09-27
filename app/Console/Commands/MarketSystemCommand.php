<?php

namespace App\Console\Commands;

use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarketSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:manage 
                            {action : Action to perform (generate|process|cleanup|stats)}
                            {--player-id= : Specific player ID}
                            {--world-id= : Specific world ID}
                            {--resource-type= : Resource type filter}
                            {--force : Force the operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage market system - generate offers, process trades, cleanup expired offers, and show statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info('🏪 Market System Management');
        $this->info('===========================');

        switch ($action) {
            case 'generate':
                $this->generateMarketOffers();
                break;
            case 'process':
                $this->processMarketTrades();
                break;
            case 'cleanup':
                $this->cleanupExpiredOffers();
                break;
            case 'stats':
                $this->showMarketStats();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Generate market offers for players.
     */
    protected function generateMarketOffers(): void
    {
        $this->info('📈 Generating market offers...');

        $worldId = $this->option('world-id');
        $playerId = $this->option('player-id');
        $resourceType = $this->option('resource-type');

        $query = Player::with(['villages.resources']);
        
        if ($worldId) {
            $query->where('world_id', $worldId);
        }
        
        if ($playerId) {
            $query->where('id', $playerId);
        }

        $players = $query->get();

        $generatedCount = 0;

        foreach ($players as $player) {
            $generated = $this->generateOffersForPlayer($player, $resourceType);
            $generatedCount += $generated;
        }

        $this->info("✅ Generated {$generatedCount} market offers");
    }

    /**
     * Generate offers for a specific player.
     */
    protected function generateOffersForPlayer(Player $player, ?string $resourceType = null): int
    {
        $generatedCount = 0;
        $resourceTypes = $resourceType ? [$resourceType] : ['wood', 'clay', 'iron', 'crop'];

        foreach ($player->villages as $village) {
            foreach ($resourceTypes as $type) {
                $resource = $village->resources()->where('type', $type)->first();
                
                if (!$resource || $resource->amount < 1000) {
                    continue; // Skip if not enough resources
                }

                // Generate sell offers (player has excess resources)
                if ($resource->amount > 5000) {
                    $offerAmount = min($resource->amount - 2000, 10000);
                    $demandType = $this->getRandomResourceType($type);
                    $demandAmount = $this->calculateDemandAmount($offerAmount, $type, $demandType);

                    DB::table('market_trades')->insert([
                        'player_id' => $player->id,
                        'village_id' => $village->id,
                        'offer_type' => $type,
                        'offer_amount' => $offerAmount,
                        'demand_type' => $demandType,
                        'demand_amount' => $demandAmount,
                        'ratio' => $demandAmount / $offerAmount,
                        'status' => 'active',
                        'expires_at' => now()->addHours(24),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $generatedCount++;
                    $this->line("  → Created sell offer: {$offerAmount} {$type} for {$demandAmount} {$demandType}");
                }

                // Generate buy offers (player needs resources)
                if ($resource->amount < 2000) {
                    $demandAmount = 5000 - $resource->amount;
                    $offerType = $this->getRandomResourceType($type);
                    $offerAmount = $this->calculateOfferAmount($demandAmount, $type, $offerType);

                    // Check if player has enough of the offer resource
                    $offerResource = $village->resources()->where('type', $offerType)->first();
                    if ($offerResource && $offerResource->amount >= $offerAmount) {
                        DB::table('market_trades')->insert([
                            'player_id' => $player->id,
                            'village_id' => $village->id,
                            'offer_type' => $offerType,
                            'offer_amount' => $offerAmount,
                            'demand_type' => $type,
                            'demand_amount' => $demandAmount,
                            'ratio' => $offerAmount / $demandAmount,
                            'status' => 'active',
                            'expires_at' => now()->addHours(24),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $generatedCount++;
                        $this->line("  → Created buy offer: {$offerAmount} {$offerType} for {$demandAmount} {$type}");
                    }
                }
            }
        }

        return $generatedCount;
    }

    /**
     * Get a random resource type different from the given one.
     */
    protected function getRandomResourceType(string $exclude): string
    {
        $types = array_diff(['wood', 'clay', 'iron', 'crop'], [$exclude]);
        return $types[array_rand($types)];
    }

    /**
     * Calculate demand amount based on resource types and ratios.
     */
    protected function calculateDemandAmount(int $offerAmount, string $offerType, string $demandType): int
    {
        // Base ratios (can be adjusted based on game balance)
        $ratios = [
            'wood' => ['clay' => 1.0, 'iron' => 0.8, 'crop' => 1.2],
            'clay' => ['wood' => 1.0, 'iron' => 0.8, 'crop' => 1.2],
            'iron' => ['wood' => 1.25, 'clay' => 1.25, 'crop' => 1.5],
            'crop' => ['wood' => 0.83, 'clay' => 0.83, 'iron' => 0.67],
        ];

        $baseRatio = $ratios[$offerType][$demandType] ?? 1.0;
        $randomFactor = 0.8 + (rand(0, 40) / 100); // 80-120% variation

        return (int) round($offerAmount * $baseRatio * $randomFactor);
    }

    /**
     * Calculate offer amount based on demand amount and resource types.
     */
    protected function calculateOfferAmount(int $demandAmount, string $demandType, string $offerType): int
    {
        $ratios = [
            'wood' => ['clay' => 1.0, 'iron' => 0.8, 'crop' => 1.2],
            'clay' => ['wood' => 1.0, 'iron' => 0.8, 'crop' => 1.2],
            'iron' => ['wood' => 1.25, 'clay' => 1.25, 'crop' => 1.5],
            'crop' => ['wood' => 0.83, 'clay' => 0.83, 'iron' => 0.67],
        ];

        $baseRatio = $ratios[$demandType][$offerType] ?? 1.0;
        $randomFactor = 0.8 + (rand(0, 40) / 100); // 80-120% variation

        return (int) round($demandAmount * $baseRatio * $randomFactor);
    }

    /**
     * Process market trades and match offers.
     */
    protected function processMarketTrades(): void
    {
        $this->info('🔄 Processing market trades...');

        $activeOffers = DB::table('market_trades')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->get();

        $processedCount = 0;

        foreach ($activeOffers as $offer) {
            $matches = $this->findMatchingOffers($offer);
            
            foreach ($matches as $match) {
                if ($this->executeTrade($offer, $match)) {
                    $processedCount++;
                }
            }
        }

        $this->info("✅ Processed {$processedCount} trades");
    }

    /**
     * Find matching offers for a given offer.
     */
    protected function findMatchingOffers($offer): array
    {
        return DB::table('market_trades')
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('id', '!=', $offer->id)
            ->where('player_id', '!=', $offer->player_id)
            ->where('offer_type', $offer->demand_type)
            ->where('demand_type', $offer->offer_type)
            ->where('offer_amount', '>=', $offer->demand_amount)
            ->where('demand_amount', '<=', $offer->offer_amount)
            ->get()
            ->toArray();
    }

    /**
     * Execute a trade between two offers.
     */
    protected function executeTrade($offer1, $offer2): bool
    {
        try {
            DB::beginTransaction();

            // Calculate trade amounts
            $tradeAmount = min($offer1->offer_amount, $offer2->demand_amount);
            $resourcesExchanged = [
                'from_seller' => [
                    'player_id' => $offer1->player_id,
                    'resource_type' => $offer1->offer_type,
                    'amount' => $tradeAmount,
                ],
                'to_buyer' => [
                    'player_id' => $offer2->player_id,
                    'resource_type' => $offer1->offer_type,
                    'amount' => $tradeAmount,
                ],
                'from_buyer' => [
                    'player_id' => $offer2->player_id,
                    'resource_type' => $offer2->offer_type,
                    'amount' => $tradeAmount,
                ],
                'to_seller' => [
                    'player_id' => $offer1->player_id,
                    'resource_type' => $offer2->offer_type,
                    'amount' => $tradeAmount,
                ],
            ];

            // Update resources
            $this->transferResources($offer1->player_id, $offer1->offer_type, $tradeAmount, 'subtract');
            $this->transferResources($offer2->player_id, $offer1->offer_type, $tradeAmount, 'add');
            $this->transferResources($offer2->player_id, $offer2->offer_type, $tradeAmount, 'subtract');
            $this->transferResources($offer1->player_id, $offer2->offer_type, $tradeAmount, 'add');

            // Create trade record
            DB::table('trade_offers')->insert([
                'market_trade_id' => $offer1->id,
                'buyer_id' => $offer2->player_id,
                'seller_id' => $offer1->player_id,
                'amount_traded' => $tradeAmount,
                'resources_exchanged' => json_encode($resourcesExchanged),
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update offer status
            DB::table('market_trades')
                ->where('id', $offer1->id)
                ->update(['status' => 'completed', 'updated_at' => now()]);
            
            DB::table('market_trades')
                ->where('id', $offer2->id)
                ->update(['status' => 'completed', 'updated_at' => now()]);

            DB::commit();

            $this->line("  → Trade completed: {$tradeAmount} {$offer1->offer_type} ↔ {$tradeAmount} {$offer2->offer_type}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Trade failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer resources between players.
     */
    protected function transferResources(int $playerId, string $resourceType, int $amount, string $operation): void
    {
        $player = Player::find($playerId);
        if (!$player) {
            return;
        }

        $village = $player->villages()->first();
        if (!$village) {
            return;
        }

        $resource = $village->resources()->where('type', $resourceType)->first();
        if (!$resource) {
            return;
        }

        if ($operation === 'add') {
            $resource->increment('amount', $amount);
        } else {
            $resource->decrement('amount', $amount);
        }
    }

    /**
     * Cleanup expired offers.
     */
    protected function cleanupExpiredOffers(): void
    {
        $this->info('🧹 Cleaning up expired offers...');

        $expiredCount = DB::table('market_trades')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        $this->info("✅ Cleaned up {$expiredCount} expired offers");
    }

    /**
     * Show market statistics.
     */
    protected function showMarketStats(): void
    {
        $this->info('📊 Market Statistics');
        $this->info('===================');

        $stats = [
            'Active Offers' => DB::table('market_trades')->where('status', 'active')->count(),
            'Completed Trades' => DB::table('market_trades')->where('status', 'completed')->count(),
            'Cancelled Offers' => DB::table('market_trades')->where('status', 'cancelled')->count(),
            'Total Trade Volume' => DB::table('trade_offers')->sum('amount_traded'),
        ];

        foreach ($stats as $label => $value) {
            $this->line("  {$label}: {$value}");
        }

        // Show resource breakdown
        $this->info('');
        $this->info('Resource Breakdown:');
        $resourceStats = DB::table('market_trades')
            ->select('offer_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(offer_amount) as total_amount'))
            ->where('status', 'active')
            ->groupBy('offer_type')
            ->get();

        foreach ($resourceStats as $stat) {
            $this->line("  {$stat->offer_type}: {$stat->count} offers, {$stat->total_amount} total");
        }
    }
}

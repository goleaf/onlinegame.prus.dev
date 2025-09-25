<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\MarketManager;
use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MarketManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $this->actingAs($user);
    }

    public function test_can_render_market_manager()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertStatus(200)
            ->assertSee('Market Manager');
    }

    public function test_loads_market_data_on_mount()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('village', $village)
            ->assertSet('offers', [])
            ->assertSet('myOffers', []);
    }

    public function test_can_toggle_real_time_updates()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_refresh()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_can_set_refresh_interval()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('refreshInterval', 10)
            ->call('setRefreshInterval', 15)
            ->assertSet('refreshInterval', 15)
            ->call('setRefreshInterval', 0)
            ->assertSet('refreshInterval', 5)
            ->call('setRefreshInterval', 100)
            ->assertSet('refreshInterval', 60);
    }

    public function test_can_set_game_speed()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_offer()
    {
        $village = Village::first();
        $offer = MarketOffer::factory()->create(['world_id' => $village->world_id]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('selectedOffer', null)
            ->assertSet('showDetails', false)
            ->call('selectOffer', $offer->id)
            ->assertSet('selectedOffer.id', $offer->id)
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_can_set_offer_type()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('selectedType', 'buy')
            ->call('setOfferType', 'sell')
            ->assertSet('selectedType', 'sell');
    }

    public function test_can_set_resource_type()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('selectedResource', 'wood')
            ->call('setResourceType', 'iron')
            ->assertSet('selectedResource', 'iron');
    }

    public function test_can_set_offer_quantity()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('offerQuantity', 1)
            ->call('setOfferQuantity', 100)
            ->assertSet('offerQuantity', 100)
            ->call('setOfferQuantity', 0)
            ->assertSet('offerQuantity', 1)
            ->call('setOfferQuantity', 20000)
            ->assertSet('offerQuantity', 10000);
    }

    public function test_can_set_offer_price()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('offerPrice', 1)
            ->call('setOfferPrice', 50)
            ->assertSet('offerPrice', 50)
            ->call('setOfferPrice', 0)
            ->assertSet('offerPrice', 1)
            ->call('setOfferPrice', 2000000)
            ->assertSet('offerPrice', 1000000);
    }

    public function test_can_set_offer_duration()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('offerDuration', 24)
            ->call('setOfferDuration', 48)
            ->assertSet('offerDuration', 48)
            ->call('setOfferDuration', 0)
            ->assertSet('offerDuration', 1)
            ->call('setOfferDuration', 200)
            ->assertSet('offerDuration', 168);
    }

    public function test_can_filter_by_type()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('filterByType', null)
            ->call('filterByType', 'sell')
            ->assertSet('filterByType', 'sell');
    }

    public function test_can_filter_by_resource()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('filterByResource', null)
            ->call('filterByResource', 'iron')
            ->assertSet('filterByResource', 'iron');
    }

    public function test_can_clear_filters()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->set('filterByType', 'sell')
            ->set('filterByResource', 'iron')
            ->set('searchQuery', 'test')
            ->set('showOnlyActive', true)
            ->set('showOnlyMyOffers', true)
            ->set('showOnlyExpired', true)
            ->call('clearFilters')
            ->assertSet('filterByType', null)
            ->assertSet('filterByResource', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyActive', false)
            ->assertSet('showOnlyMyOffers', false)
            ->assertSet('showOnlyExpired', false);
    }

    public function test_can_sort_offers()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('sortBy', 'created_at')
            ->assertSet('sortOrder', 'desc')
            ->call('sortOffers', 'price_per_unit')
            ->assertSet('sortBy', 'price_per_unit')
            ->assertSet('sortOrder', 'desc')
            ->call('sortOffers', 'price_per_unit')
            ->assertSet('sortBy', 'price_per_unit')
            ->assertSet('sortOrder', 'asc');
    }

    public function test_can_search_offers()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->set('searchQuery', 'wood offer')
            ->call('searchOffers')
            ->assertSet('searchQuery', 'wood offer');
    }

    public function test_can_toggle_active_filter()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('showOnlyActive', false)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', true)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', false);
    }

    public function test_can_toggle_my_offers_filter()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('showOnlyMyOffers', false)
            ->call('toggleMyOffersFilter')
            ->assertSet('showOnlyMyOffers', true)
            ->call('toggleMyOffersFilter')
            ->assertSet('showOnlyMyOffers', false);
    }

    public function test_can_toggle_expired_filter()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('showOnlyExpired', false)
            ->call('toggleExpiredFilter')
            ->assertSet('showOnlyExpired', true)
            ->call('toggleExpiredFilter')
            ->assertSet('showOnlyExpired', false);
    }

    public function test_can_create_offer()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->set('selectedType', 'sell')
            ->set('selectedResource', 'wood')
            ->set('offerQuantity', 100)
            ->set('offerPrice', 10)
            ->set('offerDuration', 24)
            ->call('createOffer')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer created successfully')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_create_offer_with_invalid_parameters()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->set('offerQuantity', 0)
            ->set('offerPrice', 0)
            ->call('createOffer')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Invalid offer parameters')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_accept_offer()
    {
        $village = Village::first();
        $offer = MarketOffer::factory()->create([
            'world_id' => $village->world_id,
            'seller_id' => $village->player_id + 1,  // Different player
            'status' => 'active'
        ]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('acceptOffer', $offer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Successfully bought 1 wood')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_accept_nonexistent_offer()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('acceptOffer', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_accept_own_offer()
    {
        $village = Village::first();
        $offer = MarketOffer::factory()->create([
            'world_id' => $village->world_id,
            'seller_id' => $village->player_id,
            'status' => 'active'
        ]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('acceptOffer', $offer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Cannot accept your own offer')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_cancel_offer()
    {
        $village = Village::first();
        $offer = MarketOffer::factory()->create([
            'world_id' => $village->world_id,
            'seller_id' => $village->player_id,
            'status' => 'active'
        ]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('cancelOffer', $offer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer cancelled successfully')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_cancel_someone_else_offer()
    {
        $village = Village::first();
        $offer = MarketOffer::factory()->create([
            'world_id' => $village->world_id,
            'seller_id' => $village->player_id + 1,  // Different player
            'status' => 'active'
        ]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('cancelOffer', $offer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Cannot cancel someone else's offer")
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_get_resource_icon()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $this->assertEquals('🪵', $component->instance()->getResourceIcon('wood'));
        $this->assertEquals('🏺', $component->instance()->getResourceIcon('clay'));
        $this->assertEquals('⛏️', $component->instance()->getResourceIcon('iron'));
        $this->assertEquals('🌾', $component->instance()->getResourceIcon('crop'));
        $this->assertEquals('📦', $component->instance()->getResourceIcon('unknown'));
    }

    public function test_get_resource_color()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $this->assertEquals('brown', $component->instance()->getResourceColor('wood'));
        $this->assertEquals('orange', $component->instance()->getResourceColor('clay'));
        $this->assertEquals('gray', $component->instance()->getResourceColor('iron'));
        $this->assertEquals('green', $component->instance()->getResourceColor('crop'));
        $this->assertEquals('blue', $component->instance()->getResourceColor('unknown'));
    }

    public function test_get_offer_status()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $offer = ['status' => 'active'];
        $this->assertEquals('Active', $component->instance()->getOfferStatus($offer));

        $offer = ['status' => 'completed'];
        $this->assertEquals('Completed', $component->instance()->getOfferStatus($offer));

        $offer = ['status' => 'cancelled'];
        $this->assertEquals('Cancelled', $component->instance()->getOfferStatus($offer));

        $offer = ['status' => 'expired'];
        $this->assertEquals('Expired', $component->instance()->getOfferStatus($offer));
    }

    public function test_get_offer_color()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $offer = ['status' => 'active'];
        $this->assertEquals('green', $component->instance()->getOfferColor($offer));

        $offer = ['status' => 'completed'];
        $this->assertEquals('blue', $component->instance()->getOfferColor($offer));

        $offer = ['status' => 'cancelled'];
        $this->assertEquals('red', $component->instance()->getOfferColor($offer));

        $offer = ['status' => 'expired'];
        $this->assertEquals('gray', $component->instance()->getOfferColor($offer));
    }

    public function test_get_time_remaining()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $offer = ['status' => 'active', 'expires_at' => now()->addHours(2)];
        $this->assertNotEquals('N/A', $component->instance()->getTimeRemaining($offer));

        $offer = ['status' => 'completed', 'expires_at' => now()->addHours(2)];
        $this->assertEquals('N/A', $component->instance()->getTimeRemaining($offer));
    }

    public function test_notification_system()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $village = Village::first();

        $component = Livewire::test(MarketManager::class, ['village' => $village]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_calculates_market_stats()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('marketStats', [])
            ->call('calculateMarketStats')
            ->assertSet('marketStats', []);
    }

    public function test_calculates_trading_history()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('tradingHistory', [])
            ->call('calculateTradingHistory')
            ->assertSet('tradingHistory', []);
    }

    public function test_calculates_price_history()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('priceHistory', [])
            ->call('calculatePriceHistory')
            ->assertSet('priceHistory', []);
    }

    public function test_calculates_offer_stats()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('offerStats', [])
            ->call('calculateOfferStats')
            ->assertSet('offerStats', []);
    }

    public function test_calculates_trading_volume()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('tradingVolume', [])
            ->call('calculateTradingVolume')
            ->assertSet('tradingVolume', []);
    }

    public function test_calculates_average_prices()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('averagePrices', [])
            ->call('calculateAveragePrices')
            ->assertSet('averagePrices', []);
    }

    public function test_calculates_market_trends()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('marketTrends', [])
            ->call('calculateMarketTrends')
            ->assertSet('marketTrends', []);
    }

    public function test_handles_game_tick_processed()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_offer_created()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('offerCreated', ['offer_id' => 1, 'type' => 'sell', 'resource_type' => 'wood', 'quantity' => 100])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'New offer created')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_offer_updated()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('offerUpdated', ['offer_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_offer_cancelled()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('offerCancelled', ['offer_id' => 1, 'seller_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer cancelled')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_offer_expired()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('offerExpired', ['offer_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Offer expired')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_trade_completed()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('tradeCompleted', ['offer_id' => 1, 'buyer_id' => 1, 'seller_id' => 2])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Trade completed')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_village_selected()
    {
        $village = Village::first();
        $newVillage = Village::factory()->create(['player_id' => $village->player_id]);

        Livewire::test(MarketManager::class, ['village' => $village])
            ->dispatch('villageSelected', $newVillage->id)
            ->assertSet('village.id', $newVillage->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - market data updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $village = Village::first();

        Livewire::test(MarketManager::class, ['village' => $village])
            ->assertSee('Market Manager')
            ->assertSee('Offers')
            ->assertSee('Trading');
    }

    public function test_handles_missing_village()
    {
        Livewire::test(MarketManager::class, ['village' => null])
            ->assertSet('village', null);
    }
}

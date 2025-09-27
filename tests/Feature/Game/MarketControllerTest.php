<?php

namespace Tests\Feature\Game;

use Tests\TestCase;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\MarketOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class MarketControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $player;
    protected $village;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_get_market_offers()
    {
        MarketOffer::factory()->count(5)->create();

        $response = $this->getJson('/game/api/market/offers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'offer_type',
                            'resource_type',
                            'resource_amount',
                            'exchange_rate',
                            'status',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_filter_market_offers_by_resource_type()
    {
        MarketOffer::factory()->create(['resource_type' => 'wood']);
        MarketOffer::factory()->create(['resource_type' => 'clay']);

        $response = $this->getJson('/game/api/market/offers?resource_type=wood');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_create_market_offer()
    {
        // Set player resources
        $this->player->update(['wood' => 1000]);

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'description' => 'Selling wood for clay'
        ];

        $response = $this->postJson('/game/api/market/offers', $offerData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'offer' => [
                        'id',
                        'player_id',
                        'offer_type',
                        'resource_type',
                        'resource_amount',
                        'exchange_rate'
                    ]
                ]);

        $this->assertDatabaseHas('market_offers', [
            'player_id' => $this->player->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5
        ]);
    }

    /** @test */
    public function it_can_accept_market_offer()
    {
        $seller = Player::factory()->create();
        $seller->update(['wood' => 1000]);
        
        $offer = MarketOffer::factory()->create([
            'player_id' => $seller->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'total_amount' => 750,
            'status' => 'active'
        ]);

        // Set buyer resources
        $this->player->update(['clay' => 1000]);

        $acceptData = [
            'accept_amount' => 250
        ];

        $response = $this->postJson("/game/api/market/offers/{$offer->id}/accept", $acceptData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'trade_details' => [
                        'offer_id',
                        'accepted_amount',
                        'received_amount',
                        'cost'
                    ]
                ]);

        // Check that resources were transferred
        $this->player->refresh();
        $seller->refresh();
        
        $this->assertEquals(750, $this->player->wood); // Received 250 wood
        $this->assertEquals(750, $this->player->clay); // Paid 375 clay (250 * 1.5)
        $this->assertEquals(1375, $seller->clay); // Received 375 clay
        $this->assertEquals(750, $seller->wood); // Still has 750 wood
    }

    /** @test */
    public function it_can_cancel_market_offer()
    {
        $this->player->update(['wood' => 1000]);
        
        $offer = MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'status' => 'active'
        ]);

        $response = $this->deleteJson("/game/api/market/offers/{$offer->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertDatabaseHas('market_offers', [
            'id' => $offer->id,
            'status' => 'cancelled'
        ]);

        // Check that resources were refunded
        $this->player->refresh();
        $this->assertEquals(1000, $this->player->wood);
    }

    /** @test */
    public function it_can_get_market_statistics()
    {
        MarketOffer::factory()->count(10)->create([
            'resource_type' => 'wood',
            'status' => 'active'
        ]);
        MarketOffer::factory()->count(5)->create([
            'resource_type' => 'clay',
            'status' => 'completed'
        ]);

        $response = $this->getJson('/game/api/market/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_offers',
                    'active_offers',
                    'completed_offers',
                    'cancelled_offers',
                    'average_exchange_rates',
                    'recent_trades'
                ]);
    }

    /** @test */
    public function it_validates_market_offer_creation()
    {
        $response = $this->postJson('/game/api/market/offers', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'offer_type',
                    'resource_type',
                    'resource_amount',
                    'exchange_rate'
                ]);
    }

    /** @test */
    public function it_prevents_insufficient_resources_for_sell_offer()
    {
        $this->player->update(['wood' => 100]);

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5
        ];

        $response = $this->postJson('/game/api/market/offers', $offerData);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function it_prevents_accepting_own_offer()
    {
        $this->player->update(['clay' => 1000]);
        
        $offer = MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'status' => 'active'
        ]);

        $acceptData = [
            'accept_amount' => 250
        ];

        $response = $this->postJson("/game/api/market/offers/{$offer->id}/accept", $acceptData);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }
}

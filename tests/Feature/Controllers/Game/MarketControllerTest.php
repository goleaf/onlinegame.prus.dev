<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_market_offers()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/market/offers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_type',
                        'resource_type',
                        'amount',
                        'price_per_unit',
                        'total_price',
                        'description',
                        'status',
                        'player_id',
                        'village_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_market_offer()
    {
        $user = User::factory()->create();
        $offer = MarketOffer::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/market/offers/{$offer->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'offer_type',
                'resource_type',
                'amount',
                'price_per_unit',
                'total_price',
                'description',
                'status',
                'player',
                'village',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_market_offer()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'amount' => 1000,
            'price_per_unit' => 0.5,
            'description' => 'Selling wood for iron',
            'village_id' => $village->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/market/offers', $offerData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'offer' => [
                    'id',
                    'offer_type',
                    'resource_type',
                    'amount',
                    'price_per_unit',
                    'total_price',
                    'description',
                    'status',
                    'player_id',
                    'village_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('market_offers', [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'amount' => 1000,
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_market_offer()
    {
        $user = User::factory()->create();
        $offer = MarketOffer::factory()->create();

        $updateData = [
            'amount' => 1500,
            'price_per_unit' => 0.6,
            'description' => 'Updated offer description',
        ];

        $response = $this->actingAs($user)->put("/api/game/market/offers/{$offer->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'offer' => [
                    'id',
                    'amount',
                    'price_per_unit',
                    'description',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('market_offers', [
            'id' => $offer->id,
            'amount' => 1500,
            'price_per_unit' => 0.6,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_market_offer()
    {
        $user = User::factory()->create();
        $offer = MarketOffer::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/market/offers/{$offer->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('market_offers', ['id' => $offer->id]);
    }

    /**
     * @test
     */
    public function it_can_get_buy_offers()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(2)->create(['offer_type' => 'buy']);
        MarketOffer::factory()->count(1)->create(['offer_type' => 'sell']);

        $response = $this->actingAs($user)->get('/api/game/market/buy');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_sell_offers()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(2)->create(['offer_type' => 'sell']);
        MarketOffer::factory()->count(1)->create(['offer_type' => 'buy']);

        $response = $this->actingAs($user)->get('/api/game/market/sell');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_offers_by_resource_type()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(2)->create(['resource_type' => 'wood']);
        MarketOffer::factory()->count(1)->create(['resource_type' => 'iron']);

        $response = $this->actingAs($user)->get('/api/game/market/offers?resource_type=wood');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_offers_by_status()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(2)->create(['status' => 'active']);
        MarketOffer::factory()->count(1)->create(['status' => 'completed']);

        $response = $this->actingAs($user)->get('/api/game/market/offers?status=active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_accept_market_offer()
    {
        $user = User::factory()->create();
        $offer = MarketOffer::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post("/api/game/market/offers/{$offer->id}/accept");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'transaction' => [
                    'id',
                    'offer_id',
                    'buyer_id',
                    'seller_id',
                    'amount',
                    'total_price',
                    'completed_at',
                ],
            ]);

        $this->assertDatabaseHas('market_offers', [
            'id' => $offer->id,
            'status' => 'completed',
        ]);
    }

    /**
     * @test
     */
    public function it_can_cancel_market_offer()
    {
        $user = User::factory()->create();
        $offer = MarketOffer::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post("/api/game/market/offers/{$offer->id}/cancel");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseHas('market_offers', [
            'id' => $offer->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_market_statistics()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->count(5)->create(['resource_type' => 'wood']);
        MarketOffer::factory()->count(3)->create(['resource_type' => 'iron']);

        $response = $this->actingAs($user)->get('/api/game/market/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_offers',
                'active_offers',
                'completed_offers',
                'by_resource_type',
                'by_offer_type',
                'average_prices',
                'recent_activity',
            ]);
    }

    /**
     * @test
     */
    public function it_can_search_market_offers()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->create(['description' => 'Selling premium wood']);
        MarketOffer::factory()->create(['description' => 'Buying iron ore']);

        $response = $this->actingAs($user)->get('/api/game/market/offers?search=premium');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_offers_by_price_range()
    {
        $user = User::factory()->create();
        MarketOffer::factory()->create(['price_per_unit' => 0.5]);
        MarketOffer::factory()->create(['price_per_unit' => 1.0]);
        MarketOffer::factory()->create(['price_per_unit' => 2.0]);

        $response = $this->actingAs($user)->get('/api/game/market/offers?min_price=0.5&max_price=1.0');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_my_offers()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        MarketOffer::factory()->count(2)->create(['player_id' => $player->id]);
        MarketOffer::factory()->count(1)->create();  // Other player's offer

        $response = $this->actingAs($user)->get('/api/game/market/my-offers');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_market_history()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/market/history');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'offer_type',
                        'resource_type',
                        'amount',
                        'price_per_unit',
                        'total_price',
                        'status',
                        'completed_at',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/market/offers');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_market_offer_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/market/offers', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_type', 'resource_type', 'amount', 'price_per_unit']);
    }

    /**
     * @test
     */
    public function it_validates_offer_type_enum()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $offerData = [
            'offer_type' => 'invalid_type',
            'resource_type' => 'wood',
            'amount' => 1000,
            'price_per_unit' => 0.5,
            'village_id' => $village->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/market/offers', $offerData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_type']);
    }

    /**
     * @test
     */
    public function it_validates_resource_type()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'invalid_resource',
            'amount' => 1000,
            'price_per_unit' => 0.5,
            'village_id' => $village->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/market/offers', $offerData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['resource_type']);
    }

    /**
     * @test
     */
    public function it_validates_amount_is_positive()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'amount' => -100,  // Invalid: negative amount
            'price_per_unit' => 0.5,
            'village_id' => $village->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/market/offers', $offerData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    /**
     * @test
     */
    public function it_validates_price_per_unit_is_positive()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'amount' => 1000,
            'price_per_unit' => -0.5,  // Invalid: negative price
            'village_id' => $village->id,
        ];

        $response = $this->actingAs($user)->post('/api/game/market/offers', $offerData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price_per_unit']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_offer()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/market/offers/999');

        $response->assertStatus(404);
    }
}

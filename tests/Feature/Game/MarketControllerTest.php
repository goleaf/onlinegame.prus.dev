<?php

namespace Tests\Feature\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class MarketControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Player $player;

    protected World $world;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->world = World::factory()->create(['is_active' => true]);
        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'is_active' => true,
        ]);

        // Mock rate limiter
        $this->mock(RateLimiterUtil::class, function ($mock): void {
            $mock->shouldReceive('attempt')->andReturn(true);
        });
    }

    /**
     * @test
     */
    public function it_can_get_all_market_offers()
    {
        // Create test market offers
        MarketOffer::factory()->count(5)->create([
            'status' => 'active',
            'world_id' => $this->world->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'offer_type',
                            'resource_type',
                            'resource_amount',
                            'exchange_rate',
                            'status',
                            'created_at',
                        ],
                    ],
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'has_more_pages',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_market_offers_by_resource_type()
    {
        // Create test offers
        MarketOffer::factory()->create([
            'resource_type' => 'wood',
            'status' => 'active',
            'world_id' => $this->world->id,
        ]);

        MarketOffer::factory()->create([
            'resource_type' => 'clay',
            'status' => 'active',
            'world_id' => $this->world->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers?resource_type=wood');

        $response->assertStatus(200);
        $response->assertJsonPath('data.data.0.resource_type', 'wood');
    }

    /**
     * @test
     */
    public function it_can_get_specific_market_offer()
    {
        $offer = MarketOffer::factory()->create([
            'status' => 'active',
            'world_id' => $this->world->id,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/market/offers/{$offer->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'offer_type',
                    'resource_type',
                    'resource_amount',
                    'exchange_rate',
                    'status',
                    'created_at',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_market_offer()
    {
        // Set up player resources
        $this->player->update(['wood' => 1000]);

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'description' => 'Selling wood for clay',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/market/offers', $offerData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'player_id',
                    'offer_type',
                    'resource_type',
                    'resource_amount',
                    'exchange_rate',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('market_offers', [
            'player_id' => $this->player->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'status' => 'active',
        ]);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_when_creating_offer()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/market/offers', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'offer_type',
                'resource_type',
                'resource_amount',
                'exchange_rate',
            ]);
    }

    /**
     * @test
     */
    public function it_prevents_creating_offer_with_insufficient_resources()
    {
        // Player has no wood
        $this->player->update(['wood' => 0]);

        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/market/offers', $offerData);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient resources for this offer',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_market_offers()
    {
        // Create offers for the player
        MarketOffer::factory()->count(3)->create([
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'completed',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/my-offers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'player_id',
                        'offer_type',
                        'resource_type',
                        'resource_amount',
                        'exchange_rate',
                        'status',
                    ],
                ],
            ]);

        $response->assertJsonCount(4, 'data');
    }

    /**
     * @test
     */
    public function it_can_filter_player_offers_by_status()
    {
        MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'completed',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/my-offers?status=active');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.status', 'active');
    }

    /**
     * @test
     */
    public function it_can_accept_market_offer()
    {
        // Create another player and offer
        $otherUser = User::factory()->create();
        $otherPlayer = Player::factory()->create([
            'user_id' => $otherUser->id,
            'world_id' => $this->world->id,
            'wood' => 1000,
        ]);

        $offer = MarketOffer::factory()->create([
            'player_id' => $otherPlayer->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
            'status' => 'active',
        ]);

        // Set up current player's resources
        $this->player->update(['clay' => 1000]);  // Clay is the payment for wood

        $acceptData = [
            'accept_amount' => 500,
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson("/game/api/market/offers/{$offer->id}/accept", $acceptData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'offer_id',
                    'accepted_amount',
                    'received_amount',
                    'cost',
                ],
            ]);

        // Check that the offer was updated
        $this->assertDatabaseHas('market_offers', [
            'id' => $offer->id,
            'status' => 'completed',
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_accepting_own_offer()
    {
        $offer = MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'status' => 'active',
        ]);

        $acceptData = [
            'accept_amount' => 100,
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson("/game/api/market/offers/{$offer->id}/accept", $acceptData);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot accept your own offer',
            ]);
    }

    /**
     * @test
     */
    public function it_can_cancel_market_offer()
    {
        $offer = MarketOffer::factory()->create([
            'player_id' => $this->player->id,
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/game/api/market/offers/{$offer->id}/cancel");

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Market offer cancelled successfully.',
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
        // Create test offers with different statuses
        MarketOffer::factory()->count(5)->create(['status' => 'active']);
        MarketOffer::factory()->count(3)->create(['status' => 'completed']);
        MarketOffer::factory()->count(2)->create(['status' => 'cancelled']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_offers',
                    'active_offers',
                    'completed_offers',
                    'cancelled_offers',
                    'average_exchange_rates',
                    'recent_trades',
                ],
            ]);

        $response->assertJsonPath('data.total_offers', 10);
        $response->assertJsonPath('data.active_offers', 5);
        $response->assertJsonPath('data.completed_offers', 3);
        $response->assertJsonPath('data.cancelled_offers', 2);
    }

    /**
     * @test
     */
    public function it_respects_rate_limiting()
    {
        // Mock rate limiter to return false
        $this->mock(RateLimiterUtil::class, function ($mock): void {
            $mock->shouldReceive('attempt')->andReturn(false);
        });

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers');

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ]);
    }

    /**
     * @test
     */
    public function it_uses_caching_for_offers_list()
    {
        MarketOffer::factory()->count(3)->create(['status' => 'active']);

        // First request
        $response1 = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers');

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers');

        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * @test
     */
    public function it_clears_cache_when_creating_offer()
    {
        // Create initial offers
        MarketOffer::factory()->count(2)->create(['status' => 'active']);

        // First request to populate cache
        $this->actingAs($this->user)->getJson('/game/api/market/offers');

        // Create new offer
        $this->player->update(['wood' => 1000]);
        $offerData = [
            'offer_type' => 'sell',
            'resource_type' => 'wood',
            'resource_amount' => 500,
            'exchange_rate' => 1.5,
        ];

        $this
            ->actingAs($this->user)
            ->postJson('/game/api/market/offers', $offerData);

        // Next request should include the new offer
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/market/offers');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }
}

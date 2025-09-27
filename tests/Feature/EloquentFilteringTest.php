<?php

namespace Tests\Feature;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use App\Models\Game\Battle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_filtering_by_name()
    {
        // Create test players
        $player1 = Player::factory()->create(['name' => 'TestPlayer1']);
        $player2 = Player::factory()->create(['name' => 'TestPlayer2']);
        $player3 = Player::factory()->create(['name' => 'AnotherPlayer']);

        // Test filtering by name contains
        $filters = [
            [
                'target' => 'name',
                'type' => '$contains',
                'value' => 'Test'
            ]
        ];

        $results = Player::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('name', 'TestPlayer1'));
        $this->assertTrue($results->contains('name', 'TestPlayer2'));
        $this->assertFalse($results->contains('name', 'AnotherPlayer'));
    }

    public function test_player_filtering_by_points()
    {
        // Create test players with different points
        $player1 = Player::factory()->create(['points' => 1000]);
        $player2 = Player::factory()->create(['points' => 2000]);
        $player3 = Player::factory()->create(['points' => 3000]);

        // Test filtering by points greater than
        $filters = [
            [
                'target' => 'points',
                'type' => '$gt',
                'value' => 1500
            ]
        ];

        $results = Player::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('points', 2000));
        $this->assertTrue($results->contains('points', 3000));
        $this->assertFalse($results->contains('points', 1000));
    }

    public function test_village_filtering_by_population()
    {
        // Create test villages with different populations
        $village1 = Village::factory()->create(['population' => 100]);
        $village2 = Village::factory()->create(['population' => 500]);
        $village3 = Village::factory()->create(['population' => 1000]);

        // Test filtering by population less than
        $filters = [
            [
                'target' => 'population',
                'type' => '$lt',
                'value' => 600
            ]
        ];

        $results = Village::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('population', 100));
        $this->assertTrue($results->contains('population', 500));
        $this->assertFalse($results->contains('population', 1000));
    }

    public function test_alliance_filtering_by_active_status()
    {
        // Create test alliances
        $alliance1 = Alliance::factory()->create(['is_active' => true]);
        $alliance2 = Alliance::factory()->create(['is_active' => false]);
        $alliance3 = Alliance::factory()->create(['is_active' => true]);

        // Test filtering by active status
        $filters = [
            [
                'target' => 'is_active',
                'type' => '$eq',
                'value' => true
            ]
        ];

        $results = Alliance::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('is_active', true));
        $this->assertFalse($results->contains('is_active', false));
    }

    public function test_user_filtering_by_email()
    {
        // Create test users
        $user1 = User::factory()->create(['email' => 'test1@example.com']);
        $user2 = User::factory()->create(['email' => 'test2@example.com']);
        $user3 = User::factory()->create(['email' => 'admin@example.com']);

        // Test filtering by email contains
        $filters = [
            [
                'target' => 'email',
                'type' => '$contains',
                'value' => 'test'
            ]
        ];

        $results = User::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('email', 'test1@example.com'));
        $this->assertTrue($results->contains('email', 'test2@example.com'));
        $this->assertFalse($results->contains('email', 'admin@example.com'));
    }

    public function test_multiple_filters_combination()
    {
        // Create test players
        $player1 = Player::factory()->create(['name' => 'TestPlayer1', 'points' => 1000, 'is_active' => true]);
        $player2 = Player::factory()->create(['name' => 'TestPlayer2', 'points' => 2000, 'is_active' => true]);
        $player3 = Player::factory()->create(['name' => 'AnotherPlayer', 'points' => 1500, 'is_active' => false]);
        $player4 = Player::factory()->create(['name' => 'TestPlayer3', 'points' => 500, 'is_active' => true]);

        // Test multiple filters
        $filters = [
            [
                'target' => 'name',
                'type' => '$contains',
                'value' => 'Test'
            ],
            [
                'target' => 'points',
                'type' => '$gte',
                'value' => 1000
            ],
            [
                'target' => 'is_active',
                'type' => '$eq',
                'value' => true
            ]
        ];

        $results = Player::filter($filters)->get();
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('name', 'TestPlayer1'));
        $this->assertTrue($results->contains('name', 'TestPlayer2'));
        $this->assertFalse($results->contains('name', 'AnotherPlayer'));
        $this->assertFalse($results->contains('name', 'TestPlayer3'));
    }

    public function test_relationship_filtering()
    {
        // Create test data with relationships
        $user = User::factory()->create(['name' => 'TestUser']);
        $player = Player::factory()->create(['user_id' => $user->id, 'name' => 'TestPlayer']);
        $village = Village::factory()->create(['player_id' => $player->id, 'name' => 'TestVillage']);

        // Test filtering users by player relationship
        $filters = [
            [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    [
                        'target' => 'name',
                        'type' => '$eq',
                        'value' => 'TestPlayer'
                    ]
                ]
            ]
        ];

        $results = User::filter($filters)->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('TestUser', $results->first()->name);
    }
}


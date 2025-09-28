<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Services\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlayerServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_player()
    {
        $data = [
            'name' => 'Test Player',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $service = new PlayerService();
        $result = $service->createPlayer($data);

        $this->assertInstanceOf(Player::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['email'], $result->email);
    }

    /**
     * @test
     */
    public function it_can_update_player()
    {
        $player = Player::factory()->create();
        $data = [
            'name' => 'Updated Player',
            'email' => 'updated@example.com',
        ];

        $service = new PlayerService();
        $result = $service->updatePlayer($player, $data);

        $this->assertTrue($result);
        $this->assertEquals($data['name'], $player->name);
        $this->assertEquals($data['email'], $player->email);
    }

    /**
     * @test
     */
    public function it_can_delete_player()
    {
        $player = Player::factory()->create();

        $service = new PlayerService();
        $result = $service->deletePlayer($player);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_id()
    {
        $player = Player::factory()->create();

        $service = new PlayerService();
        $result = $service->getPlayerById($player->id);

        $this->assertInstanceOf(Player::class, $result);
        $this->assertEquals($player->id, $result->id);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_name()
    {
        $player = Player::factory()->create(['name' => 'Test Player']);

        $service = new PlayerService();
        $result = $service->getPlayerByName('Test Player');

        $this->assertInstanceOf(Player::class, $result);
        $this->assertEquals('Test Player', $result->name);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_email()
    {
        $player = Player::factory()->create(['email' => 'test@example.com']);

        $service = new PlayerService();
        $result = $service->getPlayerByEmail('test@example.com');

        $this->assertInstanceOf(Player::class, $result);
        $this->assertEquals('test@example.com', $result->email);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_status()
    {
        $player = Player::factory()->create(['status' => 'active']);

        $service = new PlayerService();
        $result = $service->getPlayerByStatus('active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_level()
    {
        $player = Player::factory()->create(['level' => 5]);

        $service = new PlayerService();
        $result = $service->getPlayerByLevel(5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_points()
    {
        $player = Player::factory()->create(['points' => 1000]);

        $service = new PlayerService();
        $result = $service->getPlayerByPoints(1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_rank()
    {
        $player = Player::factory()->create(['rank' => 1]);

        $service = new PlayerService();
        $result = $service->getPlayerByRank(1);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_alliance()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);

        $service = new PlayerService();
        $result = $service->getPlayerByAlliance(1);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_world()
    {
        $player = Player::factory()->create(['world_id' => 1]);

        $service = new PlayerService();
        $result = $service->getPlayerByWorld(1);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_creation_date()
    {
        $player = Player::factory()->create(['created_at' => now()]);

        $service = new PlayerService();
        $result = $service->getPlayerByCreationDate(now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_last_activity()
    {
        $player = Player::factory()->create(['last_activity_at' => now()]);

        $service = new PlayerService();
        $result = $service->getPlayerByLastActivity(now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_combined_filters()
    {
        $player = Player::factory()->create([
            'name' => 'Test Player',
            'status' => 'active',
        ]);

        $service = new PlayerService();
        $result = $service->getPlayerByCombinedFilters([
            'name' => 'Test Player',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_search()
    {
        $player = Player::factory()->create(['name' => 'Test Player']);

        $service = new PlayerService();
        $result = $service->getPlayerBySearch('Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_sort()
    {
        $player = Player::factory()->create(['level' => 5]);

        $service = new PlayerService();
        $result = $service->getPlayerBySort('level', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_by_pagination()
    {
        $player = Player::factory()->create();

        $service = new PlayerService();
        $result = $service->getPlayerByPagination(1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_statistics()
    {
        $service = new PlayerService();
        $result = $service->getPlayerStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_players', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_level', $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_leaderboard()
    {
        $service = new PlayerService();
        $result = $service->getPlayerLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

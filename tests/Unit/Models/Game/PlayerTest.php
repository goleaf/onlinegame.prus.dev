<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Achievement;
use App\Models\Game\Alliance;
use App\Models\Game\Battle;
use App\Models\Game\GameEvent;
use App\Models\Game\GameTask;
use App\Models\Game\Hero;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\PlayerNote;
use App\Models\Game\PlayerQuest;
use App\Models\Game\PlayerStatistic;
use App\Models\Game\Quest;
use App\Models\Game\Report;
use App\Models\Game\Technology;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\ValueObjects\PlayerStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_player()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'Romans',
        ]);

        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals('Test Player', $player->name);
        $this->assertEquals('Romans', $player->tribe);
        $this->assertEquals($user->id, $player->user_id);
        $this->assertEquals($world->id, $player->world_id);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $player = new Player();
        $fillable = $player->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('world_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('tribe', $fillable);
        $this->assertContains('alliance_id', $fillable);
        $this->assertContains('population', $fillable);
        $this->assertContains('villages_count', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('is_online', $fillable);
        $this->assertContains('last_login', $fillable);
        $this->assertContains('last_active_at', $fillable);
        $this->assertContains('points', $fillable);
        $this->assertContains('total_attack_points', $fillable);
        $this->assertContains('total_defense_points', $fillable);
        $this->assertContains('reference_number', $fillable);
    }

    /**
     * @test
     */
    public function it_casts_attributes_correctly()
    {
        $player = Player::factory()->create();
        $casts = $player->getCasts();

        $this->assertArrayHasKey('last_login', $casts);
        $this->assertArrayHasKey('last_active_at', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('is_online', $casts);
    }

    /**
     * @test
     */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $player->user());
        $this->assertEquals($user->id, $player->user->id);
    }

    /**
     * @test
     */
    public function it_has_villages_relationship()
    {
        $player = Player::factory()->create();
        $village1 = Village::factory()->create(['player_id' => $player->id]);
        $village2 = Village::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->villages());
        $this->assertTrue($player->villages->contains($village1));
        $this->assertTrue($player->villages->contains($village2));
    }

    /**
     * @test
     */
    public function it_has_hero_relationship()
    {
        $player = Player::factory()->create();
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $player->hero());
        $this->assertEquals($hero->id, $player->hero->id);
    }

    /**
     * @test
     */
    public function it_has_world_relationship()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $player->world());
        $this->assertEquals($world->id, $player->world->id);
    }

    /**
     * @test
     */
    public function it_has_alliance_relationship()
    {
        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create(['alliance_id' => $alliance->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $player->alliance());
        $this->assertEquals($alliance->id, $player->alliance->id);
    }

    /**
     * @test
     */
    public function it_has_statistics_relationship()
    {
        $player = Player::factory()->create();
        $statistics = PlayerStatistic::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $player->statistics());
        $this->assertEquals($statistics->id, $player->statistics->id);
    }

    /**
     * @test
     */
    public function it_has_quests_relationship()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create();
        $player->quests()->attach($quest->id, [
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $player->quests());
        $this->assertTrue($player->quests->contains($quest));
    }

    /**
     * @test
     */
    public function it_has_achievements_relationship()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create();
        $player->achievements()->attach($achievement->id, [
            'unlocked_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $player->achievements());
        $this->assertTrue($player->achievements->contains($achievement));
    }

    /**
     * @test
     */
    public function it_has_technologies_relationship()
    {
        $player = Player::factory()->create();
        $technology = Technology::factory()->create();
        $player->technologies()->attach($technology->id, [
            'level' => 5,
            'research_progress' => 100,
            'unlocked_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $player->technologies());
        $this->assertTrue($player->technologies->contains($technology));
    }

    /**
     * @test
     */
    public function it_has_tasks_relationship()
    {
        $player = Player::factory()->create();
        $task = GameTask::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->tasks());
        $this->assertTrue($player->tasks->contains($task));
    }

    /**
     * @test
     */
    public function it_has_events_relationship()
    {
        $player = Player::factory()->create();
        $event = GameEvent::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->events());
        $this->assertTrue($player->events->contains($event));
    }

    /**
     * @test
     */
    public function it_has_reports_relationship()
    {
        $player = Player::factory()->create();
        $report = Report::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->reports());
        $this->assertTrue($player->reports->contains($report));
    }

    /**
     * @test
     */
    public function it_has_player_quests_relationship()
    {
        $player = Player::factory()->create();
        $playerQuest = PlayerQuest::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->playerQuests());
        $this->assertTrue($player->playerQuests->contains($playerQuest));
    }

    /**
     * @test
     */
    public function it_has_notes_relationship()
    {
        $player = Player::factory()->create();
        $note = PlayerNote::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->notes());
        $this->assertTrue($player->notes->contains($note));
    }

    /**
     * @test
     */
    public function it_has_movements_relationship()
    {
        $player = Player::factory()->create();
        $movement = Movement::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->movements());
        $this->assertTrue($player->movements->contains($movement));
    }

    /**
     * @test
     */
    public function it_has_battles_as_attacker_relationship()
    {
        $player = Player::factory()->create();
        $battle = Battle::factory()->create(['attacker_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->battlesAsAttacker());
        $this->assertTrue($player->battlesAsAttacker->contains($battle));
    }

    /**
     * @test
     */
    public function it_has_battles_as_defender_relationship()
    {
        $player = Player::factory()->create();
        $battle = Battle::factory()->create(['defender_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $player->battlesAsDefender());
        $this->assertTrue($player->battlesAsDefender->contains($battle));
    }

    /**
     * @test
     */
    public function it_can_scope_by_world()
    {
        $world1 = World::factory()->create();
        $world2 = World::factory()->create();
        $player1 = Player::factory()->create(['world_id' => $world1->id]);
        $player2 = Player::factory()->create(['world_id' => $world2->id]);

        $world1Players = Player::byWorld($world1->id)->get();

        $this->assertTrue($world1Players->contains($player1));
        $this->assertFalse($world1Players->contains($player2));
    }

    /**
     * @test
     */
    public function it_can_scope_active_players()
    {
        $activePlayer = Player::factory()->create(['is_active' => true]);
        $inactivePlayer = Player::factory()->create(['is_active' => false]);

        $activePlayers = Player::active()->get();

        $this->assertTrue($activePlayers->contains($activePlayer));
        $this->assertFalse($activePlayers->contains($inactivePlayer));
    }

    /**
     * @test
     */
    public function it_can_scope_online_players()
    {
        $onlinePlayer = Player::factory()->create(['is_online' => true]);
        $offlinePlayer = Player::factory()->create(['is_online' => false]);

        $onlinePlayers = Player::online()->get();

        $this->assertTrue($onlinePlayers->contains($onlinePlayer));
        $this->assertFalse($onlinePlayers->contains($offlinePlayer));
    }

    /**
     * @test
     */
    public function it_can_scope_in_alliance()
    {
        $alliance = Alliance::factory()->create();
        $playerInAlliance = Player::factory()->create(['alliance_id' => $alliance->id]);
        $playerNotInAlliance = Player::factory()->create(['alliance_id' => null]);

        $alliancePlayers = Player::inAlliance($alliance->id)->get();

        $this->assertTrue($alliancePlayers->contains($playerInAlliance));
        $this->assertFalse($alliancePlayers->contains($playerNotInAlliance));
    }

    /**
     * @test
     */
    public function it_can_scope_by_tribe()
    {
        $romanPlayer = Player::factory()->create(['tribe' => 'Romans']);
        $teutonPlayer = Player::factory()->create(['tribe' => 'Teutons']);

        $romanPlayers = Player::byTribe('Romans')->get();

        $this->assertTrue($romanPlayers->contains($romanPlayer));
        $this->assertFalse($romanPlayers->contains($teutonPlayer));
    }

    /**
     * @test
     */
    public function it_can_scope_top_players()
    {
        $player1 = Player::factory()->create(['points' => 1000]);
        $player2 = Player::factory()->create(['points' => 2000]);
        $player3 = Player::factory()->create(['points' => 500]);

        $topPlayers = Player::topPlayers(2)->get();

        $this->assertTrue($topPlayers->contains($player2));
        $this->assertTrue($topPlayers->contains($player1));
        $this->assertFalse($topPlayers->contains($player3));
    }

    /**
     * @test
     */
    public function it_can_scope_search_players()
    {
        $player1 = Player::factory()->create(['name' => 'John Doe']);
        $player2 = Player::factory()->create(['name' => 'Jane Smith']);

        $searchResults = Player::search('John')->get();

        $this->assertTrue($searchResults->contains($player1));
        $this->assertFalse($searchResults->contains($player2));
    }

    /**
     * @test
     */
    public function it_has_stats_attribute()
    {
        $player = Player::factory()->create([
            'points' => 1000,
            'population' => 500,
            'villages_count' => 3,
            'total_attack_points' => 800,
            'total_defense_points' => 600,
            'is_active' => true,
            'is_online' => false,
        ]);

        $stats = $player->stats;

        $this->assertInstanceOf(PlayerStats::class, $stats);
        $this->assertEquals(1000, $stats->points);
        $this->assertEquals(500, $stats->population);
        $this->assertEquals(3, $stats->villagesCount);
        $this->assertEquals(800, $stats->totalAttackPoints);
        $this->assertEquals(600, $stats->totalDefensePoints);
        $this->assertTrue($stats->isActive);
        $this->assertFalse($stats->isOnline);
    }

    /**
     * @test
     */
    public function it_can_set_stats_attribute()
    {
        $player = Player::factory()->create();
        $newStats = new PlayerStats(
            points: 1500,
            population: 750,
            villagesCount: 5,
            totalAttackPoints: 1200,
            totalDefensePoints: 900,
            isActive: true,
            isOnline: true
        );

        $player->stats = $newStats;
        $player->save();

        $this->assertEquals(1500, $player->points);
        $this->assertEquals(750, $player->population);
        $this->assertEquals(5, $player->villages_count);
        $this->assertEquals(1200, $player->total_attack_points);
        $this->assertEquals(900, $player->total_defense_points);
        $this->assertTrue($player->is_active);
        $this->assertTrue($player->is_online);
    }

    /**
     * @test
     */
    public function it_has_allowed_filters()
    {
        $player = new Player();
        $filters = $player->allowedFilters();

        $this->assertInstanceOf(\IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList::class, $filters);
    }

    /**
     * @test
     */
    public function it_can_initialize_with_integration()
    {
        $player = Player::factory()->create();

        // Mock the services to avoid actual calls
        $this->mock(\App\Services\GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
        });

        $this->mock(\App\Services\GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
        });

        $player->initializeWithIntegration();

        $this->assertTrue(true);  // If we get here without exception, the test passes
    }

    /**
     * @test
     */
    public function it_can_update_stats_with_integration()
    {
        $player = Player::factory()->create();
        $stats = ['points' => 1500, 'population' => 750];

        // Mock the notification service
        $this->mock(\App\Services\GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
        });

        $player->updateStatsWithIntegration($stats);

        $this->assertEquals(1500, $player->fresh()->points);
        $this->assertEquals(750, $player->fresh()->population);
    }
}

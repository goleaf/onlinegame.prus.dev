<?php

namespace Tests\Unit\Models;

use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_user()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('phone_country', $fillable);
        $this->assertContains('reference_number', $fillable);
    }

    /**
     * @test
     */
    public function it_hides_sensitive_attributes()
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    /**
     * @test
     */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->create();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('phone', $casts);
        $this->assertArrayHasKey('reference_number', $casts);
    }

    /**
     * @test
     */
    public function it_has_players_relationship()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->players());
        $this->assertTrue($user->players->contains($player));
    }

    /**
     * @test
     */
    public function it_has_single_player_relationship()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->player());
        $this->assertEquals($player->id, $user->player->id);
    }

    /**
     * @test
     */
    public function it_can_get_game_stats()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'points' => 1000,
            'population' => 500,
        ]);

        $stats = $user->getGameStats();

        $this->assertIsArray($stats);
        $this->assertEquals($player->id, $stats['player_id']);
        $this->assertEquals(1000, $stats['points']);
        $this->assertEquals(500, $stats['total_population']);
    }

    /**
     * @test
     */
    public function it_returns_null_game_stats_when_no_player()
    {
        $user = User::factory()->create();

        $stats = $user->getGameStats();

        $this->assertNull($stats);
    }

    /**
     * @test
     */
    public function it_can_check_active_game_session()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasActiveGameSession());
    }

    /**
     * @test
     */
    public function it_returns_false_for_inactive_game_session()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $this->assertFalse($user->hasActiveGameSession());
    }

    /**
     * @test
     */
    public function it_can_get_last_activity()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'last_active_at' => now()->subHours(2),
        ]);

        $lastActivity = $user->getLastActivity();

        $this->assertEquals($player->last_active_at, $lastActivity);
    }

    /**
     * @test
     */
    public function it_can_check_if_online()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_online' => true,
            'last_active_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($user->isOnline());
    }

    /**
     * @test
     */
    public function it_returns_false_when_not_online()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_online' => false,
        ]);

        $this->assertFalse($user->isOnline());
    }

    /**
     * @test
     */
    public function it_can_get_villages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = \App\Models\Game\Village::factory()->create(['player_id' => $player->id]);

        $villages = $user->getVillages();

        $this->assertTrue($villages->contains($village));
    }

    /**
     * @test
     */
    public function it_can_get_capital_village()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $capitalVillage = \App\Models\Game\Village::factory()->create([
            'player_id' => $player->id,
            'is_capital' => true,
        ]);

        $capital = $user->getCapitalVillage();

        $this->assertEquals($capitalVillage->id, $capital->id);
    }

    /**
     * @test
     */
    public function it_can_scope_with_game_players()
    {
        $userWithPlayer = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $userWithPlayer->id]);

        $userWithoutPlayer = User::factory()->create();

        $usersWithPlayers = User::withGamePlayers()->get();

        $this->assertTrue($usersWithPlayers->contains($userWithPlayer));
        $this->assertFalse($usersWithPlayers->contains($userWithoutPlayer));
    }

    /**
     * @test
     */
    public function it_can_scope_active_game_users()
    {
        $activeUser = User::factory()->create();
        $activePlayer = Player::factory()->create([
            'user_id' => $activeUser->id,
            'is_active' => true,
        ]);

        $inactiveUser = User::factory()->create();
        $inactivePlayer = Player::factory()->create([
            'user_id' => $inactiveUser->id,
            'is_active' => false,
        ]);

        $activeUsers = User::activeGameUsers()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    /**
     * @test
     */
    public function it_can_scope_online_users()
    {
        $onlineUser = User::factory()->create();
        $onlinePlayer = Player::factory()->create([
            'user_id' => $onlineUser->id,
            'is_online' => true,
            'last_active_at' => now()->subMinutes(5),
        ]);

        $offlineUser = User::factory()->create();
        $offlinePlayer = Player::factory()->create([
            'user_id' => $offlineUser->id,
            'is_online' => false,
        ]);

        $onlineUsers = User::onlineUsers()->get();

        $this->assertTrue($onlineUsers->contains($onlineUser));
        $this->assertFalse($onlineUsers->contains($offlineUser));
    }

    /**
     * @test
     */
    public function it_can_scope_by_world()
    {
        $user1 = User::factory()->create();
        $player1 = Player::factory()->create([
            'user_id' => $user1->id,
            'world_id' => 1,
        ]);

        $user2 = User::factory()->create();
        $player2 = Player::factory()->create([
            'user_id' => $user2->id,
            'world_id' => 2,
        ]);

        $world1Users = User::byWorld(1)->get();

        $this->assertTrue($world1Users->contains($user1));
        $this->assertFalse($world1Users->contains($user2));
    }

    /**
     * @test
     */
    public function it_can_scope_by_tribe()
    {
        $user1 = User::factory()->create();
        $player1 = Player::factory()->create([
            'user_id' => $user1->id,
            'tribe' => 'Romans',
        ]);

        $user2 = User::factory()->create();
        $player2 = Player::factory()->create([
            'user_id' => $user2->id,
            'tribe' => 'Teutons',
        ]);

        $romanUsers = User::byTribe('Romans')->get();

        $this->assertTrue($romanUsers->contains($user1));
        $this->assertFalse($romanUsers->contains($user2));
    }

    /**
     * @test
     */
    public function it_can_scope_by_alliance()
    {
        $user1 = User::factory()->create();
        $player1 = Player::factory()->create([
            'user_id' => $user1->id,
            'alliance_id' => 1,
        ]);

        $user2 = User::factory()->create();
        $player2 = Player::factory()->create([
            'user_id' => $user2->id,
            'alliance_id' => 2,
        ]);

        $alliance1Users = User::byAlliance(1)->get();

        $this->assertTrue($alliance1Users->contains($user1));
        $this->assertFalse($alliance1Users->contains($user2));
    }

    /**
     * @test
     */
    public function it_can_scope_active_users()
    {
        $activeUser = User::factory()->create(['email_verified_at' => now()]);
        $inactiveUser = User::factory()->create(['email_verified_at' => null]);

        $activeUsers = User::active()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    /**
     * @test
     */
    public function it_can_scope_verified_users()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        $verifiedUsers = User::verified()->get();

        $this->assertTrue($verifiedUsers->contains($verifiedUser));
        $this->assertFalse($verifiedUsers->contains($unverifiedUser));
    }

    /**
     * @test
     */
    public function it_can_scope_unverified_users()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        $unverifiedUsers = User::unverified()->get();

        $this->assertFalse($unverifiedUsers->contains($verifiedUser));
        $this->assertTrue($unverifiedUsers->contains($unverifiedUser));
    }

    /**
     * @test
     */
    public function it_can_scope_recent_users()
    {
        $recentUser = User::factory()->create(['created_at' => now()->subDays(3)]);
        $oldUser = User::factory()->create(['created_at' => now()->subDays(10)]);

        $recentUsers = User::recent(7)->get();

        $this->assertTrue($recentUsers->contains($recentUser));
        $this->assertFalse($recentUsers->contains($oldUser));
    }

    /**
     * @test
     */
    public function it_can_scope_today_users()
    {
        $todayUser = User::factory()->create(['created_at' => now()]);
        $yesterdayUser = User::factory()->create(['created_at' => now()->subDay()]);

        $todayUsers = User::today()->get();

        $this->assertTrue($todayUsers->contains($todayUser));
        $this->assertFalse($todayUsers->contains($yesterdayUser));
    }

    /**
     * @test
     */
    public function it_can_scope_this_week_users()
    {
        $thisWeekUser = User::factory()->create(['created_at' => now()->subDays(3)]);
        $lastWeekUser = User::factory()->create(['created_at' => now()->subDays(10)]);

        $thisWeekUsers = User::thisWeek()->get();

        $this->assertTrue($thisWeekUsers->contains($thisWeekUser));
        $this->assertFalse($thisWeekUsers->contains($lastWeekUser));
    }

    /**
     * @test
     */
    public function it_can_scope_this_month_users()
    {
        $thisMonthUser = User::factory()->create(['created_at' => now()->subDays(15)]);
        $lastMonthUser = User::factory()->create(['created_at' => now()->subDays(35)]);

        $thisMonthUsers = User::thisMonth()->get();

        $this->assertTrue($thisMonthUsers->contains($thisMonthUser));
        $this->assertFalse($thisMonthUsers->contains($lastMonthUser));
    }

    /**
     * @test
     */
    public function it_can_scope_search_users()
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $searchResults = User::search('John')->get();

        $this->assertTrue($searchResults->contains($user1));
        $this->assertFalse($searchResults->contains($user2));
    }

    /**
     * @test
     */
    public function it_can_scope_with_player_info()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $usersWithPlayerInfo = User::withPlayerInfo()->get();

        $this->assertTrue($usersWithPlayerInfo->contains($user));
    }

    /**
     * @test
     */
    public function it_can_disable_auditing_for_admin_users()
    {
        $adminUser = User::factory()->create(['email' => 'admin@example.com']);
        $regularUser = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue($adminUser->isAuditingDisabled());
        $this->assertFalse($regularUser->isAuditingDisabled());
    }

    /**
     * @test
     */
    public function it_can_disable_auditing_for_old_users()
    {
        $oldUser = User::factory()->create(['created_at' => now()->subYears(2)]);
        $newUser = User::factory()->create(['created_at' => now()->subMonths(6)]);

        $this->assertTrue($oldUser->isAuditingDisabled());
        $this->assertFalse($newUser->isAuditingDisabled());
    }

    /**
     * @test
     */
    public function it_has_audit_exclude_attributes()
    {
        $user = new User();
        $exclude = $user->getAuditExclude();

        $this->assertContains('password', $exclude);
        $this->assertContains('remember_token', $exclude);
    }

    /**
     * @test
     */
    public function it_has_allowed_filters()
    {
        $user = new User();
        $filters = $user->allowedFilters();

        $this->assertInstanceOf(\IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList::class, $filters);
    }
}

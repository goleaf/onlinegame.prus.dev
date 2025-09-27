<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a clean database state
        $this->refreshDatabase();
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertEquals('+1234567890', $user->phone);
        $this->assertEquals('US', $user->phone_country);
    }

    /** @test */
    public function it_can_update_user_information()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function it_can_have_multiple_players()
    {
        $user = User::factory()->create();
        
        // Create multiple players for the user
        $player1 = Player::factory()->create(['user_id' => $user->id]);
        $player2 = Player::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->players);
        $this->assertTrue($user->players->contains($player1));
        $this->assertTrue($user->players->contains($player2));
    }

    /** @test */
    public function it_can_have_a_single_player()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Player::class, $user->player);
        $this->assertEquals($player->id, $user->player->id);
    }

    /** @test */
    public function it_returns_null_when_user_has_no_player()
    {
        $user = User::factory()->create();

        $this->assertNull($user->player);
    }

    /** @test */
    public function it_can_get_game_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'name' => 'TestPlayer',
            'points' => 1000,
            'is_active' => true,
            'is_online' => true,
        ]);

        $stats = $user->getGameStats();

        $this->assertIsArray($stats);
        $this->assertEquals($player->id, $stats['player_id']);
        $this->assertEquals('TestPlayer', $stats['player_name']);
        $this->assertEquals(1000, $stats['points']);
        $this->assertTrue($stats['is_active']);
        $this->assertTrue($stats['is_online']);
    }

    /** @test */
    public function it_returns_null_game_stats_when_no_player()
    {
        $user = User::factory()->create();

        $stats = $user->getGameStats();

        $this->assertNull($stats);
    }

    /** @test */
    public function it_can_check_active_game_session()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasActiveGameSession());
    }

    /** @test */
    public function it_returns_false_for_active_game_session_when_no_player()
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasActiveGameSession());
    }

    /** @test */
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

    /** @test */
    public function it_returns_updated_at_when_no_player_for_last_activity()
    {
        $user = User::factory()->create();
        $user->touch(); // Ensure updated_at is recent

        $lastActivity = $user->getLastActivity();

        $this->assertEquals($user->updated_at, $lastActivity);
    }

    /** @test */
    public function it_can_check_if_user_is_online()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_online' => true,
            'last_active_at' => now()->subMinutes(5), // Active within 15 minutes
        ]);

        $this->assertTrue($user->isOnline());
    }

    /** @test */
    public function it_returns_false_for_online_check_when_no_player()
    {
        $user = User::factory()->create();

        $this->assertFalse($user->isOnline());
    }

    /** @test */
    public function it_returns_false_for_online_check_when_inactive_too_long()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'is_online' => true,
            'last_active_at' => now()->subMinutes(20), // Inactive for more than 15 minutes
        ]);

        $this->assertFalse($user->isOnline());
    }

    /** @test */
    public function it_can_get_user_villages()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        // Create some villages for the player
        $villages = collect([
            ['name' => 'Village 1', 'population' => 100],
            ['name' => 'Village 2', 'population' => 200],
        ]);

        $userVillages = $user->getVillages();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $userVillages);
    }

    /** @test */
    public function it_can_get_capital_village()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $capitalVillage = $user->getCapitalVillage();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $user->getVillages());
    }

    /** @test */
    public function it_can_scope_users_with_game_players()
    {
        $userWithPlayer = User::factory()->create();
        $userWithoutPlayer = User::factory()->create();
        
        Player::factory()->create(['user_id' => $userWithPlayer->id]);

        $usersWithPlayers = User::withGamePlayers()->get();

        $this->assertTrue($usersWithPlayers->contains($userWithPlayer));
        $this->assertFalse($usersWithPlayers->contains($userWithoutPlayer));
    }

    /** @test */
    public function it_can_scope_active_game_users()
    {
        $activeUser = User::factory()->create();
        $inactiveUser = User::factory()->create();
        
        Player::factory()->create([
            'user_id' => $activeUser->id,
            'is_active' => true,
        ]);
        
        Player::factory()->create([
            'user_id' => $inactiveUser->id,
            'is_active' => false,
        ]);

        $activeUsers = User::activeGameUsers()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    /** @test */
    public function it_can_scope_online_users()
    {
        $onlineUser = User::factory()->create();
        $offlineUser = User::factory()->create();
        
        Player::factory()->create([
            'user_id' => $onlineUser->id,
            'is_online' => true,
            'last_active_at' => now()->subMinutes(5),
        ]);
        
        Player::factory()->create([
            'user_id' => $offlineUser->id,
            'is_online' => false,
            'last_active_at' => now()->subMinutes(20),
        ]);

        $onlineUsers = User::onlineUsers()->get();

        $this->assertTrue($onlineUsers->contains($onlineUser));
        $this->assertFalse($onlineUsers->contains($offlineUser));
    }

    /** @test */
    public function it_can_scope_users_by_world()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Player::factory()->create([
            'user_id' => $user1->id,
            'world_id' => 1,
        ]);
        
        Player::factory()->create([
            'user_id' => $user2->id,
            'world_id' => 2,
        ]);

        $world1Users = User::byWorld(1)->get();

        $this->assertTrue($world1Users->contains($user1));
        $this->assertFalse($world1Users->contains($user2));
    }

    /** @test */
    public function it_can_scope_users_by_tribe()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Player::factory()->create([
            'user_id' => $user1->id,
            'tribe' => 'Romans',
        ]);
        
        Player::factory()->create([
            'user_id' => $user2->id,
            'tribe' => 'Teutons',
        ]);

        $romanUsers = User::byTribe('Romans')->get();

        $this->assertTrue($romanUsers->contains($user1));
        $this->assertFalse($romanUsers->contains($user2));
    }

    /** @test */
    public function it_can_scope_users_by_alliance()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Player::factory()->create([
            'user_id' => $user1->id,
            'alliance_id' => 1,
        ]);
        
        Player::factory()->create([
            'user_id' => $user2->id,
            'alliance_id' => 2,
        ]);

        $alliance1Users = User::byAlliance(1)->get();

        $this->assertTrue($alliance1Users->contains($user1));
        $this->assertFalse($alliance1Users->contains($user2));
    }

    /** @test */
    public function it_can_scope_with_stats()
    {
        $user = User::factory()->create();
        
        $userWithStats = User::withStats()->find($user->id);

        $this->assertInstanceOf(User::class, $userWithStats);
        $this->assertArrayHasKey('player_count', $userWithStats->getAttributes());
        $this->assertArrayHasKey('village_count', $userWithStats->getAttributes());
        $this->assertArrayHasKey('total_population', $userWithStats->getAttributes());
        $this->assertArrayHasKey('total_battles', $userWithStats->getAttributes());
    }

    /** @test */
    public function it_can_scope_active_users()
    {
        $activeUser = User::factory()->create(['email_verified_at' => now()]);
        $inactiveUser = User::factory()->create(['email_verified_at' => null]);

        $activeUsers = User::active()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
    }

    /** @test */
    public function it_can_scope_verified_users()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        $verifiedUsers = User::verified()->get();

        $this->assertTrue($verifiedUsers->contains($verifiedUser));
        $this->assertFalse($verifiedUsers->contains($unverifiedUser));
    }

    /** @test */
    public function it_can_scope_unverified_users()
    {
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $unverifiedUser = User::factory()->create(['email_verified_at' => null]);

        $unverifiedUsers = User::unverified()->get();

        $this->assertFalse($unverifiedUsers->contains($verifiedUser));
        $this->assertTrue($unverifiedUsers->contains($unverifiedUser));
    }

    /** @test */
    public function it_can_scope_recent_users()
    {
        $recentUser = User::factory()->create(['created_at' => now()->subDays(3)]);
        $oldUser = User::factory()->create(['created_at' => now()->subDays(10)]);

        $recentUsers = User::recent(7)->get();

        $this->assertTrue($recentUsers->contains($recentUser));
        $this->assertFalse($recentUsers->contains($oldUser));
    }

    /** @test */
    public function it_can_scope_today_users()
    {
        $todayUser = User::factory()->create(['created_at' => today()]);
        $yesterdayUser = User::factory()->create(['created_at' => today()->subDay()]);

        $todayUsers = User::today()->get();

        $this->assertTrue($todayUsers->contains($todayUser));
        $this->assertFalse($todayUsers->contains($yesterdayUser));
    }

    /** @test */
    public function it_can_scope_this_week_users()
    {
        $thisWeekUser = User::factory()->create(['created_at' => now()->startOfWeek()->addDays(2)]);
        $lastWeekUser = User::factory()->create(['created_at' => now()->startOfWeek()->subDays(2)]);

        $thisWeekUsers = User::thisWeek()->get();

        $this->assertTrue($thisWeekUsers->contains($thisWeekUser));
        $this->assertFalse($thisWeekUsers->contains($lastWeekUser));
    }

    /** @test */
    public function it_can_scope_this_month_users()
    {
        $thisMonthUser = User::factory()->create(['created_at' => now()->startOfMonth()->addDays(5)]);
        $lastMonthUser = User::factory()->create(['created_at' => now()->startOfMonth()->subDays(5)]);

        $thisMonthUsers = User::thisMonth()->get();

        $this->assertTrue($thisMonthUsers->contains($thisMonthUser));
        $this->assertFalse($thisMonthUsers->contains($lastMonthUser));
    }

    /** @test */
    public function it_can_scope_search_users()
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $user3 = User::factory()->create(['name' => 'Bob Wilson', 'phone' => '+1234567890']);

        // Search by name
        $nameResults = User::search('John')->get();
        $this->assertTrue($nameResults->contains($user1));
        $this->assertFalse($nameResults->contains($user2));

        // Search by email
        $emailResults = User::search('jane@example.com')->get();
        $this->assertTrue($emailResults->contains($user2));
        $this->assertFalse($emailResults->contains($user1));

        // Search by phone
        $phoneResults = User::search('1234567890')->get();
        $this->assertTrue($phoneResults->contains($user3));
        $this->assertFalse($phoneResults->contains($user1));
    }

    /** @test */
    public function it_can_scope_with_player_info()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $userWithPlayerInfo = User::withPlayerInfo()->find($user->id);

        $this->assertTrue($userWithPlayerInfo->relationLoaded('player'));
        $this->assertEquals($player->id, $userWithPlayerInfo->player->id);
    }

    /** @test */
    public function it_can_use_allowed_filters()
    {
        $user = User::factory()->create();

        $allowedFilters = $user->allowedFilters();

        $this->assertInstanceOf(\IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList::class, $allowedFilters);
    }

    /** @test */
    public function it_excludes_sensitive_attributes_from_audit()
    {
        $user = User::factory()->create([
            'password' => 'secret123',
            'remember_token' => 'token123',
        ]);

        $this->assertContains('password', $user->getAuditExclude());
        $this->assertContains('remember_token', $user->getAuditExclude());
    }

    /** @test */
    public function it_implements_auditable_contract()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $user);
    }

    /** @test */
    public function it_has_auditing_disabled_method()
    {
        $user = User::factory()->create();

        $this->assertTrue(method_exists($user, 'auditingDisabled'));
        $this->assertIsBool($user->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_admin_users()
    {
        $adminUser = User::factory()->create(['email' => 'admin@example.com']);
        $regularUser = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue($adminUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_system_users()
    {
        $systemUser = User::factory()->create(['email' => 'system@example.com']);
        $regularUser = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue($systemUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_old_users()
    {
        $oldUser = User::factory()->create(['created_at' => now()->subYears(2)]);
        $newUser = User::factory()->create(['created_at' => now()->subMonths(6)]);

        $this->assertTrue($oldUser->auditingDisabled());
        $this->assertFalse($newUser->auditingDisabled());
    }

    /** @test */
    public function it_enables_auditing_by_default()
    {
        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
            'created_at' => now()->subMonths(6),
        ]);

        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_can_be_mass_assigned()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
            'reference_number' => 'REF123',
        ];

        $user = User::create($userData);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('+1234567890', $user->phone);
        $this->assertEquals('US', $user->phone_country);
        $this->assertEquals('REF123', $user->reference_number);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_serialization()
    {
        $user = User::factory()->create([
            'password' => 'secret123',
            'remember_token' => 'token123',
        ]);

        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
            'reference_number' => 'REF123',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertIsString($user->reference_number);
    }
}

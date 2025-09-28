<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\PlayerManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlayerManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
    }

    /**
     * @test
     */
    public function it_can_render_player_manager()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_player_manager_interface()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->assertSee('Player Manager')
            ->assertSee('Player Information')
            ->assertSee('Player Statistics')
            ->assertSee('Player Settings');
    }

    /**
     * @test
     */
    public function it_can_display_player_information()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->assertSee('Player Name')
            ->assertSee('Player Level')
            ->assertSee('Experience Points')
            ->assertSee('Player Rank');
    }

    /**
     * @test
     */
    public function it_can_display_player_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->assertSee('Player Statistics')
            ->assertSee('Total Battles')
            ->assertSee('Battles Won')
            ->assertSee('Battles Lost')
            ->assertSee('Win Rate');
    }

    /**
     * @test
     */
    public function it_can_display_player_settings()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->assertSee('Player Settings')
            ->assertSee('Notification Preferences')
            ->assertSee('Privacy Settings')
            ->assertSee('Game Preferences');
    }

    /**
     * @test
     */
    public function it_can_update_player_name()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('playerName', 'New Player Name')
            ->call('updatePlayerName')
            ->assertSee('Player name updated successfully')
            ->assertEmitted('playerNameUpdated');
    }

    /**
     * @test
     */
    public function it_can_update_player_avatar()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('updateAvatar', 'new_avatar.png')
            ->assertSee('Avatar updated successfully')
            ->assertEmitted('avatarUpdated');
    }

    /**
     * @test
     */
    public function it_can_update_player_bio()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('playerBio', 'This is my new bio')
            ->call('updatePlayerBio')
            ->assertSee('Player bio updated successfully')
            ->assertEmitted('playerBioUpdated');
    }

    /**
     * @test
     */
    public function it_can_update_notification_preferences()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('notificationPreferences', [
                'battle_notifications' => true,
                'alliance_notifications' => false,
                'resource_notifications' => true,
            ])
            ->call('updateNotificationPreferences')
            ->assertSee('Notification preferences updated')
            ->assertEmitted('notificationPreferencesUpdated');
    }

    /**
     * @test
     */
    public function it_can_update_privacy_settings()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('privacySettings', [
                'profile_visibility' => 'public',
                'battle_history_visibility' => 'alliance',
                'resource_visibility' => 'private',
            ])
            ->call('updatePrivacySettings')
            ->assertSee('Privacy settings updated')
            ->assertEmitted('privacySettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_update_game_preferences()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('gamePreferences', [
                'auto_collect_resources' => true,
                'auto_upgrade_buildings' => false,
                'auto_join_battles' => true,
            ])
            ->call('updateGamePreferences')
            ->assertSee('Game preferences updated')
            ->assertEmitted('gamePreferencesUpdated');
    }

    /**
     * @test
     */
    public function it_can_view_player_achievements()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('viewAchievements')
            ->assertSee('Player Achievements')
            ->assertSee('Completed Achievements')
            ->assertSee('In Progress Achievements')
            ->assertSee('Available Achievements');
    }

    /**
     * @test
     */
    public function it_can_view_player_history()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('viewPlayerHistory')
            ->assertSee('Player History')
            ->assertSee('Battle History')
            ->assertSee('Alliance History')
            ->assertSee('Resource History');
    }

    /**
     * @test
     */
    public function it_can_export_player_data()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('exportPlayerData')
            ->assertEmitted('playerDataExported');
    }

    /**
     * @test
     */
    public function it_can_import_player_data()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('importPlayerData', 'player_data.json')
            ->assertSee('Player data imported successfully')
            ->assertEmitted('playerDataImported');
    }

    /**
     * @test
     */
    public function it_can_configure_player_automation()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('automationSettings', [
                'auto_collect' => true,
                'auto_upgrade' => false,
                'auto_attack' => true,
                'auto_defend' => false,
            ])
            ->call('updateAutomationSettings')
            ->assertSee('Automation settings updated')
            ->assertEmitted('automationSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_player_scheduling()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('scheduleSettings', [
                'collection_schedule' => 'hourly',
                'upgrade_schedule' => 'daily',
                'attack_schedule' => 'weekly',
            ])
            ->call('updateScheduleSettings')
            ->assertSee('Schedule settings updated')
            ->assertEmitted('scheduleSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_player_limits()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('limitSettings', [
                'max_attacks_per_day' => 10,
                'max_upgrades_per_day' => 5,
                'max_trades_per_day' => 20,
            ])
            ->call('updateLimitSettings')
            ->assertSee('Limit settings updated')
            ->assertEmitted('limitSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_view_player_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('viewPlayerStatistics')
            ->assertSee('Player Statistics')
            ->assertSee('Total Experience')
            ->assertSee('Total Battles')
            ->assertSee('Win Rate')
            ->assertSee('Average Battle Duration');
    }

    /**
     * @test
     */
    public function it_can_configure_player_alerts()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->set('alertSettings', [
                'low_resource_alert' => true,
                'battle_alert' => true,
                'alliance_alert' => false,
            ])
            ->call('updateAlertSettings')
            ->assertSee('Alert settings updated')
            ->assertEmitted('alertSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_handle_player_errors()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('handlePlayerError', 'Player update failed')
            ->assertSee('Player Error: Player update failed')
            ->assertEmitted('playerErrorOccurred');
    }

    /**
     * @test
     */
    public function it_can_reset_player_manager()
    {
        Livewire::actingAs($this->user)
            ->test(PlayerManager::class)
            ->call('resetPlayerManager')
            ->assertSee('Player manager reset')
            ->assertEmitted('playerManagerReset');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(PlayerManager::class)
            ->assertSee('Please login to access Player Manager');
    }
}

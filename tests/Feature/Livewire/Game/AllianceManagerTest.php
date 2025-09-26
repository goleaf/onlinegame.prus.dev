<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\AllianceManager;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AllianceManagerTest extends TestCase
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
            'world_id' => $world->id,
        ]);

        $this->actingAs($user);
    }

    public function test_can_render_alliance_manager()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertStatus(200)
            ->assertSee('Alliance Manager');
    }

    public function test_loads_alliance_data_on_mount()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('world', $world)
            ->assertSet('alliances', [])
            ->assertSet('myAlliance', null);
    }

    public function test_can_toggle_real_time_updates()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('realTimeUpdates', true)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', false)
            ->call('toggleRealTimeUpdates')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_can_toggle_auto_refresh()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    }

    public function test_can_set_refresh_interval()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
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
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('gameSpeed', 1)
            ->call('setGameSpeed', 2)
            ->assertSet('gameSpeed', 2)
            ->call('setGameSpeed', 0.1)
            ->assertSet('gameSpeed', 0.5)
            ->call('setGameSpeed', 5)
            ->assertSet('gameSpeed', 3);
    }

    public function test_can_select_alliance()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('selectedAlliance', null)
            ->assertSet('showDetails', false)
            ->call('selectAlliance', $alliance->id)
            ->assertSet('selectedAlliance.id', $alliance->id)
            ->assertSet('showDetails', true);
    }

    public function test_can_toggle_details()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('showDetails', false)
            ->call('toggleDetails')
            ->assertSet('showDetails', true)
            ->call('toggleDetails')
            ->assertSet('showDetails', false);
    }

    public function test_can_select_member()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('selectedMember', null)
            ->call('selectMember', 1)
            ->assertSet('selectedMember', 1);
    }

    public function test_can_filter_by_rank()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('filterByRank', null)
            ->call('filterByRank', 'leader')
            ->assertSet('filterByRank', 'leader');
    }

    public function test_can_clear_filters()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->set('filterByRank', 'leader')
            ->set('searchQuery', 'test')
            ->set('showOnlyActive', true)
            ->set('showOnlyInvites', true)
            ->set('showOnlyApplications', true)
            ->call('clearFilters')
            ->assertSet('filterByRank', null)
            ->assertSet('searchQuery', '')
            ->assertSet('showOnlyActive', false)
            ->assertSet('showOnlyInvites', false)
            ->assertSet('showOnlyApplications', false);
    }

    public function test_can_sort_alliances()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('sortBy', 'name')
            ->assertSet('sortOrder', 'asc')
            ->call('sortAlliances', 'members_count')
            ->assertSet('sortBy', 'members_count')
            ->assertSet('sortOrder', 'asc')
            ->call('sortAlliances', 'members_count')
            ->assertSet('sortBy', 'members_count')
            ->assertSet('sortOrder', 'desc');
    }

    public function test_can_search_alliances()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->set('searchQuery', 'test alliance')
            ->call('searchAlliances')
            ->assertSet('searchQuery', 'test alliance');
    }

    public function test_can_toggle_active_filter()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('showOnlyActive', false)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', true)
            ->call('toggleActiveFilter')
            ->assertSet('showOnlyActive', false);
    }

    public function test_can_toggle_invites_filter()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('showOnlyInvites', false)
            ->call('toggleInvitesFilter')
            ->assertSet('showOnlyInvites', true)
            ->call('toggleInvitesFilter')
            ->assertSet('showOnlyInvites', false);
    }

    public function test_can_toggle_applications_filter()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('showOnlyApplications', false)
            ->call('toggleApplicationsFilter')
            ->assertSet('showOnlyApplications', true)
            ->call('toggleApplicationsFilter')
            ->assertSet('showOnlyApplications', false);
    }

    public function test_can_create_alliance()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('createAlliance', 'Test Alliance', 'TEST', 'Test description')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Alliance 'Test Alliance' created successfully")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_create_alliance_without_name()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('createAlliance', '', 'TEST', 'Test description')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Alliance name and tag are required')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_cannot_create_alliance_without_tag()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('createAlliance', 'Test Alliance', '', 'Test description')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Alliance name and tag are required')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_join_alliance()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('joinAlliance', $alliance->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Joined alliance '{$alliance->name}'")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_join_nonexistent_alliance()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('joinAlliance', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Alliance not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_leave_alliance()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);
        $player = Player::where('user_id', auth()->id())->first();
        $player->update(['alliance_id' => $alliance->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('leaveAlliance')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Left alliance '{$alliance->name}")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_leave_alliance_when_not_in_one()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('leaveAlliance')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'You are not in an alliance')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_invite_player()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);
        $player = Player::where('user_id', auth()->id())->first();
        $player->update(['alliance_id' => $alliance->id, 'alliance_rank' => 'leader']);

        $targetPlayer = Player::factory()->create(['world_id' => $world->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('invitePlayer', $targetPlayer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Invitation sent to {$targetPlayer->name}")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_invite_player_without_permission()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);
        $player = Player::where('user_id', auth()->id())->first();
        $player->update(['alliance_id' => $alliance->id, 'alliance_rank' => 'member']);

        $targetPlayer = Player::factory()->create(['world_id' => $world->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('invitePlayer', $targetPlayer->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'You do not have permission to invite players')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_can_apply_to_alliance()
    {
        $world = World::first();
        $alliance = Alliance::factory()->create(['world_id' => $world->id]);

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('applyToAlliance', $alliance->id)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', "Application sent to '{$alliance->name}'")
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_cannot_apply_to_nonexistent_alliance()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('applyToAlliance', 999)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Alliance not found')
            ->assertSet('notifications.0.type', 'error');
    }

    public function test_get_alliance_icon()
    {
        $world = World::first();

        $component = Livewire::test(AllianceManager::class, ['world' => $world]);

        $this->assertEquals('👑', $component->instance()->getAllianceIcon(['rank' => 'leader']));
        $this->assertEquals('⭐', $component->instance()->getAllianceIcon(['rank' => 'co_leader']));
        $this->assertEquals('👤', $component->instance()->getAllianceIcon(['rank' => 'member']));
        $this->assertEquals('🏰', $component->instance()->getAllianceIcon(['rank' => 'unknown']));
    }

    public function test_get_alliance_color()
    {
        $world = World::first();

        $component = Livewire::test(AllianceManager::class, ['world' => $world]);

        $alliance = ['is_active' => true, 'members_count' => 5, 'max_members' => 10];
        $this->assertEquals('green', $component->instance()->getAllianceColor($alliance));

        $alliance = ['is_active' => false, 'members_count' => 5, 'max_members' => 10];
        $this->assertEquals('red', $component->instance()->getAllianceColor($alliance));

        $alliance = ['is_active' => true, 'members_count' => 10, 'max_members' => 10];
        $this->assertEquals('blue', $component->instance()->getAllianceColor($alliance));
    }

    public function test_get_alliance_status()
    {
        $world = World::first();

        $component = Livewire::test(AllianceManager::class, ['world' => $world]);

        $alliance = ['is_active' => false, 'members_count' => 5, 'max_members' => 10];
        $this->assertEquals('Inactive', $component->instance()->getAllianceStatus($alliance));

        $alliance = ['is_active' => true, 'members_count' => 10, 'max_members' => 10];
        $this->assertEquals('Full', $component->instance()->getAllianceStatus($alliance));

        $alliance = ['is_active' => true, 'members_count' => 5, 'max_members' => 10];
        $this->assertEquals('Active', $component->instance()->getAllianceStatus($alliance));
    }

    public function test_notification_system()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('notifications', [])
            ->call('addNotification', 'Test notification', 'info')
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Test notification')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_can_remove_notification()
    {
        $world = World::first();

        $component = Livewire::test(AllianceManager::class, ['world' => $world]);

        $component->call('addNotification', 'Test notification', 'info');
        $notificationId = $component->get('notifications.0.id');

        $component
            ->call('removeNotification', $notificationId)
            ->assertCount('notifications', 0);
    }

    public function test_can_clear_notifications()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->call('addNotification', 'Test notification 1', 'info')
            ->call('addNotification', 'Test notification 2', 'success')
            ->assertCount('notifications', 2)
            ->call('clearNotifications')
            ->assertCount('notifications', 0);
    }

    public function test_calculates_alliance_stats()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('allianceStats', [])
            ->call('calculateAllianceStats')
            ->assertSet('allianceStats', []);
    }

    public function test_calculates_member_stats()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('memberStats', [])
            ->call('calculateMemberStats')
            ->assertSet('memberStats', []);
    }

    public function test_calculates_invite_stats()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('inviteStats', [])
            ->call('calculateInviteStats')
            ->assertSet('inviteStats', []);
    }

    public function test_calculates_application_stats()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('applicationStats', [])
            ->call('calculateApplicationStats')
            ->assertSet('applicationStats', []);
    }

    public function test_handles_game_tick_processed()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSet('realTimeUpdates', true)
            ->dispatch('gameTickProcessed')
            ->assertSet('realTimeUpdates', true);
    }

    public function test_handles_alliance_updated()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('allianceUpdated', ['alliance_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Alliance updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_member_joined()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('memberJoined', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'New member joined')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_member_left()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('memberLeft', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Member left alliance')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_invite_sent()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('inviteSent', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Invitation sent')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_invite_accepted()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('inviteAccepted', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Invitation accepted')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_invite_declined()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('inviteDeclined', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Invitation declined')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_application_submitted()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('applicationSubmitted', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'New application received')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_application_accepted()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('applicationAccepted', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Application accepted')
            ->assertSet('notifications.0.type', 'success');
    }

    public function test_handles_application_declined()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('applicationDeclined', ['alliance_id' => 1, 'player_id' => 1])
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Application declined')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_handles_village_selected()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->dispatch('villageSelected', 1)
            ->assertCount('notifications', 1)
            ->assertSet('notifications.0.message', 'Village selected - alliance data updated')
            ->assertSet('notifications.0.type', 'info');
    }

    public function test_component_renders_with_all_data()
    {
        $world = World::first();

        Livewire::test(AllianceManager::class, ['world' => $world])
            ->assertSee('Alliance Manager')
            ->assertSee('Alliances')
            ->assertSee('Members');
    }

    public function test_handles_missing_world()
    {
        Livewire::test(AllianceManager::class, ['world' => null])
            ->assertSet('world', null);
    }
}

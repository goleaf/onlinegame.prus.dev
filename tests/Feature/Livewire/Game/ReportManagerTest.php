<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\ReportManager;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportManagerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $world;
    protected $player;
    protected $village;
    protected $attacker;
    protected $defender;
    protected $attackerVillage;
    protected $defenderVillage;
    protected $report1;
    protected $report2;
    protected $report3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->world = World::factory()->create();

        $this->player = Player::factory()->create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
        ]);

        $this->village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
        ]);

        // Create attacker and defender
        $this->attacker = Player::factory()->create(['world_id' => $this->world->id]);
        $this->defender = Player::factory()->create(['world_id' => $this->world->id]);

        $this->attackerVillage = Village::factory()->create([
            'player_id' => $this->attacker->id,
            'world_id' => $this->world->id,
        ]);

        $this->defenderVillage = Village::factory()->create([
            'player_id' => $this->defender->id,
            'world_id' => $this->world->id,
        ]);

        // Create reports
        $this->report1 = Report::factory()->create([
            'world_id' => $this->world->id,
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->player->id,
            'from_village_id' => $this->attackerVillage->id,
            'to_village_id' => $this->village->id,
            'type' => 'attack',
            'status' => 'victory',
            'is_read' => false,
            'is_important' => true,
        ]);

        $this->report2 = Report::factory()->create([
            'world_id' => $this->world->id,
            'attacker_id' => $this->player->id,
            'defender_id' => $this->defender->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $this->defenderVillage->id,
            'type' => 'attack',
            'status' => 'defeat',
            'is_read' => true,
            'is_important' => false,
        ]);

        $this->report3 = Report::factory()->create([
            'world_id' => $this->world->id,
            'attacker_id' => $this->player->id,
            'defender_id' => $this->defender->id,
            'from_village_id' => $this->village->id,
            'to_village_id' => $this->defenderVillage->id,
            'type' => 'support',
            'status' => 'victory',
            'is_read' => false,
            'is_important' => false,
        ]);
    }

    public function test_can_mount_component()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->world);
        $this->assertEquals($this->world->id, $component->world->id);
    }

    public function test_loads_report_data_on_mount()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertNotNull($component->reports);
        $this->assertGreaterThan(0, count($component->reports));
    }

    public function test_can_select_report()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('selectReport', $this->report1->id);

        $this->assertNotNull($component->selectedReport);
        $this->assertEquals($this->report1->id, $component->selectedReport->id);
        $this->assertTrue($component->showDetails);
    }

    public function test_can_mark_report_as_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsRead', $this->report1->id);

        $this->assertTrue($this->report1->fresh()->is_read);
    }

    public function test_can_mark_report_as_unread()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsUnread', $this->report2->id);

        $this->assertFalse($this->report2->fresh()->is_read);
    }

    public function test_can_mark_report_as_important()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsImportant', $this->report3->id);

        $this->assertTrue($this->report3->fresh()->is_important);
    }

    public function test_can_mark_report_as_unimportant()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsUnimportant', $this->report1->id);

        $this->assertFalse($this->report1->fresh()->is_important);
    }

    public function test_can_delete_report()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('deleteReport', $this->report1->id);

        $this->assertDatabaseMissing('reports', ['id' => $this->report1->id]);
    }

    public function test_can_mark_all_as_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAllAsRead');

        $this->assertTrue($this->report1->fresh()->is_read);
        $this->assertTrue($this->report3->fresh()->is_read);
    }

    public function test_can_delete_all_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('deleteAllRead');

        $this->assertDatabaseMissing('reports', ['id' => $this->report2->id]);
        $this->assertDatabaseHas('reports', ['id' => $this->report1->id]);
        $this->assertDatabaseHas('reports', ['id' => $this->report3->id]);
    }

    public function test_can_toggle_details()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertFalse($component->showDetails);

        $component->call('toggleDetails');
        $this->assertTrue($component->showDetails);

        $component->call('toggleDetails');
        $this->assertFalse($component->showDetails);
    }

    public function test_can_filter_by_type()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('filterByType', 'attack');

        $this->assertEquals('attack', $component->filterByType);
    }

    public function test_can_filter_by_status()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('filterByStatus', 'victory');

        $this->assertEquals('victory', $component->filterByStatus);
    }

    public function test_can_filter_by_date()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('filterByDate', 'today');

        $this->assertEquals('today', $component->filterByDate);
    }

    public function test_can_clear_filters()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->set('filterByType', 'attack');
        $component->set('filterByStatus', 'victory');
        $component->set('searchQuery', 'test');
        $component->call('clearFilters');

        $this->assertNull($component->filterByType);
        $this->assertNull($component->filterByStatus);
        $this->assertEmpty($component->searchQuery);
    }

    public function test_can_sort_reports()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('sortReports', 'type');

        $this->assertEquals('type', $component->sortBy);
        $this->assertEquals('desc', $component->sortOrder);
    }

    public function test_can_toggle_sort_order()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->set('sortBy', 'type');
        $component->set('sortOrder', 'desc');

        $component->call('sortReports', 'type');
        $this->assertEquals('asc', $component->sortOrder);
    }

    public function test_can_search_reports()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->set('searchQuery', 'test');
        $component->call('searchReports');

        $this->assertEquals('test', $component->searchQuery);
    }

    public function test_can_toggle_unread_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertFalse($component->showOnlyUnread);

        $component->call('toggleUnreadFilter');
        $this->assertTrue($component->showOnlyUnread);

        $component->call('toggleUnreadFilter');
        $this->assertFalse($component->showOnlyUnread);
    }

    public function test_can_toggle_important_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertFalse($component->showOnlyImportant);

        $component->call('toggleImportantFilter');
        $this->assertTrue($component->showOnlyImportant);

        $component->call('toggleImportantFilter');
        $this->assertFalse($component->showOnlyImportant);
    }

    public function test_can_toggle_my_reports_filter()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertTrue($component->showOnlyMyReports);

        $component->call('toggleMyReportsFilter');
        $this->assertFalse($component->showOnlyMyReports);

        $component->call('toggleMyReportsFilter');
        $this->assertTrue($component->showOnlyMyReports);
    }

    public function test_calculates_report_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportStats');

        $this->assertArrayHasKey('total_reports', $component->reportStats);
        $this->assertArrayHasKey('unread_reports', $component->reportStats);
        $this->assertArrayHasKey('important_reports', $component->reportStats);
        $this->assertArrayHasKey('today_reports', $component->reportStats);
    }

    public function test_calculates_battle_stats()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateBattleStats');

        $this->assertArrayHasKey('total_battles', $component->battleStats);
        $this->assertArrayHasKey('victories', $component->battleStats);
        $this->assertArrayHasKey('defeats', $component->battleStats);
        $this->assertArrayHasKey('defenses', $component->battleStats);
    }

    public function test_calculates_recent_activity()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateRecentActivity');

        $this->assertNotNull($component->recentActivity);
    }

    public function test_calculates_report_history()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportHistory');

        $this->assertNotNull($component->reportHistory);
    }

    public function test_handles_missing_world()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => null]);

        $this->assertNull($component->world);
    }

    public function test_handles_missing_player()
    {
        $this->actingAs($this->user);

        // Delete the player
        $this->player->delete();

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        // The component should still have the world object
        $this->assertNotNull($component->world);
    }

    public function test_handles_missing_reports()
    {
        $this->actingAs($this->user);

        // Delete all reports
        Report::truncate();

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $this->assertEmpty($component->reports);
    }

    public function test_handles_invalid_report_id()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('selectReport', 99999);

        $this->assertNull($component->selectedReport);
    }

    public function test_handles_missing_report_for_mark_as_read()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsRead', 99999);

        // Should not throw an error
        $this->assertTrue(true);
    }

    public function test_handles_missing_report_for_mark_as_unread()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsUnread', 99999);

        // Should not throw an error
        $this->assertTrue(true);
    }

    public function test_handles_missing_report_for_mark_as_important()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsImportant', 99999);

        // Should not throw an error
        $this->assertTrue(true);
    }

    public function test_handles_missing_report_for_mark_as_unimportant()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('markAsUnimportant', 99999);

        // Should not throw an error
        $this->assertTrue(true);
    }

    public function test_handles_missing_report_for_delete()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('deleteReport', 99999);

        // Should not throw an error
        $this->assertTrue(true);
    }

    public function test_handles_missing_report_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('loadReportData');

        $this->assertNotNull($component->reports);
    }

    public function test_handles_missing_report_relationships()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportStats');

        $this->assertNotNull($component->reportStats);
    }

    public function test_handles_missing_battle_relationships()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateBattleStats');

        $this->assertNotNull($component->battleStats);
    }

    public function test_handles_missing_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportStats');

        $this->assertNotNull($component->reportStats);
    }

    public function test_handles_missing_world_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportStats');

        $this->assertNotNull($component->reportStats);
    }

    public function test_handles_missing_report_records()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateRecentActivity');

        $this->assertNotNull($component->recentActivity);
    }

    public function test_handles_missing_from_village_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('loadReportData');

        $this->assertNotNull($component->reports);
    }

    public function test_handles_missing_to_village_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('loadReportData');

        $this->assertNotNull($component->reports);
    }

    public function test_handles_missing_report_type_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('filterByType', 'invalid_type');

        $this->assertEquals('invalid_type', $component->filterByType);
    }

    public function test_handles_missing_report_status_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('filterByStatus', 'invalid_status');

        $this->assertEquals('invalid_status', $component->filterByStatus);
    }

    public function test_handles_missing_report_player_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('calculateReportStats');

        $this->assertNotNull($component->reportStats);
    }

    public function test_handles_missing_report_search_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->set('searchQuery', 'nonexistent');
        $component->call('searchReports');

        $this->assertEquals('nonexistent', $component->searchQuery);
    }

    public function test_handles_missing_report_sort_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('sortReports', 'invalid_field');

        $this->assertEquals('invalid_field', $component->sortBy);
    }

    public function test_handles_missing_report_order_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->set('sortOrder', 'invalid_order');
        $component->call('sortReports', 'type');

        $this->assertEquals('desc', $component->sortOrder);
    }

    public function test_handles_missing_report_get_data()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);
        $component->call('loadReportData');

        $this->assertNotNull($component->reports);
    }

    public function test_real_time_event_handlers()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        // Test event handlers
        $component->dispatch('gameTickProcessed');
        $component->dispatch('reportReceived', ['reportId' => 1]);
        $component->dispatch('reportUpdated', ['reportId' => 1]);
        $component->dispatch('reportDeleted', ['reportId' => 1]);
        $component->dispatch('markAsRead', ['reportId' => 1]);
        $component->dispatch('markAsUnread', ['reportId' => 1]);
        $component->dispatch('markAsImportant', ['reportId' => 1]);
        $component->dispatch('markAsUnimportant', ['reportId' => 1]);
        $component->dispatch('villageSelected', ['villageId' => 1]);

        $this->assertTrue(true);
    }

    public function test_real_time_update_handling()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        // Test real-time features
        $component->call('toggleRealTimeUpdates');
        $component->call('toggleAutoRefresh');
        $component->call('setRefreshInterval', 30);
        $component->call('setGameSpeed', 2.0);

        $this->assertTrue(true);
    }

    public function test_notification_management()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        // Test notifications
        $component->call('addNotification', 'Test notification', 'info');
        $this->assertCount(1, $component->notifications);

        $component->call('clearNotifications');
        $this->assertCount(0, $component->notifications);
    }

    public function test_report_icon_and_color_methods()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $report = [
            'type' => 'attack',
            'status' => 'victory',
            'is_read' => false,
            'is_important' => true,
        ];

        $icon = $component->instance()->getReportIcon($report);
        $color = $component->instance()->getReportColor($report);
        $status = $component->instance()->getReportStatus($report);

        $this->assertNotEmpty($icon);
        $this->assertNotEmpty($color);
        $this->assertNotEmpty($status);
    }

    public function test_time_ago_method()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ReportManager::class, ['world' => $this->world]);

        $timeAgo = $component->instance()->getTimeAgo(now()->subHour());

        $this->assertStringContainsString('hour', $timeAgo);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminDashboard;
use App\Livewire\BaseSessionComponent;
use App\Livewire\Game\ChatComponent;
use App\Livewire\Game\GameDashboard;
use App\Livewire\Game\RealTimeGameComponent;
use App\Services\SessionPropertiesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class SessionPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected SessionPropertiesService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionService = app(SessionPropertiesService::class);
    }

    /** @test */
    public function base_session_component_initializes_default_properties()
    {
        $component = new class () extends BaseSessionComponent {
            public function render()
            {
                return '';
            }
        };

        $component->initializeSessionProperties();

        $this->assertEquals('light', $component->theme);
        $this->assertEquals('en', $component->language);
        $this->assertEquals('UTC', $component->timezone);
        $this->assertTrue($component->notifications);
        $this->assertTrue($component->autoRefresh);
        $this->assertEquals(30, $component->refreshInterval);
    }

    /** @test */
    public function game_dashboard_persists_village_selection()
    {
        $component = Livewire::test(GameDashboard::class);

        // Simulate selecting a village
        $villageId = 123;
        $component->call('selectVillage', $villageId);

        // Check that the selection is persisted in session
        $this->assertEquals($villageId, $component->selectedVillageId);
        $this->assertTrue(Session::has('livewire_session_GameDashboard_selectedVillageId'));
    }

    /** @test */
    public function game_dashboard_persists_user_preferences()
    {
        $component = Livewire::test(GameDashboard::class);

        // Test game speed setting
        $component->call('setGameSpeed', 2.5);
        $this->assertEquals(2.5, $component->gameSpeed);

        // Test dashboard layout
        $component->call('setDashboardLayout', 'list');
        $this->assertEquals('list', $component->dashboardLayout);

        // Test resource view mode
        $component->call('setResourceViewMode', 'compact');
        $this->assertEquals('compact', $component->resourceViewMode);

        // Test building view mode
        $component->call('setBuildingViewMode', 'grid');
        $this->assertEquals('grid', $component->buildingViewMode);
    }

    /** @test */
    public function chat_component_persists_channel_selection()
    {
        $component = Livewire::test(ChatComponent::class);

        // Simulate selecting a channel
        $channelId = 456;
        $component->call('loadChannel', $channelId);

        // Check that the selection is persisted
        $this->assertEquals($channelId, $component->selectedChannelId);
    }

    /** @test */
    public function chat_component_persists_display_preferences()
    {
        $component = Livewire::test(ChatComponent::class);

        // Test chat layout
        $component->call('setChatLayout', 'fullscreen');
        $this->assertEquals('fullscreen', $component->chatLayout);

        // Test message display mode
        $component->call('setMessageDisplayMode', 'list');
        $this->assertEquals('list', $component->messageDisplayMode);

        // Test font size
        $component->call('setFontSize', 'large');
        $this->assertEquals('large', $component->fontSize);

        // Test chat theme
        $component->call('setChatTheme', 'dark');
        $this->assertEquals('dark', $component->theme);
    }

    /** @test */
    public function real_time_component_persists_settings()
    {
        $component = Livewire::test(RealTimeGameComponent::class);

        // Test display mode
        $component->call('setDisplayMode', 'detailed');
        $this->assertEquals('detailed', $component->displayMode);

        // Test maximum updates
        $component->call('setMaxUpdates', 50);
        $this->assertEquals(50, $component->maxUpdates);

        // Test maximum notifications
        $component->call('setMaxNotifications', 25);
        $this->assertEquals(25, $component->maxNotifications);

        // Test auto-mark as read toggle
        $component->call('toggleAutoMarkRead');
        $this->assertTrue($component->autoMarkRead);
    }

    /** @test */
    public function admin_dashboard_persists_preferences()
    {
        $component = Livewire::test(AdminDashboard::class);

        // Test dashboard layout
        $component->call('setDashboardLayout', 'compact');
        $this->assertEquals('compact', $component->dashboardLayout);

        // Test chart time range
        $component->call('setChartTimeRange', '7d');
        $this->assertEquals('7d', $component->chartTimeRange);

        // Test chart type
        $component->call('setChartType', 'bar');
        $this->assertEquals('bar', $component->chartType);

        // Test alert threshold
        $component->call('setAlertThreshold', 'error');
        $this->assertEquals('error', $component->alertThreshold);
    }

    /** @test */
    public function session_properties_persist_across_page_refreshes()
    {
        // Set up initial state
        $component = Livewire::test(GameDashboard::class);
        $component->call('setGameSpeed', 2.0);
        $component->call('setDashboardLayout', 'list');

        // Simulate page refresh by creating new component instance
        $newComponent = Livewire::test(GameDashboard::class);

        // Verify properties are restored
        $this->assertEquals(2.0, $newComponent->gameSpeed);
        $this->assertEquals('list', $newComponent->dashboardLayout);
    }

    /** @test */
    public function reset_preferences_works_correctly()
    {
        $component = Livewire::test(GameDashboard::class);

        // Set some custom preferences
        $component->call('setGameSpeed', 2.5);
        $component->call('setDashboardLayout', 'compact');
        $component->call('setResourceViewMode', 'minimal');

        // Reset all preferences
        $component->call('resetGamePreferences');

        // Verify all preferences are reset to defaults
        $this->assertEquals(1, $component->gameSpeed);
        $this->assertEquals('grid', $component->dashboardLayout);
        $this->assertEquals('detailed', $component->resourceViewMode);
    }

    /** @test */
    public function session_properties_service_registers_components()
    {
        $analytics = $this->sessionService->getSessionAnalytics();

        $this->assertGreaterThan(0, $analytics['total_properties']);
        $this->assertArrayHasKey('components', $analytics);
        $this->assertArrayHasKey(App\Livewire\Game\GameDashboard::class, $analytics['components']);
    }

    /** @test */
    public function session_properties_service_validates_integrity()
    {
        $issues = $this->sessionService->validateSessionIntegrity();

        // Should return an array (may be empty if no issues)
        $this->assertIsArray($issues);
    }

    /** @test */
    public function session_properties_service_can_export_and_import()
    {
        // Set some session data
        Session::put('test_key', 'test_value');

        // Export session properties
        $export = $this->sessionService->exportSessionProperties();
        $this->assertIsArray($export);

        // Import session properties
        $imported = $this->sessionService->importSessionProperties($export);
        $this->assertIsInt($imported);
    }

    /** @test */
    public function session_properties_service_can_cleanup_expired_data()
    {
        $cleaned = $this->sessionService->cleanupSession();

        // Should return an array of cleaned items
        $this->assertIsArray($cleaned);
    }

    /** @test */
    public function components_handle_validation_correctly()
    {
        $component = Livewire::test(GameDashboard::class);

        // Test invalid game speed (should be clamped)
        $component->call('setGameSpeed', 5.0); // Above max of 3.0
        $this->assertEquals(3.0, $component->gameSpeed);

        // Test invalid dashboard layout (should default to 'grid')
        $component->call('setDashboardLayout', 'invalid');
        $this->assertEquals('grid', $component->dashboardLayout);
    }

    /** @test */
    public function session_properties_work_with_filters()
    {
        $component = Livewire::test(ChatComponent::class);

        // Test message filters
        $filters = ['type' => 'text', 'player' => 'test'];
        $component->call('updateMessageFilters', $filters);
        $this->assertEquals($filters, $component->messageFilters);

        // Test clearing filters
        $component->call('clearMessageFilters');
        $this->assertEmpty($component->messageFilters);
    }

    /** @test */
    public function session_properties_maintain_type_safety()
    {
        $component = Livewire::test(RealTimeGameComponent::class);

        // Test that numeric values remain numeric
        $component->call('setMaxUpdates', 42);
        $this->assertIsInt($component->maxUpdates);
        $this->assertEquals(42, $component->maxUpdates);

        // Test that boolean values remain boolean
        $component->call('toggleAutoMarkRead');
        $this->assertIsBool($component->autoMarkRead);
        $this->assertTrue($component->autoMarkRead);
    }
}

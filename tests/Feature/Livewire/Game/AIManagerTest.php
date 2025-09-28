<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\AIManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AIManagerTest extends TestCase
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
    public function it_can_render_ai_manager()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_ai_status()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->assertSee('AI Status')
            ->assertSee('Active AI Tasks')
            ->assertSee('AI Configuration');
    }

    /**
     * @test
     */
    public function it_can_enable_ai_assistance()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('aiEnabled', true)
            ->call('toggleAI')
            ->assertSee('AI assistance enabled')
            ->assertEmitted('aiStatusChanged');
    }

    /**
     * @test
     */
    public function it_can_disable_ai_assistance()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('aiEnabled', false)
            ->call('toggleAI')
            ->assertSee('AI assistance disabled')
            ->assertEmitted('aiStatusChanged');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_behavior()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('aiBehavior', 'aggressive')
            ->call('updateAIBehavior')
            ->assertSee('AI behavior updated to aggressive')
            ->assertEmitted('aiBehaviorChanged');
    }

    /**
     * @test
     */
    public function it_can_set_ai_priorities()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('aiPriorities', [
                'resource_production' => 80,
                'defense' => 60,
                'expansion' => 40,
            ])
            ->call('updateAIPriorities')
            ->assertSee('AI priorities updated')
            ->assertEmitted('aiPrioritiesChanged');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_automation()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('automationSettings', [
                'auto_build' => true,
                'auto_research' => false,
                'auto_trade' => true,
                'auto_attack' => false,
            ])
            ->call('updateAutomationSettings')
            ->assertSee('Automation settings updated')
            ->assertEmitted('automationSettingsChanged');
    }

    /**
     * @test
     */
    public function it_can_view_ai_recommendations()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('getAIRecommendations')
            ->assertSee('AI Recommendations')
            ->assertSee('Resource Management')
            ->assertSee('Building Suggestions')
            ->assertSee('Defense Recommendations');
    }

    /**
     * @test
     */
    public function it_can_accept_ai_recommendation()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('acceptRecommendation', 'build_barracks')
            ->assertSee('Recommendation accepted')
            ->assertEmitted('recommendationAccepted');
    }

    /**
     * @test
     */
    public function it_can_reject_ai_recommendation()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('rejectRecommendation', 'build_barracks')
            ->assertSee('Recommendation rejected')
            ->assertEmitted('recommendationRejected');
    }

    /**
     * @test
     */
    public function it_can_view_ai_activity_log()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('viewActivityLog')
            ->assertSee('AI Activity Log')
            ->assertSee('Recent AI Actions')
            ->assertSee('AI Decision History');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_learning()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('learningSettings', [
                'learning_enabled' => true,
                'learning_rate' => 0.1,
                'memory_size' => 1000,
            ])
            ->call('updateLearningSettings')
            ->assertSee('Learning settings updated')
            ->assertEmitted('learningSettingsChanged');
    }

    /**
     * @test
     */
    public function it_can_train_ai_model()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('trainAIModel')
            ->assertSee('AI model training started')
            ->assertEmitted('aiModelTrainingStarted');
    }

    /**
     * @test
     */
    public function it_can_view_ai_performance_metrics()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('viewPerformanceMetrics')
            ->assertSee('AI Performance Metrics')
            ->assertSee('Success Rate')
            ->assertSee('Accuracy')
            ->assertSee('Response Time');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_safety_settings()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('safetySettings', [
                'max_resource_usage' => 80,
                'max_attack_frequency' => 3,
                'emergency_stop' => false,
            ])
            ->call('updateSafetySettings')
            ->assertSee('Safety settings updated')
            ->assertEmitted('safetySettingsChanged');
    }

    /**
     * @test
     */
    public function it_can_export_ai_data()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('exportAIData')
            ->assertEmitted('aiDataExported');
    }

    /**
     * @test
     */
    public function it_can_import_ai_data()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('importAIData', 'ai_data.json')
            ->assertSee('AI data imported successfully')
            ->assertEmitted('aiDataImported');
    }

    /**
     * @test
     */
    public function it_can_reset_ai_to_defaults()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('resetAIToDefaults')
            ->assertSee('AI reset to default settings')
            ->assertEmitted('aiResetToDefaults');
    }

    /**
     * @test
     */
    public function it_can_view_ai_help()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('showAIHelp')
            ->assertSee('AI Help')
            ->assertSee('Getting Started')
            ->assertSee('Configuration Guide')
            ->assertSee('Troubleshooting');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_notifications()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('notificationSettings', [
                'ai_actions' => true,
                'recommendations' => true,
                'errors' => true,
                'performance' => false,
            ])
            ->call('updateNotificationSettings')
            ->assertSee('Notification settings updated')
            ->assertEmitted('notificationSettingsChanged');
    }

    /**
     * @test
     */
    public function it_can_view_ai_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('viewAIStatistics')
            ->assertSee('AI Statistics')
            ->assertSee('Total AI Actions')
            ->assertSee('Success Rate')
            ->assertSee('Average Response Time');
    }

    /**
     * @test
     */
    public function it_can_configure_ai_limits()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->set('aiLimits', [
                'max_actions_per_hour' => 100,
                'max_resource_spending' => 10000,
                'max_attack_frequency' => 5,
            ])
            ->call('updateAILimits')
            ->assertSee('AI limits updated')
            ->assertEmitted('aiLimitsChanged');
    }

    /**
     * @test
     */
    public function it_can_handle_ai_errors()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('handleAIError', 'AI service unavailable')
            ->assertSee('AI Error: AI service unavailable')
            ->assertEmitted('aiErrorOccurred');
    }

    /**
     * @test
     */
    public function it_can_restart_ai_service()
    {
        Livewire::actingAs($this->user)
            ->test(AIManager::class)
            ->call('restartAIService')
            ->assertSee('AI service restarting...')
            ->assertEmitted('aiServiceRestarted');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(AIManager::class)
            ->assertSee('Please login to access AI Manager');
    }
}

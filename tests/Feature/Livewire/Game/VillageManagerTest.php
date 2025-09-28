<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\VillageManager;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VillageManagerTest extends TestCase
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
    public function it_can_render_village_manager()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_display_village_manager_interface()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->assertSee('Village Manager')
            ->assertSee('Village Information')
            ->assertSee('Buildings')
            ->assertSee('Resources');
    }

    /**
     * @test
     */
    public function it_can_display_village_information()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->assertSee('Village Name')
            ->assertSee('Village Level')
            ->assertSee('Population')
            ->assertSee('Location');
    }

    /**
     * @test
     */
    public function it_can_display_village_buildings()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->assertSee('Buildings')
            ->assertSee('Town Hall')
            ->assertSee('Barracks')
            ->assertSee('Warehouse')
            ->assertSee('Farm');
    }

    /**
     * @test
     */
    public function it_can_display_village_resources()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->assertSee('Resources')
            ->assertSee('Wood')
            ->assertSee('Stone')
            ->assertSee('Iron')
            ->assertSee('Food');
    }

    /**
     * @test
     */
    public function it_can_upgrade_building()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('upgradeBuilding', 'town_hall')
            ->assertSee('Town hall upgrade started')
            ->assertEmitted('buildingUpgradeStarted');
    }

    /**
     * @test
     */
    public function it_can_construct_building()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('constructBuilding', 'barracks')
            ->assertSee('Barracks construction started')
            ->assertEmitted('buildingConstructionStarted');
    }

    /**
     * @test
     */
    public function it_can_demolish_building()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('demolishBuilding', 'barracks')
            ->assertSee('Barracks demolition started')
            ->assertEmitted('buildingDemolitionStarted');
    }

    /**
     * @test
     */
    public function it_can_collect_resources()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('collectResources')
            ->assertSee('Resources collected successfully')
            ->assertEmitted('resourcesCollected');
    }

    /**
     * @test
     */
    public function it_can_boost_production()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('boostProduction', 'wood', 0.5)
            ->assertSee('Wood production boosted by 50%')
            ->assertEmitted('productionBoosted');
    }

    /**
     * @test
     */
    public function it_can_configure_village_settings()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('villageSettings', [
                'auto_collect' => true,
                'auto_upgrade' => false,
                'auto_defend' => true,
            ])
            ->call('updateVillageSettings')
            ->assertSee('Village settings updated')
            ->assertEmitted('villageSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_building_priorities()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('buildingPriorities', [
                'town_hall' => 1,
                'barracks' => 2,
                'warehouse' => 3,
                'farm' => 4,
            ])
            ->call('updateBuildingPriorities')
            ->assertSee('Building priorities updated')
            ->assertEmitted('buildingPrioritiesUpdated');
    }

    /**
     * @test
     */
    public function it_can_view_village_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('viewVillageStatistics')
            ->assertSee('Village Statistics')
            ->assertSee('Total Buildings')
            ->assertSee('Production Rate')
            ->assertSee('Defense Rating');
    }

    /**
     * @test
     */
    public function it_can_view_village_history()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('viewVillageHistory')
            ->assertSee('Village History')
            ->assertSee('Construction History')
            ->assertSee('Upgrade History')
            ->assertSee('Attack History');
    }

    /**
     * @test
     */
    public function it_can_export_village_data()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('exportVillageData')
            ->assertEmitted('villageDataExported');
    }

    /**
     * @test
     */
    public function it_can_import_village_data()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('importVillageData', 'village_data.json')
            ->assertSee('Village data imported successfully')
            ->assertEmitted('villageDataImported');
    }

    /**
     * @test
     */
    public function it_can_configure_village_defense()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('defenseSettings', [
                'auto_defend' => true,
                'defense_priority' => 'walls',
                'max_defense_level' => 20,
            ])
            ->call('updateDefenseSettings')
            ->assertSee('Defense settings updated')
            ->assertEmitted('defenseSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_village_production()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('productionSettings', [
                'auto_collect' => true,
                'production_priority' => 'wood',
                'max_production_level' => 15,
            ])
            ->call('updateProductionSettings')
            ->assertSee('Production settings updated')
            ->assertEmitted('productionSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_village_storage()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('storageSettings', [
                'auto_upgrade_storage' => true,
                'storage_priority' => 'warehouse',
                'max_storage_level' => 25,
            ])
            ->call('updateStorageSettings')
            ->assertSee('Storage settings updated')
            ->assertEmitted('storageSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_village_automation()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('automationSettings', [
                'auto_collect' => true,
                'auto_upgrade' => false,
                'auto_defend' => true,
                'auto_construct' => false,
            ])
            ->call('updateAutomationSettings')
            ->assertSee('Automation settings updated')
            ->assertEmitted('automationSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_village_scheduling()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('scheduleSettings', [
                'collection_schedule' => 'hourly',
                'upgrade_schedule' => 'daily',
                'construction_schedule' => 'weekly',
            ])
            ->call('updateScheduleSettings')
            ->assertSee('Schedule settings updated')
            ->assertEmitted('scheduleSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_configure_village_alerts()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->set('alertSettings', [
                'low_resource_alert' => true,
                'full_storage_alert' => true,
                'attack_alert' => true,
            ])
            ->call('updateAlertSettings')
            ->assertSee('Alert settings updated')
            ->assertEmitted('alertSettingsUpdated');
    }

    /**
     * @test
     */
    public function it_can_handle_village_errors()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('handleVillageError', 'Village update failed')
            ->assertSee('Village Error: Village update failed')
            ->assertEmitted('villageErrorOccurred');
    }

    /**
     * @test
     */
    public function it_can_reset_village_manager()
    {
        Livewire::actingAs($this->user)
            ->test(VillageManager::class)
            ->call('resetVillageManager')
            ->assertSee('Village manager reset')
            ->assertEmitted('villageManagerReset');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(VillageManager::class)
            ->assertSee('Please login to access Village Manager');
    }
}

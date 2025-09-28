<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\Dashboard;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_render_dashboard_component()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertStatus(200);
        $component->assertSee('Dashboard');
    }

    /**
     * @test
     */
    public function it_loads_player_data_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('player.id', $player->id);
        $component->assertSet('world.id', $world->id);
    }

    /**
     * @test
     */
    public function it_loads_villages_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('villages', function ($villages) use ($village) {
            return $villages->contains('id', $village->id);
        });
    }

    /**
     * @test
     */
    public function it_loads_resources_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Create resources for the village
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1500,
        ]);
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 1200,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('resources.wood', 1500);
        $component->assertSet('resources.clay', 1200);
    }

    /**
     * @test
     */
    public function it_can_create_village()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillage');

        $this->assertDatabaseHas('villages', [
            'player_id' => $player->id,
            'world_id' => $world->id,
            'name' => 'Village 1',
            'is_capital' => true,
        ]);

        $component->assertSet('villages', function ($villages) {
            return $villages->count() === 1;
        });
    }

    /**
     * @test
     */
    public function it_creates_initial_resources_when_creating_village()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillage');

        $village = Village::where('player_id', $player->id)->first();

        $this->assertDatabaseHas('resources', [
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);
        $this->assertDatabaseHas('resources', [
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 1000,
        ]);
        $this->assertDatabaseHas('resources', [
            'village_id' => $village->id,
            'type' => 'iron',
            'amount' => 1000,
        ]);
        $this->assertDatabaseHas('resources', [
            'village_id' => $village->id,
            'type' => 'crop',
            'amount' => 1000,
        ]);
    }

    /**
     * @test
     */
    public function it_prevents_village_creation_for_non_premium_users_with_existing_village()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Create first village
        Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillage');

        $component->assertDispatched('fathom-track', name: 'village creation failed - premium required');
        $component->assertSessionHas('error', 'You need premium to create more villages!');
    }

    /**
     * @test
     */
    public function it_can_enter_village()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('enterVillage', $village->id);

        $component->assertDispatched('fathom-track', name: 'village entered', value: $village->id);
        $component->assertRedirect(route('game.village', $village->id));
    }

    /**
     * @test
     */
    public function it_initializes_player_real_time_on_mount()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameIntegrationService
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
        });

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertDispatched('dashboard-initialized');
    }

    /**
     * @test
     */
    public function it_handles_real_time_initialization_error()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameIntegrationService to throw an exception
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->andThrow(new \Exception('Test error'));
        });

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertDispatched('error', [
            'message' => 'Failed to initialize dashboard real-time features: Test error',
        ]);
    }

    /**
     * @test
     */
    public function it_can_create_village_with_integration()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameNotificationService
        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
        });

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillageWithIntegration');

        $this->assertDatabaseHas('villages', [
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $component->assertDispatched('village-created');
    }

    /**
     * @test
     */
    public function it_handles_village_creation_with_integration_error()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Mock GameNotificationService to throw an exception
        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->andThrow(new \Exception('Test error'));
        });

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillageWithIntegration');

        $component->assertDispatched('error', [
            'message' => 'Failed to create village: Test error',
        ]);
    }

    /**
     * @test
     */
    public function it_calculates_total_resources_from_all_villages()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $village1 = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);
        $village2 = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Create resources for village 1
        Resource::factory()->create([
            'village_id' => $village1->id,
            'type' => 'wood',
            'amount' => 1000,
        ]);
        Resource::factory()->create([
            'village_id' => $village1->id,
            'type' => 'clay',
            'amount' => 800,
        ]);

        // Create resources for village 2
        Resource::factory()->create([
            'village_id' => $village2->id,
            'type' => 'wood',
            'amount' => 500,
        ]);
        Resource::factory()->create([
            'village_id' => $village2->id,
            'type' => 'clay',
            'amount' => 300,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('resources.wood', 1500);  // 1000 + 500
        $component->assertSet('resources.clay', 1100);  // 800 + 300
    }

    /**
     * @test
     */
    public function it_handles_missing_resources_gracefully()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Don't create any resources

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('resources.wood', 0);
        $component->assertSet('resources.clay', 0);
        $component->assertSet('resources.iron', 0);
        $component->assertSet('resources.crop', 0);
    }

    /**
     * @test
     */
    public function it_handles_unknown_resource_types()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Create resource with unknown type
        Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'unknown_resource',
            'amount' => 1000,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        // Unknown resource types should be ignored
        $component->assertSet('resources.wood', 0);
        $component->assertSet('resources.clay', 0);
        $component->assertSet('resources.iron', 0);
        $component->assertSet('resources.crop', 0);
    }

    /**
     * @test
     */
    public function it_tracks_village_creation_with_fathom()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->call('createVillage');

        $component->assertDispatched('fathom-track', name: 'village created', value: 100);
    }

    /**
     * @test
     */
    public function it_handles_empty_villages_collection()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        // Don't create any villages

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertSet('villages', function ($villages) {
            return $villages->count() === 0;
        });
        $component->assertSet('resources.wood', 0);
        $component->assertSet('resources.clay', 0);
        $component->assertSet('resources.iron', 0);
        $component->assertSet('resources.crop', 0);
    }

    /**
     * @test
     */
    public function it_renders_correct_view()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id,
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component->assertViewIs('livewire.game.dashboard');
    }
}

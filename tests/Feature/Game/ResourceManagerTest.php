<?php

namespace Tests\Feature\Game;

use App\Livewire\Game\ResourceManager;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_manager_loads_resources()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->assertSee('1,000');
    }

    public function test_resource_manager_updates_resources()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->call('updateResources')
            ->assertDispatched('resources-updated');
    }

    public function test_resource_manager_spends_resources()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->call('spendResources', ['wood' => 100])
            ->assertDispatched('resources-updated');
    }

    public function test_resource_manager_adds_resources()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->call('addResources', ['wood' => 100])
            ->assertDispatched('resources-updated');
    }

    public function test_resource_manager_toggles_auto_update()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->assertSet('autoUpdate', true)
            ->call('toggleAutoUpdate')
            ->assertSet('autoUpdate', false);
    }

    public function test_resource_manager_sets_update_interval()
    {
        $user = User::factory()->create();
        $world = World::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $world->id
        ]);

        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id
        ]);

        $this->actingAs($user);

        Livewire::test(ResourceManager::class, ['villageId' => $village->id])
            ->call('setUpdateInterval', 10)
            ->assertSet('updateInterval', 10);
    }
}

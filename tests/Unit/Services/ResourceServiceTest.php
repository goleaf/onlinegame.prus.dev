<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Services\ResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ResourceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_resource()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
        ];

        $service = new ResourceService();
        $result = $service->createResource($player, $village, $data);

        $this->assertInstanceOf(Resource::class, $result);
        $this->assertEquals($data['village_id'], $result->village_id);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['amount'], $result->amount);
    }

    /**
     * @test
     */
    public function it_can_update_resource()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'amount' => 1000,
        ]);

        $service = new ResourceService();
        $result = $service->updateResource($player, $resource, 500);

        $this->assertTrue($result);
        $this->assertEquals(500, $resource->amount);
    }

    /**
     * @test
     */
    public function it_can_add_resource()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'amount' => 1000,
        ]);

        $service = new ResourceService();
        $result = $service->addResource($player, $resource, 500);

        $this->assertTrue($result);
        $this->assertEquals(1500, $resource->amount);
    }

    /**
     * @test
     */
    public function it_can_subtract_resource()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'amount' => 1000,
        ]);

        $service = new ResourceService();
        $result = $service->subtractResource($player, $resource, 500);

        $this->assertTrue($result);
        $this->assertEquals(500, $resource->amount);
    }

    /**
     * @test
     */
    public function it_can_get_village_resources()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id]),
            Resource::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getVillageResources($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_type()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'type' => 'wood']),
            Resource::factory()->create(['village_id' => $village->id, 'type' => 'clay']),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByType($village, 'wood');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_amount()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'amount' => 1000]),
            Resource::factory()->create(['village_id' => $village->id, 'amount' => 2000]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByAmount($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_status()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'status' => 'active']),
            Resource::factory()->create(['village_id' => $village->id, 'status' => 'inactive']),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByStatus($village, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_creation_date()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'created_at' => now()]),
            Resource::factory()->create(['village_id' => $village->id, 'created_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByCreationDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_update_date()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'updated_at' => now()]),
            Resource::factory()->create(['village_id' => $village->id, 'updated_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByUpdateDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_combined_filters()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'type' => 'wood', 'amount' => 1000]),
            Resource::factory()->create(['village_id' => $village->id, 'type' => 'clay', 'amount' => 2000]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByCombinedFilters($village, [
            'type' => 'wood',
            'amount' => 1000,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_search()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'name' => 'Test Resource']),
            Resource::factory()->create(['village_id' => $village->id, 'name' => 'Another Resource']),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceBySearch($village, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_sort()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id, 'amount' => 1000]),
            Resource::factory()->create(['village_id' => $village->id, 'amount' => 2000]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceBySort($village, 'amount', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_by_pagination()
    {
        $village = Village::factory()->create();
        $resources = collect([
            Resource::factory()->create(['village_id' => $village->id]),
            Resource::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('resources')->andReturn($resources);

        $service = new ResourceService();
        $result = $service->getResourceByPagination($village, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_statistics()
    {
        $service = new ResourceService();
        $result = $service->getResourceStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_resources', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_amount', $result);
    }

    /**
     * @test
     */
    public function it_can_get_resource_leaderboard()
    {
        $service = new ResourceService();
        $result = $service->getResourceLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

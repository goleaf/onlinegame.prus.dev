<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Resource;
use App\Models\Game\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    protected Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = new Resource();
    }

    /**
     * @test
     */
    public function it_can_create_resource()
    {
        $village = Village::factory()->create();

        $resource = Resource::create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
            'last_updated' => now(),
        ]);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertEquals($village->id, $resource->village_id);
        $this->assertEquals('wood', $resource->type);
        $this->assertEquals(1000, $resource->amount);
        $this->assertEquals(10.5, $resource->production_rate);
        $this->assertEquals(5000, $resource->storage_capacity);
        $this->assertEquals(3, $resource->level);
    }

    /**
     * @test
     */
    public function it_casts_last_updated_to_datetime()
    {
        $now = now();
        $resource = Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
            'last_updated' => $now,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $resource->last_updated);
    }

    /**
     * @test
     */
    public function it_belongs_to_village()
    {
        $village = Village::factory()->create();
        $resource = Resource::create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        $this->assertInstanceOf(Village::class, $resource->village);
        $this->assertEquals($village->id, $resource->village->id);
    }

    /**
     * @test
     */
    public function it_has_by_village_scope()
    {
        $village1 = Village::factory()->create();
        $village2 = Village::factory()->create();

        Resource::create([
            'village_id' => $village1->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => $village2->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $village1Resources = Resource::byVillage($village1->id)->get();
        $this->assertCount(1, $village1Resources);
        $this->assertEquals('wood', $village1Resources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_type_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $woodResources = Resource::byType('wood')->get();
        $this->assertCount(1, $woodResources);
        $this->assertEquals('wood', $woodResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_amount_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $highAmountResources = Resource::byAmount(800)->get();
        $this->assertCount(1, $highAmountResources);
        $this->assertEquals('wood', $highAmountResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_production_rate_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $highProductionResources = Resource::byProductionRate(8.0)->get();
        $this->assertCount(1, $highProductionResources);
        $this->assertEquals('wood', $highProductionResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_capacity_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $highCapacityResources = Resource::byCapacity(4000)->get();
        $this->assertCount(1, $highCapacityResources);
        $this->assertEquals('wood', $highCapacityResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_level_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 5,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $highLevelResources = Resource::byLevel(4)->get();
        $this->assertCount(1, $highLevelResources);
        $this->assertEquals('wood', $highLevelResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_top_production_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $topProductionResources = Resource::topProduction(1)->get();
        $this->assertCount(1, $topProductionResources);
        $this->assertEquals('wood', $topProductionResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_top_amount_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $topAmountResources = Resource::topAmount(1)->get();
        $this->assertCount(1, $topAmountResources);
        $this->assertEquals('wood', $topAmountResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
            'last_updated' => now()->subDays(3),
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
            'last_updated' => now()->subDays(10),
        ]);

        $recentResources = Resource::recent(7)->get();
        $this->assertCount(1, $recentResources);
        $this->assertEquals('wood', $recentResources->first()->type);
    }

    /**
     * @test
     */
    public function it_has_search_scope()
    {
        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => Village::factory()->create()->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $searchResults = Resource::search('wood')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('wood', $searchResults->first()->type);
    }

    /**
     * @test
     */
    public function it_has_with_village_info_scope()
    {
        $village = Village::factory()->create();
        $resource = Resource::create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        $resourceWithInfo = Resource::withVillageInfo()->first();

        $this->assertTrue($resourceWithInfo->relationLoaded('village'));
    }

    /**
     * @test
     */
    public function it_can_get_cached_resources()
    {
        $village = Village::factory()->create();
        $resource = Resource::create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        $cachedResources = Resource::getCachedResources($village->id);

        $this->assertCount(1, $cachedResources);
        $this->assertEquals('wood', $cachedResources->first()->type);
    }

    /**
     * @test
     */
    public function it_can_get_cached_resources_with_filters()
    {
        $village = Village::factory()->create();
        Resource::create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10.5,
            'storage_capacity' => 5000,
            'level' => 3,
        ]);

        Resource::create([
            'village_id' => $village->id,
            'type' => 'clay',
            'amount' => 500,
            'production_rate' => 5.5,
            'storage_capacity' => 3000,
            'level' => 2,
        ]);

        $woodResources = Resource::getCachedResources($village->id, ['type' => 'wood']);

        $this->assertCount(1, $woodResources);
        $this->assertEquals('wood', $woodResources->first()->type);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'village_id' => Village::factory()->create()->id,
            'type' => 'iron',
            'amount' => 2000,
            'production_rate' => 15.5,
            'storage_capacity' => 8000,
            'level' => 5,
            'last_updated' => now(),
        ];

        $resource = Resource::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $resource->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_auditing_trait()
    {
        $this->assertTrue(method_exists($this->resource, 'audits'));
    }

    /**
     * @test
     */
    public function it_has_notable_trait()
    {
        $this->assertTrue(method_exists($this->resource, 'notables'));
    }
}

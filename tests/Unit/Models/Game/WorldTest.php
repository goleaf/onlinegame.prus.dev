<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\PlayerStatistic;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldTest extends TestCase
{
    use RefreshDatabase;

    private World $world;

    protected function setUp(): void
    {
        parent::setUp();

        $this->world = World::factory()->create();
    }

    /**
     * @test
     */
    public function it_has_many_players()
    {
        $player1 = Player::factory()->create(['world_id' => $this->world->id]);
        $player2 = Player::factory()->create(['world_id' => $this->world->id]);

        $players = $this->world->players;
        $this->assertCount(2, $players);
        $this->assertTrue($players->contains($player1));
        $this->assertTrue($players->contains($player2));
    }

    /**
     * @test
     */
    public function it_has_many_villages()
    {
        $village1 = Village::factory()->create(['world_id' => $this->world->id]);
        $village2 = Village::factory()->create(['world_id' => $this->world->id]);

        $villages = $this->world->villages;
        $this->assertCount(2, $villages);
        $this->assertTrue($villages->contains($village1));
        $this->assertTrue($villages->contains($village2));
    }

    /**
     * @test
     */
    public function it_has_many_alliances()
    {
        $alliance1 = Alliance::factory()->create(['world_id' => $this->world->id]);
        $alliance2 = Alliance::factory()->create(['world_id' => $this->world->id]);

        $alliances = $this->world->alliances;
        $this->assertCount(2, $alliances);
        $this->assertTrue($alliances->contains($alliance1));
        $this->assertTrue($alliances->contains($alliance2));
    }

    /**
     * @test
     */
    public function it_has_many_player_statistics()
    {
        $statistic1 = PlayerStatistic::factory()->create(['world_id' => $this->world->id]);
        $statistic2 = PlayerStatistic::factory()->create(['world_id' => $this->world->id]);

        $statistics = $this->world->playerStatistics;
        $this->assertCount(2, $statistics);
        $this->assertTrue($statistics->contains($statistic1));
        $this->assertTrue($statistics->contains($statistic2));
    }

    /**
     * @test
     */
    public function it_casts_boolean_attributes()
    {
        $world = World::factory()->create([
            'is_active' => 1,
            'has_plus' => 1,
            'has_artifacts' => 0,
        ]);

        $this->assertTrue($world->is_active);
        $this->assertIsBool($world->is_active);
        $this->assertTrue($world->has_plus);
        $this->assertIsBool($world->has_plus);
        $this->assertFalse($world->has_artifacts);
        $this->assertIsBool($world->has_artifacts);
    }

    /**
     * @test
     */
    public function it_casts_datetime_attributes()
    {
        $world = World::factory()->create([
            'start_date' => '2023-01-01 10:00:00',
            'end_date' => '2023-12-31 23:59:59',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $world->start_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $world->end_date);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'is_active',
            'reference_number',
            'max_players',
            'map_size',
            'speed',
            'has_plus',
            'has_artifacts',
            'start_date',
            'end_date',
        ];

        $this->assertEquals($fillable, $this->world->getFillable());
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $world = World::factory()->create();
        $this->assertNotNull($world->reference_number);
        $this->assertStringStartsWith('WLD-', $world->reference_number);
    }

    /**
     * @test
     */
    public function it_has_reference_configuration()
    {
        $this->assertEquals('reference_number', $this->world->getReferenceColumn());
        $this->assertEquals('template', $this->world->getReferenceStrategy());
        $this->assertEquals('WLD', $this->world->getReferencePrefix());
    }

    /**
     * @test
     */
    public function it_has_reference_template()
    {
        $template = $this->world->getReferenceTemplate();
        $this->assertIsArray($template);
        $this->assertArrayHasKey('format', $template);
        $this->assertArrayHasKey('sequence_length', $template);
        $this->assertEquals('WLD-{YEAR}{MONTH}{SEQ}', $template['format']);
        $this->assertEquals(4, $template['sequence_length']);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_factory()
    {
        $world = World::factory()->create();
        $this->assertInstanceOf(World::class, $world);
        $this->assertDatabaseHas('worlds', ['id' => $world->id]);
    }

    /**
     * @test
     */
    public function it_can_be_updated()
    {
        $this->world->update(['name' => 'Updated World']);
        $this->assertEquals('Updated World', $this->world->fresh()->name);
    }

    /**
     * @test
     */
    public function it_can_be_deleted()
    {
        $worldId = $this->world->id;
        $this->world->delete();
        $this->assertDatabaseMissing('worlds', ['id' => $worldId]);
    }

    /**
     * @test
     */
    public function it_has_auditable_trait()
    {
        $this->assertTrue(in_array('OwenIt\Auditing\Auditable', class_uses($this->world)));
    }

    /**
     * @test
     */
    public function it_has_notable_trait()
    {
        $this->assertTrue(in_array('MohamedSaid\Notable\Traits\HasNotables', class_uses($this->world)));
    }

    /**
     * @test
     */
    public function it_has_referenceable_trait()
    {
        $this->assertTrue(in_array('MohamedSaid\Referenceable\Traits\HasReference', class_uses($this->world)));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(in_array('Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy', class_uses($this->world)));
    }

    /**
     * @test
     */
    public function it_implements_auditable_interface()
    {
        $this->assertInstanceOf('OwenIt\Auditing\Contracts\Auditable', $this->world);
    }

    /**
     * @test
     */
    public function it_handles_null_dates()
    {
        $world = World::factory()->create([
            'start_date' => null,
            'end_date' => null,
        ]);

        $this->assertNull($world->start_date);
        $this->assertNull($world->end_date);
    }

    /**
     * @test
     */
    public function it_handles_null_description()
    {
        $world = World::factory()->create(['description' => null]);
        $this->assertNull($world->description);
    }

    /**
     * @test
     */
    public function it_handles_zero_max_players()
    {
        $world = World::factory()->create(['max_players' => 0]);
        $this->assertEquals(0, $world->max_players);
    }

    /**
     * @test
     */
    public function it_handles_large_max_players()
    {
        $world = World::factory()->create(['max_players' => 999999]);
        $this->assertEquals(999999, $world->max_players);
    }

    /**
     * @test
     */
    public function it_handles_zero_map_size()
    {
        $world = World::factory()->create(['map_size' => 0]);
        $this->assertEquals(0, $world->map_size);
    }

    /**
     * @test
     */
    public function it_handles_large_map_size()
    {
        $world = World::factory()->create(['map_size' => 999999]);
        $this->assertEquals(999999, $world->map_size);
    }

    /**
     * @test
     */
    public function it_handles_decimal_speed()
    {
        $world = World::factory()->create(['speed' => 1.5]);
        $this->assertEquals(1.5, $world->speed);
    }

    /**
     * @test
     */
    public function it_handles_zero_speed()
    {
        $world = World::factory()->create(['speed' => 0]);
        $this->assertEquals(0, $world->speed);
    }

    /**
     * @test
     */
    public function it_handles_high_speed()
    {
        $world = World::factory()->create(['speed' => 100]);
        $this->assertEquals(100, $world->speed);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_name()
    {
        $world = World::factory()->create(['name' => '世界 1']);
        $this->assertEquals('世界 1', $world->name);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_description()
    {
        $world = World::factory()->create(['description' => '这是一个测试世界']);
        $this->assertEquals('这是一个测试世界', $world->description);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_name()
    {
        $world = World::factory()->create(['name' => 'World "Test" & Co.']);
        $this->assertEquals('World "Test" & Co.', $world->name);
    }

    /**
     * @test
     */
    public function it_handles_long_names()
    {
        $longName = str_repeat('W', 255);
        $world = World::factory()->create(['name' => $longName]);
        $this->assertEquals($longName, $world->name);
    }

    /**
     * @test
     */
    public function it_handles_long_descriptions()
    {
        $longDescription = str_repeat('D', 1000);
        $world = World::factory()->create(['description' => $longDescription]);
        $this->assertEquals($longDescription, $world->description);
    }

    /**
     * @test
     */
    public function it_handles_future_start_date()
    {
        $futureDate = now()->addYear();
        $world = World::factory()->create(['start_date' => $futureDate]);
        $this->assertEquals($futureDate->format('Y-m-d H:i:s'), $world->start_date->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_handles_past_end_date()
    {
        $pastDate = now()->subYear();
        $world = World::factory()->create(['end_date' => $pastDate]);
        $this->assertEquals($pastDate->format('Y-m-d H:i:s'), $world->end_date->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function it_handles_same_start_and_end_date()
    {
        $date = now();
        $world = World::factory()->create([
            'start_date' => $date,
            'end_date' => $date,
        ]);

        $this->assertEquals($date->format('Y-m-d H:i:s'), $world->start_date->format('Y-m-d H:i:s'));
        $this->assertEquals($date->format('Y-m-d H:i:s'), $world->end_date->format('Y-m-d H:i:s'));
    }
}

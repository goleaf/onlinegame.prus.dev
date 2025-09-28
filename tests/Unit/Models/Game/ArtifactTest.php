<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_artifact()
    {
        $artifact = Artifact::create([
            'name' => 'Sword of Power',
            'description' => 'A legendary sword with magical properties',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'level' => 50,
            'stats' => ['attack' => 100, 'defense' => 50],
            'effects' => ['fire_damage', 'critical_hit'],
            'is_tradeable' => true,
            'is_sellable' => false,
            'value' => 1000,
            'weight' => 5.5,
            'durability' => 100,
            'max_durability' => 100,
            'metadata' => ['source' => 'quest', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('artifacts', [
            'name' => 'Sword of Power',
            'type' => 'weapon',
            'rarity' => 'legendary',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $artifact = new Artifact([
            'name' => 'Shield of Protection',
            'description' => 'A sturdy shield that blocks attacks',
            'type' => 'armor',
            'rarity' => 'rare',
            'level' => 25,
            'stats' => ['defense' => 75, 'block' => 25],
            'effects' => ['damage_reduction', 'block_chance'],
            'is_tradeable' => false,
            'is_sellable' => true,
            'value' => 500,
            'weight' => 8.0,
            'durability' => 80,
            'max_durability' => 100,
            'metadata' => ['source' => 'crafting', 'version' => '1.1'],
        ]);

        $this->assertEquals('Shield of Protection', $artifact->name);
        $this->assertEquals('armor', $artifact->type);
        $this->assertEquals('rare', $artifact->rarity);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $artifact = Artifact::create([
            'name' => 'Test Artifact',
            'description' => 'A test artifact',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => true,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $this->assertTrue($artifact->is_tradeable);
        $this->assertFalse($artifact->is_sellable);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $artifact = Artifact::create([
            'name' => 'Test Artifact',
            'description' => 'A test artifact',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => ['attack' => 10, 'defense' => 5],
            'effects' => ['fire_damage', 'ice_resistance'],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($artifact->stats);
        $this->assertIsArray($artifact->effects);
        $this->assertIsArray($artifact->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_artifacts_by_type()
    {
        Artifact::create([
            'name' => 'Sword',
            'description' => 'A sword',
            'type' => 'weapon',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        Artifact::create([
            'name' => 'Shield',
            'description' => 'A shield',
            'type' => 'armor',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $weapons = Artifact::byType('weapon')->get();
        $this->assertCount(1, $weapons);
        $this->assertEquals('weapon', $weapons->first()->type);
    }

    /**
     * @test
     */
    public function it_can_scope_artifacts_by_rarity()
    {
        Artifact::create([
            'name' => 'Common Item',
            'description' => 'A common item',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        Artifact::create([
            'name' => 'Rare Item',
            'description' => 'A rare item',
            'type' => 'test',
            'rarity' => 'rare',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $rareItems = Artifact::byRarity('rare')->get();
        $this->assertCount(1, $rareItems);
        $this->assertEquals('rare', $rareItems->first()->rarity);
    }

    /**
     * @test
     */
    public function it_can_scope_artifacts_by_level()
    {
        Artifact::create([
            'name' => 'Low Level Item',
            'description' => 'A low level item',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        Artifact::create([
            'name' => 'High Level Item',
            'description' => 'A high level item',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 50,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $highLevelItems = Artifact::byLevel(50)->get();
        $this->assertCount(1, $highLevelItems);
        $this->assertEquals(50, $highLevelItems->first()->level);
    }

    /**
     * @test
     */
    public function it_can_scope_tradeable_artifacts()
    {
        Artifact::create([
            'name' => 'Tradeable Item',
            'description' => 'A tradeable item',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => true,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        Artifact::create([
            'name' => 'Non-Tradeable Item',
            'description' => 'A non-tradeable item',
            'type' => 'test',
            'rarity' => 'common',
            'level' => 1,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 100,
            'weight' => 1.0,
            'durability' => 50,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $tradeableItems = Artifact::tradeable()->get();
        $this->assertCount(1, $tradeableItems);
        $this->assertTrue($tradeableItems->first()->is_tradeable);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_summary()
    {
        $artifact = Artifact::create([
            'name' => 'Sword of Power',
            'description' => 'A legendary sword with magical properties',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'level' => 50,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 1000,
            'weight' => 5.5,
            'durability' => 100,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $summary = $artifact->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('Sword of Power', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_details()
    {
        $artifact = Artifact::create([
            'name' => 'Sword of Power',
            'description' => 'A legendary sword with magical properties',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'level' => 50,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 1000,
            'weight' => 5.5,
            'durability' => 100,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $details = $artifact->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('name', $details);
        $this->assertArrayHasKey('description', $details);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_statistics()
    {
        $artifact = Artifact::create([
            'name' => 'Sword of Power',
            'description' => 'A legendary sword with magical properties',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'level' => 50,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 1000,
            'weight' => 5.5,
            'durability' => 100,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $stats = $artifact->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('level', $stats);
        $this->assertArrayHasKey('value', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_timeline()
    {
        $artifact = Artifact::create([
            'name' => 'Sword of Power',
            'description' => 'A legendary sword with magical properties',
            'type' => 'weapon',
            'rarity' => 'legendary',
            'level' => 50,
            'stats' => [],
            'effects' => [],
            'is_tradeable' => false,
            'is_sellable' => false,
            'value' => 1000,
            'weight' => 5.5,
            'durability' => 100,
            'max_durability' => 100,
            'metadata' => [],
        ]);

        $timeline = $artifact->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('created_at', $timeline);
        $this->assertArrayHasKey('updated_at', $timeline);
    }
}

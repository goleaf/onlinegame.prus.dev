<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceWar;
use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleTest extends TestCase
{
    use RefreshDatabase;

    private Battle $battle;

    private Player $attacker;

    private Player $defender;

    private Village $village;

    private World $world;

    protected function setUp(): void
    {
        parent::setUp();

        $this->world = World::factory()->create();
        $this->attacker = Player::factory()->create(['world_id' => $this->world->id]);
        $this->defender = Player::factory()->create(['world_id' => $this->world->id]);
        $this->village = Village::factory()->create([
            'player_id' => $this->defender->id,
            'world_id' => $this->world->id,
        ]);

        $this->battle = Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
        ]);
    }

    /**
     * @test
     */
    public function it_belongs_to_attacker()
    {
        $this->assertInstanceOf(Player::class, $this->battle->attacker);
        $this->assertEquals($this->attacker->id, $this->battle->attacker->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_defender()
    {
        $this->assertInstanceOf(Player::class, $this->battle->defender);
        $this->assertEquals($this->defender->id, $this->battle->defender->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_village()
    {
        $this->assertInstanceOf(Village::class, $this->battle->village);
        $this->assertEquals($this->village->id, $this->battle->village->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_war()
    {
        $alliance1 = Alliance::factory()->create(['world_id' => $this->world->id]);
        $alliance2 = Alliance::factory()->create(['world_id' => $this->world->id]);
        $war = AllianceWar::factory()->create([
            'attacker_alliance_id' => $alliance1->id,
            'defender_alliance_id' => $alliance2->id,
        ]);

        $battle = Battle::factory()->create(['war_id' => $war->id]);

        $this->assertInstanceOf(AllianceWar::class, $battle->war);
        $this->assertEquals($war->id, $battle->war->id);
    }

    /**
     * @test
     */
    public function it_can_scope_by_player()
    {
        $otherPlayer = Player::factory()->create();
        $otherBattle = Battle::factory()->create([
            'attacker_id' => $otherPlayer->id,
            'defender_id' => $otherPlayer->id,
        ]);

        $battles = Battle::byPlayer($this->attacker->id)->get();
        $this->assertTrue($battles->contains($this->battle));
        $this->assertFalse($battles->contains($otherBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_by_village()
    {
        $otherVillage = Village::factory()->create();
        $otherBattle = Battle::factory()->create(['village_id' => $otherVillage->id]);

        $battles = Battle::byVillage($this->village->id)->get();
        $this->assertTrue($battles->contains($this->battle));
        $this->assertFalse($battles->contains($otherBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_by_result()
    {
        $victoryBattle = Battle::factory()->create(['result' => 'victory']);
        $defeatBattle = Battle::factory()->create(['result' => 'defeat']);

        $victories = Battle::byResult('victory')->get();
        $this->assertTrue($victories->contains($victoryBattle));
        $this->assertFalse($victories->contains($defeatBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_victories()
    {
        $victoryBattle = Battle::factory()->create(['result' => 'victory']);
        $defeatBattle = Battle::factory()->create(['result' => 'defeat']);

        $victories = Battle::victories()->get();
        $this->assertTrue($victories->contains($victoryBattle));
        $this->assertFalse($victories->contains($defeatBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_defeats()
    {
        $victoryBattle = Battle::factory()->create(['result' => 'victory']);
        $defeatBattle = Battle::factory()->create(['result' => 'defeat']);

        $defeats = Battle::defeats()->get();
        $this->assertTrue($defeats->contains($defeatBattle));
        $this->assertFalse($defeats->contains($victoryBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_recent()
    {
        $recentBattle = Battle::factory()->create(['occurred_at' => now()->subDays(3)]);
        $oldBattle = Battle::factory()->create(['occurred_at' => now()->subDays(10)]);

        $battles = Battle::recent(7)->get();
        $this->assertTrue($battles->contains($recentBattle));
        $this->assertFalse($battles->contains($oldBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_today()
    {
        $todayBattle = Battle::factory()->create(['occurred_at' => now()]);
        $yesterdayBattle = Battle::factory()->create(['occurred_at' => now()->subDay()]);

        $battles = Battle::today()->get();
        $this->assertTrue($battles->contains($todayBattle));
        $this->assertFalse($battles->contains($yesterdayBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_this_week()
    {
        $thisWeekBattle = Battle::factory()->create(['occurred_at' => now()->startOfWeek()->addDay()]);
        $lastWeekBattle = Battle::factory()->create(['occurred_at' => now()->subWeek()]);

        $battles = Battle::thisWeek()->get();
        $this->assertTrue($battles->contains($thisWeekBattle));
        $this->assertFalse($battles->contains($lastWeekBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_this_month()
    {
        $thisMonthBattle = Battle::factory()->create(['occurred_at' => now()->startOfMonth()->addDay()]);
        $lastMonthBattle = Battle::factory()->create(['occurred_at' => now()->subMonth()]);

        $battles = Battle::thisMonth()->get();
        $this->assertTrue($battles->contains($thisMonthBattle));
        $this->assertFalse($battles->contains($lastMonthBattle));
    }

    /**
     * @test
     */
    public function it_can_scope_search()
    {
        $player1 = Player::factory()->create(['name' => 'Test Player']);
        $player2 = Player::factory()->create(['name' => 'Other Player']);
        $village1 = Village::factory()->create(['name' => 'Test Village']);

        $battle1 = Battle::factory()->create(['attacker_id' => $player1->id]);
        $battle2 = Battle::factory()->create(['defender_id' => $player1->id]);
        $battle3 = Battle::factory()->create(['village_id' => $village1->id]);
        $battle4 = Battle::factory()->create(['attacker_id' => $player2->id]);

        $battles = Battle::search('Test')->get();
        $this->assertTrue($battles->contains($battle1));
        $this->assertTrue($battles->contains($battle2));
        $this->assertTrue($battles->contains($battle3));
        $this->assertFalse($battles->contains($battle4));
    }

    /**
     * @test
     */
    public function it_can_scope_with_player_info()
    {
        $battle = Battle::withPlayerInfo()->find($this->battle->id);
        $this->assertTrue($battle->relationLoaded('attacker'));
        $this->assertTrue($battle->relationLoaded('defender'));
        $this->assertTrue($battle->relationLoaded('village'));
    }

    /**
     * @test
     */
    public function it_can_get_battle_statistics()
    {
        Battle::factory()->count(5)->create([
            'attacker_id' => $this->attacker->id,
            'result' => 'victory',
            'occurred_at' => now()->subDays(5),
            'loot' => ['wood' => 100, 'clay' => 100, 'iron' => 100, 'crop' => 100],
        ]);

        Battle::factory()->count(3)->create([
            'attacker_id' => $this->attacker->id,
            'result' => 'defeat',
            'occurred_at' => now()->subDays(3),
            'loot' => [],
        ]);

        $stats = Battle::getBattleStatistics($this->attacker->id, 30);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_battles', $stats);
        $this->assertArrayHasKey('victories', $stats);
        $this->assertArrayHasKey('defeats', $stats);
        $this->assertArrayHasKey('win_rate', $stats);
        $this->assertArrayHasKey('avg_loot', $stats);
        $this->assertArrayHasKey('battles_by_day', $stats);

        $this->assertEquals(9, $stats['total_battles']);  // 8 created + 1 in setUp
        $this->assertEquals(5, $stats['victories']);
        $this->assertEquals(3, $stats['defeats']);
        $this->assertEquals(55.56, $stats['win_rate']);
    }

    /**
     * @test
     */
    public function it_can_get_battle_statistics_without_player()
    {
        Battle::factory()->count(3)->create(['result' => 'victory']);
        Battle::factory()->count(2)->create(['result' => 'defeat']);

        $stats = Battle::getBattleStatistics(null, 30);

        $this->assertIsArray($stats);
        $this->assertEquals(6, $stats['total_battles']);  // 5 created + 1 in setUp
        $this->assertEquals(3, $stats['victories']);
        $this->assertEquals(2, $stats['defeats']);
    }

    /**
     * @test
     */
    public function it_casts_array_attributes()
    {
        $battle = Battle::factory()->create([
            'attacker_troops' => ['infantry' => 10, 'cavalry' => 5],
            'defender_troops' => ['infantry' => 8, 'archers' => 3],
            'attacker_losses' => ['infantry' => 2],
            'defender_losses' => ['infantry' => 1],
            'loot' => ['wood' => 100, 'clay' => 50],
        ]);

        $this->assertIsArray($battle->attacker_troops);
        $this->assertIsArray($battle->defender_troops);
        $this->assertIsArray($battle->attacker_losses);
        $this->assertIsArray($battle->defender_losses);
        $this->assertIsArray($battle->loot);

        $this->assertEquals(['infantry' => 10, 'cavalry' => 5], $battle->attacker_troops);
        $this->assertEquals(['wood' => 100, 'clay' => 50], $battle->loot);
    }

    /**
     * @test
     */
    public function it_casts_occurred_at_to_datetime()
    {
        $battle = Battle::factory()->create(['occurred_at' => '2023-01-01 10:00:00']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $battle->occurred_at);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'attacker_id',
            'defender_id',
            'village_id',
            'attacker_troops',
            'defender_troops',
            'attacker_losses',
            'defender_losses',
            'loot',
            'war_id',
            'result',
            'occurred_at',
            'reference_number',
        ];

        $this->assertEquals($fillable, $this->battle->getFillable());
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $battle = Battle::factory()->create();
        $this->assertNotNull($battle->reference_number);
        $this->assertStringStartsWith('BTL-', $battle->reference_number);
    }

    /**
     * @test
     */
    public function it_has_reference_configuration()
    {
        $this->assertEquals('reference_number', $this->battle->getReferenceColumn());
        $this->assertEquals('template', $this->battle->getReferenceStrategy());
        $this->assertEquals('BTL', $this->battle->getReferencePrefix());
    }

    /**
     * @test
     */
    public function it_has_reference_template()
    {
        $template = $this->battle->getReferenceTemplate();
        $this->assertIsArray($template);
        $this->assertArrayHasKey('format', $template);
        $this->assertArrayHasKey('sequence_length', $template);
        $this->assertEquals('BTL-{YEAR}{MONTH}{SEQ}', $template['format']);
        $this->assertEquals(4, $template['sequence_length']);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_factory()
    {
        $battle = Battle::factory()->create();
        $this->assertInstanceOf(Battle::class, $battle);
        $this->assertDatabaseHas('battles', ['id' => $battle->id]);
    }

    /**
     * @test
     */
    public function it_can_be_updated()
    {
        $this->battle->update(['result' => 'victory']);
        $this->assertEquals('victory', $this->battle->fresh()->result);
    }

    /**
     * @test
     */
    public function it_can_be_deleted()
    {
        $battleId = $this->battle->id;
        $this->battle->delete();
        $this->assertDatabaseMissing('battles', ['id' => $battleId]);
    }

    /**
     * @test
     */
    public function it_has_auditable_trait()
    {
        $this->assertTrue(in_array('OwenIt\Auditing\Auditable', class_uses($this->battle)));
    }

    /**
     * @test
     */
    public function it_has_referenceable_trait()
    {
        $this->assertTrue(in_array('MohamedSaid\Referenceable\Traits\HasReference', class_uses($this->battle)));
    }

    /**
     * @test
     */
    public function it_has_filterable_trait()
    {
        $this->assertTrue(in_array('IndexZer0\EloquentFiltering\Filter\Traits\Filterable', class_uses($this->battle)));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(in_array('Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy', class_uses($this->battle)));
    }

    /**
     * @test
     */
    public function it_implements_auditable_interface()
    {
        $this->assertInstanceOf('OwenIt\Auditing\Contracts\Auditable', $this->battle);
    }

    /**
     * @test
     */
    public function it_implements_filterable_interface()
    {
        $this->assertInstanceOf('IndexZer0\EloquentFiltering\Contracts\IsFilterable', $this->battle);
    }

    /**
     * @test
     */
    public function it_has_allowed_filters()
    {
        $filters = $this->battle->allowedFilters();
        $this->assertNotNull($filters);
    }

    /**
     * @test
     */
    public function it_handles_null_war_id()
    {
        $battle = Battle::factory()->create(['war_id' => null]);
        $this->assertNull($battle->war);
    }

    /**
     * @test
     */
    public function it_handles_null_array_attributes()
    {
        $battle = Battle::factory()->create([
            'attacker_troops' => null,
            'defender_troops' => null,
            'attacker_losses' => null,
            'defender_losses' => null,
            'loot' => null,
        ]);

        $this->assertNull($battle->attacker_troops);
        $this->assertNull($battle->defender_troops);
        $this->assertNull($battle->attacker_losses);
        $this->assertNull($battle->defender_losses);
        $this->assertNull($battle->loot);
    }

    /**
     * @test
     */
    public function it_handles_empty_search_term()
    {
        $battles = Battle::search('')->get();
        $this->assertCount(Battle::count(), $battles);
    }

    /**
     * @test
     */
    public function it_handles_null_search_term()
    {
        $battles = Battle::search(null)->get();
        $this->assertCount(Battle::count(), $battles);
    }

    /**
     * @test
     */
    public function it_handles_empty_loot_in_statistics()
    {
        Battle::factory()->count(3)->create([
            'attacker_id' => $this->attacker->id,
            'loot' => null,
        ]);

        $stats = Battle::getBattleStatistics($this->attacker->id, 30);
        $this->assertIsNumeric($stats['avg_loot']);
    }

    /**
     * @test
     */
    public function it_handles_zero_battles_in_statistics()
    {
        // Delete the battle created in setUp
        $this->battle->delete();

        $stats = Battle::getBattleStatistics(999, 30);  // Non-existent player

        $this->assertEquals(0, $stats['total_battles']);
        $this->assertEquals(0, $stats['victories']);
        $this->assertEquals(0, $stats['defeats']);
        $this->assertEquals(0, $stats['win_rate']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_result()
    {
        $battle = Battle::factory()->create(['result' => '胜利']);
        $this->assertEquals('胜利', $battle->result);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_result()
    {
        $battle = Battle::factory()->create(['result' => 'victory & defeat']);
        $this->assertEquals('victory & defeat', $battle->result);
    }

    /**
     * @test
     */
    public function it_handles_large_troop_numbers()
    {
        $largeTroops = ['infantry' => 999999, 'cavalry' => 888888];
        $battle = Battle::factory()->create(['attacker_troops' => $largeTroops]);
        $this->assertEquals($largeTroops, $battle->attacker_troops);
    }

    /**
     * @test
     */
    public function it_handles_complex_loot_structure()
    {
        $complexLoot = [
            'resources' => ['wood' => 1000, 'clay' => 500],
            'items' => ['sword' => 1, 'shield' => 2],
            'bonus' => ['experience' => 100],
        ];
        $battle = Battle::factory()->create(['loot' => $complexLoot]);
        $this->assertEquals($complexLoot, $battle->loot);
    }
}

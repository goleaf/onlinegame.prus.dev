<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\ValueObjects\ResourceAmounts;
use App\ValueObjects\TroopCounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    protected Movement $movement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->movement = new Movement();
    }

    /**
     * @test
     */
    public function it_can_create_movement()
    {
        $player = Player::factory()->create();
        $fromVillage = Village::factory()->create();
        $toVillage = Village::factory()->create();

        $movement = Movement::create([
            'player_id' => $player->id,
            'from_village_id' => $fromVillage->id,
            'to_village_id' => $toVillage->id,
            'type' => 'attack',
            'troops' => ['legionnaires' => 100, 'praetorians' => 50],
            'resources' => ['wood' => 1000, 'clay' => 500],
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
            'metadata' => ['special' => 'raid'],
        ]);

        $this->assertInstanceOf(Movement::class, $movement);
        $this->assertEquals($player->id, $movement->player_id);
        $this->assertEquals($fromVillage->id, $movement->from_village_id);
        $this->assertEquals($toVillage->id, $movement->to_village_id);
        $this->assertEquals('attack', $movement->type);
        $this->assertEquals('travelling', $movement->status);
    }

    /**
     * @test
     */
    public function it_casts_troops_to_array()
    {
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'troops' => ['legionnaires' => 100, 'praetorians' => 50],
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertIsArray($movement->troops);
        $this->assertEquals(['legionnaires' => 100, 'praetorians' => 50], $movement->troops);
    }

    /**
     * @test
     */
    public function it_casts_resources_to_array()
    {
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'resources' => ['wood' => 1000, 'clay' => 500],
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertIsArray($movement->resources);
        $this->assertEquals(['wood' => 1000, 'clay' => 500], $movement->resources);
    }

    /**
     * @test
     */
    public function it_casts_datetime_fields()
    {
        $now = now();
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => $now,
            'arrives_at' => $now->addHours(2),
            'returned_at' => $now->addHours(4),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $movement->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $movement->arrives_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $movement->returned_at);
    }

    /**
     * @test
     */
    public function it_casts_metadata_to_array()
    {
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
            'metadata' => ['special' => 'raid', 'priority' => 'high'],
        ]);

        $this->assertIsArray($movement->metadata);
        $this->assertEquals(['special' => 'raid', 'priority' => 'high'], $movement->metadata);
    }

    /**
     * @test
     */
    public function it_belongs_to_player()
    {
        $player = Player::factory()->create();
        $movement = Movement::create([
            'player_id' => $player->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(Player::class, $movement->player);
        $this->assertEquals($player->id, $movement->player->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_from_village()
    {
        $fromVillage = Village::factory()->create();
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => $fromVillage->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(Village::class, $movement->fromVillage);
        $this->assertEquals($fromVillage->id, $movement->fromVillage->id);
    }

    /**
     * @test
     */
    public function it_belongs_to_to_village()
    {
        $toVillage = Village::factory()->create();
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => $toVillage->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(Village::class, $movement->toVillage);
        $this->assertEquals($toVillage->id, $movement->toVillage->id);
    }

    /**
     * @test
     */
    public function it_has_by_village_scope()
    {
        $village1 = Village::factory()->create();
        $village2 = Village::factory()->create();

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => $village1->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => $village1->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => $village2->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $village1Movements = Movement::byVillage($village1->id)->get();
        $this->assertCount(2, $village1Movements);
    }

    /**
     * @test
     */
    public function it_has_by_player_scope()
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        Movement::create([
            'player_id' => $player1->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => $player2->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $player1Movements = Movement::byPlayer($player1->id)->get();
        $this->assertCount(1, $player1Movements);
    }

    /**
     * @test
     */
    public function it_has_by_type_scope()
    {
        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'support',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $attackMovements = Movement::byType('attack')->get();
        $this->assertCount(1, $attackMovements);
        $this->assertEquals('attack', $attackMovements->first()->type);
    }

    /**
     * @test
     */
    public function it_has_by_status_scope()
    {
        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'completed',
        ]);

        $travellingMovements = Movement::byStatus('travelling')->get();
        $this->assertCount(1, $travellingMovements);
        $this->assertEquals('travelling', $travellingMovements->first()->status);
    }

    /**
     * @test
     */
    public function it_has_travelling_scope()
    {
        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'completed',
        ]);

        $travellingMovements = Movement::travelling()->get();
        $this->assertCount(1, $travellingMovements);
        $this->assertEquals('travelling', $travellingMovements->first()->status);
    }

    /**
     * @test
     */
    public function it_has_completed_scope()
    {
        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'completed',
        ]);

        $completedMovements = Movement::completed()->get();
        $this->assertCount(1, $completedMovements);
        $this->assertEquals('completed', $completedMovements->first()->status);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
            'created_at' => now()->subDays(3),
        ]);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
            'created_at' => now()->subDays(10),
        ]);

        $recentMovements = Movement::recent(7)->get();
        $this->assertCount(1, $recentMovements);
    }

    /**
     * @test
     */
    public function it_has_search_scope()
    {
        $village = Village::factory()->create(['name' => 'Test Village']);

        Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => $village->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $searchResults = Movement::search('Test Village')->get();
        $this->assertCount(1, $searchResults);
    }

    /**
     * @test
     */
    public function it_has_with_village_info_scope()
    {
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $movementWithInfo = Movement::withVillageInfo()->first();

        $this->assertTrue($movementWithInfo->relationLoaded('fromVillage'));
        $this->assertTrue($movementWithInfo->relationLoaded('toVillage'));
        $this->assertTrue($movementWithInfo->relationLoaded('player'));
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertNotNull($movement->reference_number);
        $this->assertStringStartsWith('MOV-', $movement->reference_number);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'support',
            'troops' => ['legionnaires' => 200],
            'resources' => ['wood' => 2000],
            'started_at' => now(),
            'arrives_at' => now()->addHours(3),
            'status' => 'travelling',
            'metadata' => ['special' => 'reinforcement'],
        ];

        $movement = Movement::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $movement->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_reference_trait()
    {
        $this->assertTrue(method_exists($this->movement, 'generateReference'));
    }

    /**
     * @test
     */
    public function it_has_auditing_trait()
    {
        $this->assertTrue(method_exists($this->movement, 'audits'));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(method_exists($this->movement, 'taxonomies'));
    }

    /**
     * @test
     */
    public function it_has_allowed_filters()
    {
        $this->assertTrue(method_exists($this->movement, 'allowedFilters'));
    }

    /**
     * @test
     */
    public function it_can_use_troops_value_object()
    {
        $troops = new TroopCounts(
            legionnaires: 100,
            praetorians: 50,
            imperians: 25,
            equitesLegati: 10,
            equitesImperatoris: 5,
            equitesCaesaris: 2,
            batteringRams: 3,
            fireCatapults: 1,
            senators: 1,
            settlers: 5
        );

        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'troops' => $troops,
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(TroopCounts::class, $movement->troops);
        $this->assertEquals(100, $movement->troops->legionnaires);
        $this->assertEquals(50, $movement->troops->praetorians);
    }

    /**
     * @test
     */
    public function it_can_use_resources_value_object()
    {
        $resources = new ResourceAmounts(
            wood: 1000,
            clay: 500,
            iron: 300,
            crop: 200
        );

        $movement = Movement::create([
            'player_id' => Player::factory()->create()->id,
            'from_village_id' => Village::factory()->create()->id,
            'to_village_id' => Village::factory()->create()->id,
            'type' => 'attack',
            'resources' => $resources,
            'started_at' => now(),
            'arrives_at' => now()->addHours(2),
            'status' => 'travelling',
        ]);

        $this->assertInstanceOf(ResourceAmounts::class, $movement->resources);
        $this->assertEquals(1000, $movement->resources->wood);
        $this->assertEquals(500, $movement->resources->clay);
    }
}

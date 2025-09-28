<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\BattleResult;
use Tests\TestCase;

class BattleResultTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_with_valid_data()
    {
        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: ['infantry' => 10, 'archer' => 5],
            defenderLosses: ['infantry' => 20, 'archer' => 15],
            loot: ['wood' => 1000, 'clay' => 800, 'iron' => 600, 'crop' => 400],
            attackerPower: 1500,
            defenderPower: 1200
        );

        $this->assertTrue($battleResult->attackerWins);
        $this->assertEquals(['infantry' => 10, 'archer' => 5], $battleResult->attackerLosses);
        $this->assertEquals(['infantry' => 20, 'archer' => 15], $battleResult->defenderLosses);
        $this->assertEquals(['wood' => 1000, 'clay' => 800, 'iron' => 600, 'crop' => 400], $battleResult->loot);
        $this->assertEquals(1500, $battleResult->attackerPower);
        $this->assertEquals(1200, $battleResult->defenderPower);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_defender_wins()
    {
        $battleResult = new BattleResult(
            attackerWins: false,
            attackerLosses: ['infantry' => 25, 'archer' => 20],
            defenderLosses: ['infantry' => 5, 'archer' => 3],
            loot: [],
            attackerPower: 800,
            defenderPower: 1800
        );

        $this->assertFalse($battleResult->attackerWins);
        $this->assertEquals(['infantry' => 25, 'archer' => 20], $battleResult->attackerLosses);
        $this->assertEquals(['infantry' => 5, 'archer' => 3], $battleResult->defenderLosses);
        $this->assertEquals([], $battleResult->loot);
        $this->assertEquals(800, $battleResult->attackerPower);
        $this->assertEquals(1800, $battleResult->defenderPower);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_empty_losses()
    {
        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: [],
            defenderLosses: [],
            loot: ['wood' => 500],
            attackerPower: 1000,
            defenderPower: 500
        );

        $this->assertTrue($battleResult->attackerWins);
        $this->assertEquals([], $battleResult->attackerLosses);
        $this->assertEquals([], $battleResult->defenderLosses);
        $this->assertEquals(['wood' => 500], $battleResult->loot);
        $this->assertEquals(1000, $battleResult->attackerPower);
        $this->assertEquals(500, $battleResult->defenderPower);
    }

    /**
     * @test
     */
    public function it_can_be_created_with_zero_power()
    {
        $battleResult = new BattleResult(
            attackerWins: false,
            attackerLosses: ['infantry' => 10],
            defenderLosses: [],
            loot: [],
            attackerPower: 0,
            defenderPower: 0
        );

        $this->assertFalse($battleResult->attackerWins);
        $this->assertEquals(['infantry' => 10], $battleResult->attackerLosses);
        $this->assertEquals([], $battleResult->defenderLosses);
        $this->assertEquals([], $battleResult->loot);
        $this->assertEquals(0, $battleResult->attackerPower);
        $this->assertEquals(0, $battleResult->defenderPower);
    }

    /**
     * @test
     */
    public function it_can_handle_complex_troop_compositions()
    {
        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: [
                'infantry' => 15,
                'archer' => 8,
                'cavalry' => 3,
                'siege' => 1,
            ],
            defenderLosses: [
                'infantry' => 30,
                'archer' => 20,
                'cavalry' => 10,
                'siege' => 2,
            ],
            loot: [
                'wood' => 2000,
                'clay' => 1500,
                'iron' => 1200,
                'crop' => 800,
            ],
            attackerPower: 2500,
            defenderPower: 2200
        );

        $this->assertTrue($battleResult->attackerWins);
        $this->assertArrayHasKey('infantry', $battleResult->attackerLosses);
        $this->assertArrayHasKey('archer', $battleResult->attackerLosses);
        $this->assertArrayHasKey('cavalry', $battleResult->attackerLosses);
        $this->assertArrayHasKey('siege', $battleResult->attackerLosses);
        $this->assertEquals(15, $battleResult->attackerLosses['infantry']);
        $this->assertEquals(8, $battleResult->attackerLosses['archer']);
        $this->assertEquals(3, $battleResult->attackerLosses['cavalry']);
        $this->assertEquals(1, $battleResult->attackerLosses['siege']);

        $this->assertArrayHasKey('wood', $battleResult->loot);
        $this->assertArrayHasKey('clay', $battleResult->loot);
        $this->assertArrayHasKey('iron', $battleResult->loot);
        $this->assertArrayHasKey('crop', $battleResult->loot);
        $this->assertEquals(2000, $battleResult->loot['wood']);
        $this->assertEquals(1500, $battleResult->loot['clay']);
        $this->assertEquals(1200, $battleResult->loot['iron']);
        $this->assertEquals(800, $battleResult->loot['crop']);
    }

    /**
     * @test
     */
    public function it_can_handle_large_numbers()
    {
        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: ['infantry' => 1000000],
            defenderLosses: ['infantry' => 2000000],
            loot: ['wood' => 50000000],
            attackerPower: 100000000,
            defenderPower: 80000000
        );

        $this->assertTrue($battleResult->attackerWins);
        $this->assertEquals(['infantry' => 1000000], $battleResult->attackerLosses);
        $this->assertEquals(['infantry' => 2000000], $battleResult->defenderLosses);
        $this->assertEquals(['wood' => 50000000], $battleResult->loot);
        $this->assertEquals(100000000, $battleResult->attackerPower);
        $this->assertEquals(80000000, $battleResult->defenderPower);
    }

    /**
     * @test
     */
    public function it_preserves_data_integrity()
    {
        $originalAttackerLosses = ['infantry' => 10, 'archer' => 5];
        $originalDefenderLosses = ['infantry' => 20, 'archer' => 15];
        $originalLoot = ['wood' => 1000, 'clay' => 800];

        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: $originalAttackerLosses,
            defenderLosses: $originalDefenderLosses,
            loot: $originalLoot,
            attackerPower: 1500,
            defenderPower: 1200
        );

        // Modify original arrays
        $originalAttackerLosses['infantry'] = 999;
        $originalDefenderLosses['archer'] = 999;
        $originalLoot['wood'] = 999;

        // Value object should maintain original values
        $this->assertEquals(10, $battleResult->attackerLosses['infantry']);
        $this->assertEquals(15, $battleResult->defenderLosses['archer']);
        $this->assertEquals(1000, $battleResult->loot['wood']);
    }

    /**
     * @test
     */
    public function it_can_be_serialized_and_unserialized()
    {
        $battleResult = new BattleResult(
            attackerWins: true,
            attackerLosses: ['infantry' => 10, 'archer' => 5],
            defenderLosses: ['infantry' => 20, 'archer' => 15],
            loot: ['wood' => 1000, 'clay' => 800, 'iron' => 600, 'crop' => 400],
            attackerPower: 1500,
            defenderPower: 1200
        );

        $serialized = serialize($battleResult);
        $unserialized = unserialize($serialized);

        $this->assertEquals($battleResult->attackerWins, $unserialized->attackerWins);
        $this->assertEquals($battleResult->attackerLosses, $unserialized->attackerLosses);
        $this->assertEquals($battleResult->defenderLosses, $unserialized->defenderLosses);
        $this->assertEquals($battleResult->loot, $unserialized->loot);
        $this->assertEquals($battleResult->attackerPower, $unserialized->attackerPower);
        $this->assertEquals($battleResult->defenderPower, $unserialized->defenderPower);
    }
}

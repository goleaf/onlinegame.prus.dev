<?php

namespace Tests\Unit\Services;

use App\Models\Game\Artifact;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\ArtifactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ArtifactServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_discover_artifact()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'name' => 'Test Artifact',
            'type' => 'weapon',
            'rarity' => 'rare',
        ];

        $service = new ArtifactService();
        $result = $service->discoverArtifact($player, $village, $data);

        $this->assertInstanceOf(Artifact::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['rarity'], $result->rarity);
        $this->assertEquals($player->id, $result->owner_id);
        $this->assertEquals($village->id, $result->village_id);
    }

    /**
     * @test
     */
    public function it_can_activate_artifact()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'status' => 'inactive',
        ]);

        $service = new ArtifactService();
        $result = $service->activateArtifact($player, $artifact);

        $this->assertTrue($result);
        $this->assertEquals('active', $artifact->status);
    }

    /**
     * @test
     */
    public function it_can_deactivate_artifact()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'status' => 'active',
        ]);

        $service = new ArtifactService();
        $result = $service->deactivateArtifact($player, $artifact);

        $this->assertTrue($result);
        $this->assertEquals('inactive', $artifact->status);
    }

    /**
     * @test
     */
    public function it_can_repair_artifact()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'durability' => 50,
            'max_durability' => 100,
        ]);

        $service = new ArtifactService();
        $result = $service->repairArtifact($player, $artifact, 25);

        $this->assertTrue($result);
        $this->assertEquals(75, $artifact->durability);
    }

    /**
     * @test
     */
    public function it_can_damage_artifact()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'durability' => 100,
            'max_durability' => 100,
        ]);

        $service = new ArtifactService();
        $result = $service->damageArtifact($player, $artifact, 25);

        $this->assertTrue($result);
        $this->assertEquals(75, $artifact->durability);
    }

    /**
     * @test
     */
    public function it_can_transfer_artifact()
    {
        $player = Player::factory()->create();
        $newOwner = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
        ]);

        $service = new ArtifactService();
        $result = $service->transferArtifact($player, $artifact, $newOwner);

        $this->assertTrue($result);
        $this->assertEquals($newOwner->id, $artifact->owner_id);
    }

    /**
     * @test
     */
    public function it_can_get_player_artifacts()
    {
        $player = Player::factory()->create();
        $artifacts = collect([
            Artifact::factory()->create(['owner_id' => $player->id]),
            Artifact::factory()->create(['owner_id' => $player->id]),
        ]);

        $player->shouldReceive('artifacts')->andReturn($artifacts);

        $service = new ArtifactService();
        $result = $service->getPlayerArtifacts($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_village_artifacts()
    {
        $village = Village::factory()->create();
        $artifacts = collect([
            Artifact::factory()->create(['village_id' => $village->id]),
            Artifact::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('artifacts')->andReturn($artifacts);

        $service = new ArtifactService();
        $result = $service->getVillageArtifacts($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_by_type()
    {
        $player = Player::factory()->create();
        $artifacts = collect([
            Artifact::factory()->create(['owner_id' => $player->id, 'type' => 'weapon']),
            Artifact::factory()->create(['owner_id' => $player->id, 'type' => 'armor']),
        ]);

        $player->shouldReceive('artifacts')->andReturn($artifacts);

        $service = new ArtifactService();
        $result = $service->getArtifactsByType($player, 'weapon');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_by_rarity()
    {
        $player = Player::factory()->create();
        $artifacts = collect([
            Artifact::factory()->create(['owner_id' => $player->id, 'rarity' => 'rare']),
            Artifact::factory()->create(['owner_id' => $player->id, 'rarity' => 'common']),
        ]);

        $player->shouldReceive('artifacts')->andReturn($artifacts);

        $service = new ArtifactService();
        $result = $service->getArtifactsByRarity($player, 'rare');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_active_artifacts()
    {
        $player = Player::factory()->create();
        $artifacts = collect([
            Artifact::factory()->create(['owner_id' => $player->id, 'status' => 'active']),
            Artifact::factory()->create(['owner_id' => $player->id, 'status' => 'inactive']),
        ]);

        $player->shouldReceive('artifacts')->andReturn($artifacts);

        $service = new ArtifactService();
        $result = $service->getActiveArtifacts($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_effects()
    {
        $artifact = Artifact::factory()->create();
        $effects = collect([
            (object) ['type' => 'attack_bonus', 'value' => 10],
            (object) ['type' => 'defense_bonus', 'value' => 5],
        ]);

        $artifact->shouldReceive('artifactEffects')->andReturn($effects);

        $service = new ArtifactService();
        $result = $service->getArtifactEffects($artifact);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_apply_artifact_effects()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'effects' => [
                ['type' => 'attack_bonus', 'value' => 10],
                ['type' => 'defense_bonus', 'value' => 5],
            ],
        ]);

        $service = new ArtifactService();
        $result = $service->applyArtifactEffects($player, $artifact);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_remove_artifact_effects()
    {
        $player = Player::factory()->create();
        $artifact = Artifact::factory()->create([
            'owner_id' => $player->id,
            'effects' => [
                ['type' => 'attack_bonus', 'value' => 10],
                ['type' => 'defense_bonus', 'value' => 5],
            ],
        ]);

        $service = new ArtifactService();
        $result = $service->removeArtifactEffects($player, $artifact);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_statistics()
    {
        $service = new ArtifactService();
        $result = $service->getArtifactStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_artifacts', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_rarity', $result);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_leaderboard()
    {
        $service = new ArtifactService();
        $result = $service->getArtifactLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

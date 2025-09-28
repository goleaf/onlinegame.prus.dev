<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Artifact;
use App\Models\Game\ArtifactEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactEffectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_artifact_effect()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => true,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => now(),
            'metadata' => ['source' => 'enchantment', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('artifact_effects', [
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $artifact = Artifact::factory()->create();

        $effect = new ArtifactEffect([
            'artifact_id' => $artifact->id,
            'effect_type' => 'defense_boost',
            'effect_name' => 'Ice Shield',
            'effect_value' => 15.0,
            'effect_duration' => 600,
            'is_permanent' => true,
            'is_active' => false,
            'activation_condition' => 'on_defend',
            'deactivation_condition' => 'on_damage',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 120,
            'last_activated' => null,
            'metadata' => ['source' => 'magic', 'version' => '1.1'],
        ]);

        $this->assertEquals($artifact->id, $effect->artifact_id);
        $this->assertEquals('defense_boost', $effect->effect_type);
        $this->assertEquals('Ice Shield', $effect->effect_name);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'test',
            'effect_name' => 'Test Effect',
            'effect_value' => 10.0,
            'effect_duration' => 300,
            'is_permanent' => true,
            'is_active' => false,
            'activation_condition' => 'test',
            'deactivation_condition' => 'test',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $this->assertTrue($effect->is_permanent);
        $this->assertFalse($effect->is_active);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'test',
            'effect_name' => 'Test Effect',
            'effect_value' => 10.0,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'test',
            'deactivation_condition' => 'test',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 60,
            'last_activated' => now(),
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $effect->last_activated);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'test',
            'effect_name' => 'Test Effect',
            'effect_value' => 10.0,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'test',
            'deactivation_condition' => 'test',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($effect->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_effects_by_artifact()
    {
        $artifact1 = Artifact::factory()->create();
        $artifact2 = Artifact::factory()->create();

        ArtifactEffect::create([
            'artifact_id' => $artifact1->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        ArtifactEffect::create([
            'artifact_id' => $artifact2->id,
            'effect_type' => 'defense_boost',
            'effect_name' => 'Ice Shield',
            'effect_value' => 15.0,
            'effect_duration' => 600,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_defend',
            'deactivation_condition' => 'on_damage',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 120,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $artifact1Effects = ArtifactEffect::byArtifact($artifact1->id)->get();
        $this->assertCount(1, $artifact1Effects);
        $this->assertEquals($artifact1->id, $artifact1Effects->first()->artifact_id);
    }

    /**
     * @test
     */
    public function it_can_scope_effects_by_type()
    {
        $artifact = Artifact::factory()->create();

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'defense_boost',
            'effect_name' => 'Ice Shield',
            'effect_value' => 15.0,
            'effect_duration' => 600,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_defend',
            'deactivation_condition' => 'on_damage',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 120,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $damageEffects = ArtifactEffect::byType('damage_boost')->get();
        $this->assertCount(1, $damageEffects);
        $this->assertEquals('damage_boost', $damageEffects->first()->effect_type);
    }

    /**
     * @test
     */
    public function it_can_scope_active_effects()
    {
        $artifact = Artifact::factory()->create();

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Active Effect',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => true,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'defense_boost',
            'effect_name' => 'Inactive Effect',
            'effect_value' => 15.0,
            'effect_duration' => 600,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_defend',
            'deactivation_condition' => 'on_damage',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 120,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $activeEffects = ArtifactEffect::active()->get();
        $this->assertCount(1, $activeEffects);
        $this->assertTrue($activeEffects->first()->is_active);
    }

    /**
     * @test
     */
    public function it_can_scope_permanent_effects()
    {
        $artifact = Artifact::factory()->create();

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Permanent Effect',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => true,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'defense_boost',
            'effect_name' => 'Temporary Effect',
            'effect_value' => 15.0,
            'effect_duration' => 600,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_defend',
            'deactivation_condition' => 'on_damage',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 120,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $permanentEffects = ArtifactEffect::permanent()->get();
        $this->assertCount(1, $permanentEffects);
        $this->assertTrue($permanentEffects->first()->is_permanent);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_relationship()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'test',
            'effect_name' => 'Test Effect',
            'effect_value' => 10.0,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'test',
            'deactivation_condition' => 'test',
            'stack_limit' => 1,
            'current_stacks' => 0,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $effect->artifact());
        $this->assertEquals($artifact->id, $effect->artifact->id);
    }

    /**
     * @test
     */
    public function it_can_get_effect_summary()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $summary = $effect->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('Fire Damage', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_effect_details()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $details = $effect->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('effect_type', $details);
        $this->assertArrayHasKey('effect_name', $details);
    }

    /**
     * @test
     */
    public function it_can_get_effect_statistics()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $stats = $effect->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('effect_value', $stats);
        $this->assertArrayHasKey('effect_duration', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_effect_timeline()
    {
        $artifact = Artifact::factory()->create();

        $effect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => 'damage_boost',
            'effect_name' => 'Fire Damage',
            'effect_value' => 25.5,
            'effect_duration' => 300,
            'is_permanent' => false,
            'is_active' => false,
            'activation_condition' => 'on_attack',
            'deactivation_condition' => 'on_hit',
            'stack_limit' => 3,
            'current_stacks' => 1,
            'cooldown' => 60,
            'last_activated' => null,
            'metadata' => [],
        ]);

        $timeline = $effect->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('created_at', $timeline);
        $this->assertArrayHasKey('updated_at', $timeline);
    }
}

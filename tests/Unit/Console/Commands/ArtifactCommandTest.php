<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ArtifactCommand;
use App\Models\Game\Artifact;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\ArtifactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_generate_random_artifacts()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('generateRandomArtifact')->once()->andReturn([
                'name' => 'Test Artifact',
                'type' => 'weapon',
                'rarity' => 'rare',
                'power_level' => 75,
                'effects' => ['attack_bonus' => 0.15],
            ]);
        });

        $this
            ->artisan('artifact:generate', [
                '--count' => '1',
                '--village' => $village->id,
            ])
            ->expectsOutput('Generating 1 random artifacts for village '.$village->id)
            ->expectsOutput('Generated artifact: Test Artifact (rare weapon)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_generate_multiple_random_artifacts()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('generateRandomArtifact')->times(3)->andReturn([
                'name' => 'Test Artifact',
                'type' => 'weapon',
                'rarity' => 'rare',
                'power_level' => 75,
                'effects' => ['attack_bonus' => 0.15],
            ]);
        });

        $this
            ->artisan('artifact:generate', [
                '--count' => '3',
                '--village' => $village->id,
            ])
            ->expectsOutput('Generating 3 random artifacts for village '.$village->id)
            ->expectsOutput('Generated artifact: Test Artifact (rare weapon)')
            ->expectsOutput('Generated artifact: Test Artifact (rare weapon)')
            ->expectsOutput('Generated artifact: Test Artifact (rare weapon)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_activate_artifact()
    {
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('activateArtifact')->with($artifact->id)->once()->andReturn(true);
        });

        $this
            ->artisan('artifact:activate', ['artifact_id' => $artifact->id])
            ->expectsOutput('Activating artifact '.$artifact->id)
            ->expectsOutput('Artifact activated successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_artifact_activation_failure()
    {
        $artifact = Artifact::factory()->create(['status' => 'inactive']);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('activateArtifact')->with($artifact->id)->once()->andReturn(false);
        });

        $this
            ->artisan('artifact:activate', ['artifact_id' => $artifact->id])
            ->expectsOutput('Activating artifact '.$artifact->id)
            ->expectsOutput('Failed to activate artifact')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_deactivate_artifact()
    {
        $artifact = Artifact::factory()->create(['status' => 'active']);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('deactivateArtifact')->with($artifact->id)->once()->andReturn(true);
        });

        $this
            ->artisan('artifact:deactivate', ['artifact_id' => $artifact->id])
            ->expectsOutput('Deactivating artifact '.$artifact->id)
            ->expectsOutput('Artifact deactivated successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_artifact_deactivation_failure()
    {
        $artifact = Artifact::factory()->create(['status' => 'active']);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('deactivateArtifact')->with($artifact->id)->once()->andReturn(false);
        });

        $this
            ->artisan('artifact:deactivate', ['artifact_id' => $artifact->id])
            ->expectsOutput('Deactivating artifact '.$artifact->id)
            ->expectsOutput('Failed to deactivate artifact')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_repair_artifact()
    {
        $artifact = Artifact::factory()->create(['durability' => 50, 'max_durability' => 100]);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('repairArtifact')->with($artifact->id)->once()->andReturn(true);
        });

        $this
            ->artisan('artifact:repair', ['artifact_id' => $artifact->id])
            ->expectsOutput('Repairing artifact '.$artifact->id)
            ->expectsOutput('Artifact repaired successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_artifact_repair_failure()
    {
        $artifact = Artifact::factory()->create(['durability' => 50, 'max_durability' => 100]);

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('repairArtifact')->with($artifact->id)->once()->andReturn(false);
        });

        $this
            ->artisan('artifact:repair', ['artifact_id' => $artifact->id])
            ->expectsOutput('Repairing artifact '.$artifact->id)
            ->expectsOutput('Failed to repair artifact')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_transfer_artifact()
    {
        $artifact = Artifact::factory()->create();
        $newVillage = Village::factory()->create();

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('transferArtifact')->with(1, 2)->once()->andReturn(true);
        });

        $this
            ->artisan('artifact:transfer', [
                'artifact_id' => $artifact->id,
                'village_id' => $newVillage->id,
            ])
            ->expectsOutput('Transferring artifact '.$artifact->id.' to village '.$newVillage->id)
            ->expectsOutput('Artifact transferred successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_artifact_transfer_failure()
    {
        $artifact = Artifact::factory()->create();
        $newVillage = Village::factory()->create();

        $this->mock(ArtifactService::class, function ($mock): void {
            $mock->shouldReceive('transferArtifact')->with(1, 2)->once()->andReturn(false);
        });

        $this
            ->artisan('artifact:transfer', [
                'artifact_id' => $artifact->id,
                'village_id' => $newVillage->id,
            ])
            ->expectsOutput('Transferring artifact '.$artifact->id.' to village '.$newVillage->id)
            ->expectsOutput('Failed to transfer artifact')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_list_artifacts()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $artifacts = Artifact::factory()->count(3)->create(['village_id' => $village->id]);

        $this
            ->artisan('artifact:list', ['--village' => $village->id])
            ->expectsOutput('Artifacts in village '.$village->id.':')
            ->expectsOutput('ID | Name | Type | Rarity | Status | Durability')
            ->expectsOutput('--- | ---- | ---- | ------ | ------ | ----------')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_list_all_artifacts()
    {
        Artifact::factory()->count(3)->create();

        $this
            ->artisan('artifact:list')
            ->expectsOutput('All Artifacts:')
            ->expectsOutput('ID | Name | Type | Rarity | Status | Durability | Village')
            ->expectsOutput('--- | ---- | ---- | ------ | ------ | ---------- | -------')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_village()
    {
        $this
            ->artisan('artifact:generate', [
                '--count' => '1',
                '--village' => '999',
            ])
            ->expectsOutput('Village not found: 999')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_artifact()
    {
        $this
            ->artisan('artifact:activate', ['artifact_id' => '999'])
            ->expectsOutput('Artifact not found: 999')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new ArtifactCommand();
        $this->assertEquals('artifact', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new ArtifactCommand();
        $this->assertEquals('Manage game artifacts', $command->getDescription());
    }
}

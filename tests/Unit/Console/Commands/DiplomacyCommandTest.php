<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\DiplomacyCommand;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Services\DiplomacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiplomacyCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_peace_treaty()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance A']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance B']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('createPeaceTreaty')->once()->andReturn(true);
        });

        $this
            ->artisan('diplomacy:peace', [
                'alliance1' => $alliance1->id,
                'alliance2' => $alliance2->id,
            ])
            ->expectsOutput('Creating peace treaty between Alliance A and Alliance B...')
            ->expectsOutput('Peace treaty created successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_peace_treaty_creation_failure()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance A']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance B']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('createPeaceTreaty')->once()->andReturn(false);
        });

        $this
            ->artisan('diplomacy:peace', [
                'alliance1' => $alliance1->id,
                'alliance2' => $alliance2->id,
            ])
            ->expectsOutput('Creating peace treaty between Alliance A and Alliance B...')
            ->expectsOutput('Failed to create peace treaty.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_declare_war()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance A']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance B']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('declareWar')->once()->andReturn(true);
        });

        $this
            ->artisan('diplomacy:war', [
                'alliance1' => $alliance1->id,
                'alliance2' => $alliance2->id,
            ])
            ->expectsOutput('Declaring war between Alliance A and Alliance B...')
            ->expectsOutput('War declared successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_war_declaration_failure()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance A']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance B']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('declareWar')->once()->andReturn(false);
        });

        $this
            ->artisan('diplomacy:war', [
                'alliance1' => $alliance1->id,
                'alliance2' => $alliance2->id,
            ])
            ->expectsOutput('Declaring war between Alliance A and Alliance B...')
            ->expectsOutput('Failed to declare war.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $player = Player::factory()->create();

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('createAlliance')->once()->andReturn([
                'id' => 1,
                'name' => 'New Alliance',
                'tag' => 'NEW',
            ]);
        });

        $this
            ->artisan('diplomacy:create-alliance', [
                'name' => 'New Alliance',
                'tag' => 'NEW',
                'leader_id' => $player->id,
            ])
            ->expectsOutput('Creating alliance: New Alliance [NEW]...')
            ->expectsOutput('Alliance created successfully!')
            ->expectsOutput('Alliance ID: 1')
            ->expectsOutput('Alliance Name: New Alliance')
            ->expectsOutput('Alliance Tag: NEW')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_alliance_creation_failure()
    {
        $player = Player::factory()->create();

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('createAlliance')->once()->andReturn(false);
        });

        $this
            ->artisan('diplomacy:create-alliance', [
                'name' => 'New Alliance',
                'tag' => 'NEW',
                'leader_id' => $player->id,
            ])
            ->expectsOutput('Creating alliance: New Alliance [NEW]...')
            ->expectsOutput('Failed to create alliance.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_list_diplomatic_relations()
    {
        $alliance = Alliance::factory()->create(['name' => 'Test Alliance']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('getDiplomaticRelations')->once()->andReturn([
                [
                    'alliance1_name' => 'Alliance A',
                    'alliance2_name' => 'Alliance B',
                    'relation_type' => 'peace',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
                [
                    'alliance1_name' => 'Alliance C',
                    'alliance2_name' => 'Alliance D',
                    'relation_type' => 'war',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
            ]);
        });

        $this
            ->artisan('diplomacy:list', ['--alliance' => $alliance->id])
            ->expectsOutput('Diplomatic Relations for Test Alliance:')
            ->expectsOutput('Alliance A <-> Alliance B: peace')
            ->expectsOutput('Alliance C <-> Alliance D: war')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_list_all_diplomatic_relations()
    {
        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('getAllDiplomaticRelations')->once()->andReturn([
                [
                    'alliance1_name' => 'Alliance A',
                    'alliance2_name' => 'Alliance B',
                    'relation_type' => 'peace',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
            ]);
        });

        $this
            ->artisan('diplomacy:list')
            ->expectsOutput('All Diplomatic Relations:')
            ->expectsOutput('Alliance A <-> Alliance B: peace')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_end_diplomatic_relation()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance A']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance B']);

        $this->mock(DiplomacyService::class, function ($mock): void {
            $mock->shouldReceive('endDiplomaticRelation')->once()->andReturn(true);
        });

        $this
            ->artisan('diplomacy:end', [
                'alliance1' => $alliance1->id,
                'alliance2' => $alliance2->id,
            ])
            ->expectsOutput('Ending diplomatic relation between Alliance A and Alliance B...')
            ->expectsOutput('Diplomatic relation ended successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_alliance()
    {
        $this
            ->artisan('diplomacy:peace', [
                'alliance1' => '999',
                'alliance2' => '998',
            ])
            ->expectsOutput('Alliance not found: 999')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_same_alliance_error()
    {
        $alliance = Alliance::factory()->create();

        $this
            ->artisan('diplomacy:peace', [
                'alliance1' => $alliance->id,
                'alliance2' => $alliance->id,
            ])
            ->expectsOutput('Cannot create diplomatic relation with the same alliance.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new DiplomacyCommand();
        $this->assertEquals('diplomacy', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new DiplomacyCommand();
        $this->assertEquals('Manage diplomatic relations between alliances', $command->getDescription());
    }
}

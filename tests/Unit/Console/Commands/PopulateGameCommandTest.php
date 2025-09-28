<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\PopulateGameCommand;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopulateGameCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_populate_game_data()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this->mock(World::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new World(['id' => 1, 'name' => 'Test World']));
        });

        $this
            ->artisan('game:populate')
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Players created: 1')
            ->expectsOutput('Villages created: 1')
            ->expectsOutput('Worlds created: 1')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_custom_counts()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this->mock(World::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new World(['id' => 1, 'name' => 'Test World']));
        });

        $this
            ->artisan('game:populate', [
                '--players' => '10',
                '--villages' => '20',
                '--worlds' => '2',
            ])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Players created: 10')
            ->expectsOutput('Villages created: 20')
            ->expectsOutput('Worlds created: 2')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_dry_run()
    {
        $this
            ->artisan('game:populate', ['--dry-run' => true])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Dry run mode - no data will be created')
            ->expectsOutput('Would create:')
            ->expectsOutput('  Players: 5')
            ->expectsOutput('  Villages: 10')
            ->expectsOutput('  Worlds: 1')
            ->expectsOutput('Dry run completed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_verbose_output()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this->mock(World::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new World(['id' => 1, 'name' => 'Test World']));
        });

        $this
            ->artisan('game:populate', ['--verbose' => true])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Creating players...')
            ->expectsOutput('Creating villages...')
            ->expectsOutput('Creating worlds...')
            ->expectsOutput('Players created: 1')
            ->expectsOutput('Villages created: 1')
            ->expectsOutput('Worlds created: 1')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_specific_types()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this
            ->artisan('game:populate', ['--types' => 'players,villages'])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Players created: 1')
            ->expectsOutput('Villages created: 1')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_custom_attributes()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this->mock(World::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new World(['id' => 1, 'name' => 'Test World']));
        });

        $this
            ->artisan('game:populate', [
                '--player-attributes' => 'name=TestPlayer,level=10',
                '--village-attributes' => 'name=TestVillage,population=1000',
            ])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Players created: 1')
            ->expectsOutput('Villages created: 1')
            ->expectsOutput('Worlds created: 1')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_populate_with_relationships()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Player(['id' => 1, 'name' => 'Test Player']));
        });

        $this->mock(Village::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new Village(['id' => 1, 'name' => 'Test Village']));
        });

        $this->mock(World::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andReturn(new World(['id' => 1, 'name' => 'Test World']));
        });

        $this
            ->artisan('game:populate', ['--with-relationships' => true])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Players created: 1')
            ->expectsOutput('Villages created: 1')
            ->expectsOutput('Worlds created: 1')
            ->expectsOutput('Relationships created successfully')
            ->expectsOutput('Game data population completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_population_failure()
    {
        $this->mock(Player::class, function ($mock): void {
            $mock->shouldReceive('factory')->andReturnSelf();
            $mock->shouldReceive('create')->andThrow(new \Exception('Player creation failed'));
        });

        $this
            ->artisan('game:populate')
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Populating game data...')
            ->expectsOutput('Game data population failed: Player creation failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_counts()
    {
        $this
            ->artisan('game:populate', [
                '--players' => 'invalid',
                '--villages' => 'invalid',
                '--worlds' => 'invalid',
            ])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Invalid count values. Using default values.')
            ->expectsOutput('Populating game data...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_negative_counts()
    {
        $this
            ->artisan('game:populate', [
                '--players' => '-5',
                '--villages' => '-10',
                '--worlds' => '-1',
            ])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Invalid count values. Using default values.')
            ->expectsOutput('Populating game data...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_invalid_types()
    {
        $this
            ->artisan('game:populate', ['--types' => 'invalid,unknown'])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Invalid types specified. Using default types.')
            ->expectsOutput('Populating game data...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_population_statistics()
    {
        $this
            ->artisan('game:populate', ['--stats' => true])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Game Data Statistics:')
            ->expectsOutput('Total players: 0')
            ->expectsOutput('Total villages: 0')
            ->expectsOutput('Total worlds: 0')
            ->expectsOutput('Last population: Never')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_clear_existing_data()
    {
        $this
            ->artisan('game:populate', ['--clear' => true])
            ->expectsOutput('🎮 Game Data Population Tool')
            ->expectsOutput('Clearing existing game data...')
            ->expectsOutput('Existing data cleared successfully')
            ->expectsOutput('Populating game data...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new PopulateGameCommand();
        $this->assertEquals('game:populate', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new PopulateGameCommand();
        $this->assertEquals('Populate game with sample data', $command->getDescription());
    }
}

<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\PopulateVillageGeographicData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PopulateVillageGeographicDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_populate_village_geographic_data_command()
    {
        $command = new PopulateVillageGeographicData();

        $this->assertInstanceOf(PopulateVillageGeographicData::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_options()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--chunk' => '50',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_small_chunk()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--chunk' => '10',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_large_chunk()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--chunk' => '200',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_quiet()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--quiet' => true,
            '--chunk' => '100',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_no_interaction()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--no-interaction' => true,
            '--chunk' => '100',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_force()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle([
            '--force' => true,
            '--chunk' => '100',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_populate_village_geographic_data_command_with_help()
    {
        $command = new PopulateVillageGeographicData();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

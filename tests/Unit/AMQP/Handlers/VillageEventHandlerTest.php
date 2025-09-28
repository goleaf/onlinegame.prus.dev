<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\VillageEventHandler;
use App\Models\Game\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_village_created_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_created',
            'village_id' => $village->id,
            'data' => [
                'name' => $village->name,
                'player_id' => $village->player_id,
                'x_coordinate' => $village->x_coordinate,
                'y_coordinate' => $village->y_coordinate,
                'created_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_updated_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_updated',
            'village_id' => $village->id,
            'data' => [
                'updated_fields' => ['population', 'culture_points'],
                'new_population' => 1500,
                'new_culture_points' => 750,
                'updated_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_building_constructed_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_building_constructed',
            'village_id' => $village->id,
            'data' => [
                'building_type' => 'barracks',
                'level' => 1,
                'constructed_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_building_upgraded_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_building_upgraded',
            'village_id' => $village->id,
            'data' => [
                'building_type' => 'barracks',
                'old_level' => 1,
                'new_level' => 2,
                'upgraded_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_resource_production_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_resource_production',
            'village_id' => $village->id,
            'data' => [
                'resource_type' => 'wood',
                'amount_produced' => 100,
                'production_rate' => 10,
                'produced_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_attacked_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_attacked',
            'village_id' => $village->id,
            'data' => [
                'attacker_id' => 1,
                'attacker_name' => 'AttackerPlayer',
                'attack_strength' => 1000,
                'defense_strength' => 800,
                'attacked_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_village_destroyed_event()
    {
        $handler = new VillageEventHandler();
        $village = Village::factory()->create();

        $message = [
            'event_type' => 'village_destroyed',
            'village_id' => $village->id,
            'data' => [
                'destroyer_id' => 1,
                'destroyer_name' => 'DestroyerPlayer',
                'destroyed_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_invalid_event_type()
    {
        $handler = new VillageEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'village_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_village_id()
    {
        $handler = new VillageEventHandler();

        $message = [
            'event_type' => 'village_created',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}

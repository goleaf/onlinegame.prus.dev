<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\ResourceEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_resource_production_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_production',
            'village_id' => 1,
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
    public function it_can_handle_resource_consumption_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_consumption',
            'village_id' => 1,
            'data' => [
                'resource_type' => 'wood',
                'amount_consumed' => 50,
                'consumption_reason' => 'building_construction',
                'consumed_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_transfer_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_transfer',
            'from_village_id' => 1,
            'to_village_id' => 2,
            'data' => [
                'resources' => ['wood' => 1000, 'clay' => 800, 'iron' => 600],
                'transfer_type' => 'player_transfer',
                'transferred_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_loot_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_loot',
            'village_id' => 1,
            'data' => [
                'looter_id' => 2,
                'looted_resources' => ['wood' => 500, 'clay' => 400],
                'loot_efficiency' => 0.8,
                'looted_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_storage_full_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_storage_full',
            'village_id' => 1,
            'data' => [
                'resource_type' => 'wood',
                'current_amount' => 10000,
                'max_storage' => 10000,
                'full_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_storage_expanded_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_storage_expanded',
            'village_id' => 1,
            'data' => [
                'old_capacity' => 10000,
                'new_capacity' => 15000,
                'expansion_cost' => ['wood' => 1000, 'clay' => 800],
                'expanded_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_bonus_applied_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_bonus_applied',
            'village_id' => 1,
            'data' => [
                'bonus_type' => 'artifact_effect',
                'bonus_multiplier' => 1.2,
                'affected_resources' => ['wood', 'clay', 'iron', 'crop'],
                'bonus_duration' => 3600,
                'applied_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_shortage_event()
    {
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_shortage',
            'village_id' => 1,
            'data' => [
                'shortage_type' => 'wood',
                'required_amount' => 1000,
                'available_amount' => 500,
                'shortage_at' => now()->toISOString(),
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
        $handler = new ResourceEventHandler();

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
        $handler = new ResourceEventHandler();

        $message = [
            'event_type' => 'resource_production',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}

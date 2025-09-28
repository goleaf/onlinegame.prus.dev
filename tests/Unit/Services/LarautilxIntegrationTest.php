<?php

namespace Tests\Unit\Services;

use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarautilxIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_resolve_the_integration_service(): void
    {
        $service = app(LarautilxIntegrationService::class);

        $this->assertInstanceOf(LarautilxIntegrationService::class, $service);
    }
}

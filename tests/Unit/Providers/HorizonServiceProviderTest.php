<?php

namespace Tests\Unit\Providers;

use App\Providers\HorizonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class HorizonServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_boot_horizon_service_provider()
    {
        $provider = new HorizonServiceProvider($this->app);
        $provider->boot();

        // Test that the provider boots without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_define_horizon_gate()
    {
        $provider = new HorizonServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test that the gate is defined
        $this->assertTrue(Gate::has('viewHorizon'));
    }

    /**
     * @test
     */
    public function it_can_check_horizon_access_for_authorized_user()
    {
        $provider = new HorizonServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with authorized user
        $user = (object) ['email' => 'admin@example.com'];
        $this->assertFalse(Gate::allows('viewHorizon', $user));
    }

    /**
     * @test
     */
    public function it_can_check_horizon_access_for_unauthorized_user()
    {
        $provider = new HorizonServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with unauthorized user
        $user = (object) ['email' => 'user@example.com'];
        $this->assertFalse(Gate::allows('viewHorizon', $user));
    }

    /**
     * @test
     */
    public function it_can_check_horizon_access_for_null_user()
    {
        $provider = new HorizonServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with null user
        $this->assertFalse(Gate::allows('viewHorizon', null));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

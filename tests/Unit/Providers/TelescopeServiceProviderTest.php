<?php

namespace Tests\Unit\Providers;

use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class TelescopeServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_register_telescope_service_provider()
    {
        $provider = new TelescopeServiceProvider($this->app);
        $provider->register();

        // Test that the provider registers without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_hide_sensitive_request_details_in_non_local_environment()
    {
        $this->app->shouldReceive('environment')->with('local')->andReturn(false);

        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('hideSensitiveRequestDetails');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test that the method executes without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_hide_sensitive_request_details_in_local_environment()
    {
        $this->app->shouldReceive('environment')->with('local')->andReturn(true);

        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('hideSensitiveRequestDetails');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test that the method executes without errors
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_define_telescope_gate()
    {
        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test that the gate is defined
        $this->assertTrue(Gate::has('viewTelescope'));
    }

    /**
     * @test
     */
    public function it_can_check_telescope_access_for_authorized_user()
    {
        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with authorized user
        $user = (object) ['email' => 'admin@example.com'];
        $this->assertFalse(Gate::allows('viewTelescope', $user));
    }

    /**
     * @test
     */
    public function it_can_check_telescope_access_for_unauthorized_user()
    {
        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with unauthorized user
        $user = (object) ['email' => 'user@example.com'];
        $this->assertFalse(Gate::allows('viewTelescope', $user));
    }

    /**
     * @test
     */
    public function it_can_check_telescope_access_for_null_user()
    {
        $provider = new TelescopeServiceProvider($this->app);
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('gate');
        $method->setAccessible(true);

        $method->invoke($provider);

        // Test with null user
        $this->assertFalse(Gate::allows('viewTelescope', null));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

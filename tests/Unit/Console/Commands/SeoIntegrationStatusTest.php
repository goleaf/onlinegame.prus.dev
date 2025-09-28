<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SeoIntegrationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SeoIntegrationStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_seo_integration_status_command()
    {
        $command = new SeoIntegrationStatus();

        $this->assertInstanceOf(SeoIntegrationStatus::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_options()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'all',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_meta_tags()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'meta_tags',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_structured_data()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'structured_data',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_sitemap()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'sitemap',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_robots()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'robots',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_canonical()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'canonical',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_og_tags()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'og_tags',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_twitter_cards()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'twitter_cards',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_schema_markup()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--type' => 'schema_markup',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_output()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--output' => 'file',
            '--file' => 'seo_integration_status.json',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_verbose()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--verbose' => true,
            '--type' => 'meta_tags',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_quiet()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--quiet' => true,
            '--type' => 'structured_data',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_no_interaction()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--no-interaction' => true,
            '--type' => 'sitemap',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_force()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle([
            '--force' => true,
            '--type' => 'robots',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_integration_status_command_with_help()
    {
        $command = new SeoIntegrationStatus();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SeoValidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SeoValidateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_seo_validate_command()
    {
        $command = new SeoValidate();

        $this->assertInstanceOf(SeoValidate::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command()
    {
        $command = new SeoValidate();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_options()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'all',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_meta_tags()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'meta_tags',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_structured_data()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'structured_data',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_sitemap()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'sitemap',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_robots()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'robots',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_canonical()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'canonical',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_og_tags()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'og_tags',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_twitter_cards()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'twitter_cards',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_schema_markup()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--type' => 'schema_markup',
            '--format' => 'json',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_output()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--output' => 'file',
            '--file' => 'seo_validation.json',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_verbose()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--verbose' => true,
            '--type' => 'meta_tags',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_quiet()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--quiet' => true,
            '--type' => 'structured_data',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_no_interaction()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--no-interaction' => true,
            '--type' => 'sitemap',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_force()
    {
        $command = new SeoValidate();
        $result = $command->handle([
            '--force' => true,
            '--type' => 'robots',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_seo_validate_command_with_help()
    {
        $command = new SeoValidate();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Utilities;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use LaraUtilX\Utilities\ConfigUtil;
use Tests\TestCase;

class ConfigUtilTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_get_all_app_settings()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getAllAppSettings();

        $this->assertEquals(['name' => 'Test App', 'env' => 'testing'], $result);
    }

    /**
     * @test
     */
    public function it_can_get_all_settings_with_app_path()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getAllSettings(config('app'), 'name');

        $this->assertEquals('Test App', $result);
    }

    /**
     * @test
     */
    public function it_can_get_all_settings_with_storage_path()
    {
        Storage::shouldReceive('exists')
            ->once()
            ->with('config/settings.json')
            ->andReturn(true);

        Storage::shouldReceive('get')
            ->once()
            ->with('config/settings.json')
            ->andReturn('{"setting1": "value1", "setting2": "value2"}');

        $util = new ConfigUtil();
        $result = $util->getAllSettings('config/settings.json');

        $this->assertEquals(['setting1' => 'value1', 'setting2' => 'value2'], $result);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_storage_file_does_not_exist()
    {
        Storage::shouldReceive('exists')
            ->once()
            ->with('config/settings.json')
            ->andReturn(false);

        $util = new ConfigUtil();
        $result = $util->getAllSettings('config/settings.json');

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function it_can_get_specific_setting()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getSetting('name');

        $this->assertEquals('Test App', $result);
    }

    /**
     * @test
     */
    public function it_returns_null_for_non_existent_setting()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getSetting('non_existent');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_can_set_setting()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        Storage::shouldReceive('put')
            ->once()
            ->with(
                storage_path('app/config/settings.json'),
                '{"name":"Test App","env":"testing","new_setting":"new_value"}'
            );

        $util = new ConfigUtil();
        $util->setSetting('new_setting', 'new_value');

        $this->assertTrue(true);  // Assertion passes if no exception is thrown
    }

    /**
     * @test
     */
    public function it_can_update_existing_setting()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        Storage::shouldReceive('put')
            ->once()
            ->with(
                storage_path('app/config/settings.json'),
                '{"name":"Updated App","env":"testing"}'
            );

        $util = new ConfigUtil();
        $util->setSetting('name', 'Updated App');

        $this->assertTrue(true);  // Assertion passes if no exception is thrown
    }

    /**
     * @test
     */
    public function it_handles_json_decode_errors_gracefully()
    {
        Storage::shouldReceive('exists')
            ->once()
            ->with('config/settings.json')
            ->andReturn(true);

        Storage::shouldReceive('get')
            ->once()
            ->with('config/settings.json')
            ->andReturn('invalid json');

        $util = new ConfigUtil();
        $result = $util->getAllSettings('config/settings.json');

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function it_can_get_all_settings_with_null_path()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getAllSettings();

        $this->assertEquals(['name' => 'Test App', 'env' => 'testing'], $result);
    }

    /**
     * @test
     */
    public function it_can_get_all_settings_with_null_key()
    {
        Config::shouldReceive('get')
            ->once()
            ->with('app')
            ->andReturn(['name' => 'Test App', 'env' => 'testing']);

        $util = new ConfigUtil();
        $result = $util->getAllSettings(config('app'), null);

        $this->assertNull($result);
    }
}

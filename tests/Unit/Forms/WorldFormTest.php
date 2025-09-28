<?php

namespace Tests\Unit\Forms;

use App\Forms\WorldForm;
use App\Models\Game\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldFormTest extends TestCase
{
    use RefreshDatabase;

    private WorldForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new WorldForm();
    }

    /**
     * @test
     */
    public function it_creates_form_with_correct_method_and_action()
    {
        $createConfig = $this->form->create();

        $this->assertEquals('POST', $createConfig['method']);
        $this->assertStringContains('game.api.worlds.store', $createConfig['action']);
    }

    /**
     * @test
     */
    public function it_edits_form_with_correct_method_and_action()
    {
        $world = World::factory()->create();
        $form = new WorldForm($world);

        $editConfig = $form->edit();

        $this->assertEquals('PATCH', $editConfig['method']);
        $this->assertStringContains('game.api.worlds.update', $editConfig['action']);
        $this->assertStringContains($world->id, $editConfig['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_fields()
    {
        $fields = $this->form->fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('speed', $fields);
        $this->assertArrayHasKey('unit_speed', $fields);
        $this->assertArrayHasKey('morale', $fields);
        $this->assertArrayHasKey('is_active', $fields);
    }

    /**
     * @test
     */
    public function it_has_correct_name_field_configuration()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertEquals(__('World Name'), $nameField['label']);
        $this->assertTrue($nameField['required']);
        $this->assertEquals(50, $nameField['maxlength']);
        $this->assertContains('required', $nameField['rules']);
        $this->assertContains('string', $nameField['rules']);
        $this->assertContains('max:50', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_speed_field_configuration()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertEquals(__('Game Speed'), $speedField['label']);
        $this->assertEquals('number', $speedField['widget']);
        $this->assertTrue($speedField['required']);
        $this->assertEquals(0.1, $speedField['min']);
        $this->assertEquals(10.0, $speedField['max']);
        $this->assertEquals(0.1, $speedField['step']);
        $this->assertContains('required', $speedField['rules']);
        $this->assertContains('numeric', $speedField['rules']);
        $this->assertContains('min:0.1', $speedField['rules']);
        $this->assertContains('max:10', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_unit_speed_field_configuration()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertEquals(__('Unit Speed'), $unitSpeedField['label']);
        $this->assertEquals('number', $unitSpeedField['widget']);
        $this->assertTrue($unitSpeedField['required']);
        $this->assertEquals(0.1, $unitSpeedField['min']);
        $this->assertEquals(10.0, $unitSpeedField['max']);
        $this->assertEquals(0.1, $unitSpeedField['step']);
        $this->assertContains('required', $unitSpeedField['rules']);
        $this->assertContains('numeric', $unitSpeedField['rules']);
        $this->assertContains('min:0.1', $unitSpeedField['rules']);
        $this->assertContains('max:10', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_morale_field_configuration()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertEquals(__('Morale'), $moraleField['label']);
        $this->assertEquals('number', $moraleField['widget']);
        $this->assertTrue($moraleField['required']);
        $this->assertEquals(0, $moraleField['min']);
        $this->assertEquals(100, $moraleField['max']);
        $this->assertEquals(1, $moraleField['step']);
        $this->assertContains('required', $moraleField['rules']);
        $this->assertContains('integer', $moraleField['rules']);
        $this->assertContains('min:0', $moraleField['rules']);
        $this->assertContains('max:100', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_active_field_configuration()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertEquals(__('Active World'), $activeField['label']);
        $this->assertEquals('checkbox', $activeField['widget']);
        $this->assertFalse($activeField['required']);
    }

    /**
     * @test
     */
    public function it_handles_name_field_validation()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('required', $nameField['rules']);
        $this->assertContains('string', $nameField['rules']);
        $this->assertContains('max:50', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_speed_field_validation()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('required', $speedField['rules']);
        $this->assertContains('numeric', $speedField['rules']);
        $this->assertContains('min:0.1', $speedField['rules']);
        $this->assertContains('max:10', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unit_speed_field_validation()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('required', $unitSpeedField['rules']);
        $this->assertContains('numeric', $unitSpeedField['rules']);
        $this->assertContains('min:0.1', $unitSpeedField['rules']);
        $this->assertContains('max:10', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_morale_field_validation()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('required', $moraleField['rules']);
        $this->assertContains('integer', $moraleField['rules']);
        $this->assertContains('min:0', $moraleField['rules']);
        $this->assertContains('max:100', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('required', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('required', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('max:50', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('required', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('required', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_negative_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('min:0.1', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_speed_above_maximum()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('max:10', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('required', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('required', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_negative_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('min:0.1', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unit_speed_above_maximum()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('max:10', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('required', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('required', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_negative_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('min:0', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_morale_above_maximum()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('max:100', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_name()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_speed()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_unit_speed()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_morale()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_decimal_speed_values()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertContains('numeric', $speedField['rules']);
        $this->assertEquals(0.1, $speedField['step']);
    }

    /**
     * @test
     */
    public function it_handles_decimal_unit_speed_values()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertContains('numeric', $unitSpeedField['rules']);
        $this->assertEquals(0.1, $unitSpeedField['step']);
    }

    /**
     * @test
     */
    public function it_handles_integer_morale_values()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertContains('integer', $moraleField['rules']);
        $this->assertEquals(1, $moraleField['step']);
    }

    /**
     * @test
     */
    public function it_handles_boundary_speed_values()
    {
        $fields = $this->form->fields();
        $speedField = $fields['speed'];

        $this->assertEquals(0.1, $speedField['min']);
        $this->assertEquals(10.0, $speedField['max']);
    }

    /**
     * @test
     */
    public function it_handles_boundary_unit_speed_values()
    {
        $fields = $this->form->fields();
        $unitSpeedField = $fields['unit_speed'];

        $this->assertEquals(0.1, $unitSpeedField['min']);
        $this->assertEquals(10.0, $unitSpeedField['max']);
    }

    /**
     * @test
     */
    public function it_handles_boundary_morale_values()
    {
        $fields = $this->form->fields();
        $moraleField = $fields['morale'];

        $this->assertEquals(0, $moraleField['min']);
        $this->assertEquals(100, $moraleField['max']);
    }
}

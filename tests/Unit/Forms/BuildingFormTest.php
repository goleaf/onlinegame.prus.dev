<?php

namespace Tests\Unit\Forms;

use App\Forms\BuildingForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BuildingFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_building_form()
    {
        $form = new BuildingForm();

        $this->assertInstanceOf(BuildingForm::class, $form);
    }

    /**
     * @test
     */
    public function it_can_validate_building_form()
    {
        $form = new BuildingForm();
        $result = $form->validate([
            'name' => 'Test Building',
            'type' => 'resource',
            'level' => 1,
            'cost' => ['wood' => 100, 'clay' => 50],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_validate_building_form_with_invalid_data()
    {
        $form = new BuildingForm();
        $result = $form->validate([
            'name' => '',
            'type' => 'invalid',
            'level' => -1,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_fields()
    {
        $form = new BuildingForm();
        $fields = $form->getFields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('type', $fields);
        $this->assertArrayHasKey('level', $fields);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_rules()
    {
        $form = new BuildingForm();
        $rules = $form->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('level', $rules);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_messages()
    {
        $form = new BuildingForm();
        $messages = $form->getMessages();

        $this->assertIsArray($messages);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_attributes()
    {
        $form = new BuildingForm();
        $attributes = $form->getAttributes();

        $this->assertIsArray($attributes);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_options()
    {
        $form = new BuildingForm();
        $options = $form->getOptions();

        $this->assertIsArray($options);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_validation_rules()
    {
        $form = new BuildingForm();
        $validationRules = $form->getValidationRules();

        $this->assertIsArray($validationRules);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_validation_messages()
    {
        $form = new BuildingForm();
        $validationMessages = $form->getValidationMessages();

        $this->assertIsArray($validationMessages);
    }

    /**
     * @test
     */
    public function it_can_get_building_form_validation_attributes()
    {
        $form = new BuildingForm();
        $validationAttributes = $form->getValidationAttributes();

        $this->assertIsArray($validationAttributes);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

<?php

namespace Tests\Unit\Forms;

use App\Forms\VillageForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VillageFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_village_form()
    {
        $form = new VillageForm();

        $this->assertInstanceOf(VillageForm::class, $form);
    }

    /**
     * @test
     */
    public function it_can_validate_village_form()
    {
        $form = new VillageForm();
        $result = $form->validate([
            'name' => 'Test Village',
            'player_id' => 1,
            'x_coordinate' => 100,
            'y_coordinate' => 100,
            'world_id' => 1,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_validate_village_form_with_invalid_data()
    {
        $form = new VillageForm();
        $result = $form->validate([
            'name' => '',
            'player_id' => -1,
            'x_coordinate' => -1,
            'y_coordinate' => -1,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_fields()
    {
        $form = new VillageForm();
        $fields = $form->getFields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('player_id', $fields);
        $this->assertArrayHasKey('x_coordinate', $fields);
        $this->assertArrayHasKey('y_coordinate', $fields);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_rules()
    {
        $form = new VillageForm();
        $rules = $form->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('player_id', $rules);
        $this->assertArrayHasKey('x_coordinate', $rules);
        $this->assertArrayHasKey('y_coordinate', $rules);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_messages()
    {
        $form = new VillageForm();
        $messages = $form->getMessages();

        $this->assertIsArray($messages);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_attributes()
    {
        $form = new VillageForm();
        $attributes = $form->getAttributes();

        $this->assertIsArray($attributes);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_options()
    {
        $form = new VillageForm();
        $options = $form->getOptions();

        $this->assertIsArray($options);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_validation_rules()
    {
        $form = new VillageForm();
        $validationRules = $form->getValidationRules();

        $this->assertIsArray($validationRules);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_validation_messages()
    {
        $form = new VillageForm();
        $validationMessages = $form->getValidationMessages();

        $this->assertIsArray($validationMessages);
    }

    /**
     * @test
     */
    public function it_can_get_village_form_validation_attributes()
    {
        $form = new VillageForm();
        $validationAttributes = $form->getValidationAttributes();

        $this->assertIsArray($validationAttributes);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

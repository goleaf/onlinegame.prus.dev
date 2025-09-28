<?php

namespace Tests\Unit\Forms;

use App\Forms\PlayerForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlayerFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_player_form()
    {
        $form = new PlayerForm();

        $this->assertInstanceOf(PlayerForm::class, $form);
    }

    /**
     * @test
     */
    public function it_can_validate_player_form()
    {
        $form = new PlayerForm();
        $result = $form->validate([
            'name' => 'Test Player',
            'email' => 'test@example.com',
            'world_id' => 1,
            'alliance_id' => 1,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * @test
     */
    public function it_can_validate_player_form_with_invalid_data()
    {
        $form = new PlayerForm();
        $result = $form->validate([
            'name' => '',
            'email' => 'invalid-email',
            'world_id' => -1,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_fields()
    {
        $form = new PlayerForm();
        $fields = $form->getFields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('world_id', $fields);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_rules()
    {
        $form = new PlayerForm();
        $rules = $form->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('world_id', $rules);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_messages()
    {
        $form = new PlayerForm();
        $messages = $form->getMessages();

        $this->assertIsArray($messages);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_attributes()
    {
        $form = new PlayerForm();
        $attributes = $form->getAttributes();

        $this->assertIsArray($attributes);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_options()
    {
        $form = new PlayerForm();
        $options = $form->getOptions();

        $this->assertIsArray($options);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_validation_rules()
    {
        $form = new PlayerForm();
        $validationRules = $form->getValidationRules();

        $this->assertIsArray($validationRules);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_validation_messages()
    {
        $form = new PlayerForm();
        $validationMessages = $form->getValidationMessages();

        $this->assertIsArray($validationMessages);
    }

    /**
     * @test
     */
    public function it_can_get_player_form_validation_attributes()
    {
        $form = new PlayerForm();
        $validationAttributes = $form->getValidationAttributes();

        $this->assertIsArray($validationAttributes);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

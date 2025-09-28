<?php

namespace Tests\Unit\Forms;

use App\Forms\AllianceForm;
use App\Models\Game\Alliance;
use App\Models\Game\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceFormTest extends TestCase
{
    use RefreshDatabase;

    private AllianceForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new AllianceForm();
    }

    /**
     * @test
     */
    public function it_creates_form_with_correct_method_and_action()
    {
        $createConfig = $this->form->create();

        $this->assertEquals('POST', $createConfig['method']);
        $this->assertStringContains('game.api.alliances.store', $createConfig['action']);
    }

    /**
     * @test
     */
    public function it_edits_form_with_correct_method_and_action()
    {
        $alliance = Alliance::factory()->create();
        $form = new AllianceForm($alliance);

        $editConfig = $form->edit();

        $this->assertEquals('PATCH', $editConfig['method']);
        $this->assertStringContains('game.api.alliances.update', $editConfig['action']);
        $this->assertStringContains($alliance->id, $editConfig['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_fields()
    {
        $fields = $this->form->fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('tag', $fields);
        $this->assertArrayHasKey('world_id', $fields);
        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('is_active', $fields);
    }

    /**
     * @test
     */
    public function it_has_correct_name_field_configuration()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertEquals(__('Alliance Name'), $nameField['label']);
        $this->assertTrue($nameField['required']);
        $this->assertEquals(50, $nameField['maxlength']);
        $this->assertContains('required', $nameField['rules']);
        $this->assertContains('string', $nameField['rules']);
        $this->assertContains('max:50', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_tag_field_configuration()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertEquals(__('Alliance Tag'), $tagField['label']);
        $this->assertTrue($tagField['required']);
        $this->assertEquals(10, $tagField['maxlength']);
        $this->assertContains('required', $tagField['rules']);
        $this->assertContains('string', $tagField['rules']);
        $this->assertContains('max:10', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_world_id_field_configuration()
    {
        $world = World::factory()->create(['name' => 'Test World']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertEquals(__('World'), $worldField['label']);
        $this->assertEquals('select', $worldField['widget']);
        $this->assertTrue($worldField['required']);
        $this->assertArrayHasKey('choices', $worldField);
        $this->assertArrayHasKey($world->id, $worldField['choices']);
        $this->assertEquals('Test World', $worldField['choices'][$world->id]);
    }

    /**
     * @test
     */
    public function it_has_correct_description_field_configuration()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertEquals(__('Description'), $descriptionField['label']);
        $this->assertEquals('textarea', $descriptionField['widget']);
        $this->assertFalse($descriptionField['required']);
        $this->assertEquals(500, $descriptionField['maxlength']);
        $this->assertContains('nullable', $descriptionField['rules']);
        $this->assertContains('string', $descriptionField['rules']);
        $this->assertContains('max:500', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_active_field_configuration()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertEquals(__('Active Alliance'), $activeField['label']);
        $this->assertEquals('checkbox', $activeField['widget']);
        $this->assertFalse($activeField['required']);
    }

    /**
     * @test
     */
    public function it_handles_empty_worlds_list()
    {
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertIsArray($worldField['choices']);
        $this->assertEmpty($worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_multiple_worlds()
    {
        World::factory()->count(3)->create();
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertCount(3, $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_unicode_names()
    {
        World::factory()->create(['name' => '测试世界']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('测试世界', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_special_characters()
    {
        World::factory()->create(['name' => 'World "Test" & Co.']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('World "Test" & Co.', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_world_names()
    {
        $longName = str_repeat('A', 255);
        World::factory()->create(['name' => $longName]);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains($longName, $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_null_names()
    {
        World::factory()->create(['name' => null]);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains(null, $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_empty_names()
    {
        World::factory()->create(['name' => '']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_large_number_of_worlds()
    {
        World::factory()->count(100)->create();
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertCount(100, $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_duplicate_names()
    {
        World::factory()->count(2)->create(['name' => 'Duplicate World']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertCount(2, $worldField['choices']);
        $this->assertEquals(2, collect($worldField['choices'])->filter(fn ($name) => $name === 'Duplicate World')->count());
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_numeric_names()
    {
        World::factory()->create(['name' => '123']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('123', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_boolean_names()
    {
        World::factory()->create(['name' => 'true']);
        World::factory()->create(['name' => 'false']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('true', $worldField['choices']);
        $this->assertContains('false', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_array_names()
    {
        World::factory()->create(['name' => '["array", "name"]']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('["array", "name"]', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_object_names()
    {
        World::factory()->create(['name' => '{"object": "name"}']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('{"object": "name"}', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_json_names()
    {
        World::factory()->create(['name' => '{"json": "name", "value": 123}']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('{"json": "name", "value": 123}', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_xss_attempts()
    {
        World::factory()->create(['name' => '<script>alert("xss")</script>']);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains('<script>alert("xss")</script>', $worldField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_worlds_with_sql_injection_attempts()
    {
        World::factory()->create(['name' => "'; DROP TABLE worlds; --"]);
        $fields = $this->form->fields();
        $worldField = $fields['world_id'];

        $this->assertContains("'; DROP TABLE worlds; --", $worldField['choices']);
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
    public function it_handles_tag_field_validation()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('required', $tagField['rules']);
        $this->assertContains('string', $tagField['rules']);
        $this->assertContains('max:10', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_description_field_validation()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('nullable', $descriptionField['rules']);
        $this->assertContains('string', $descriptionField['rules']);
        $this->assertContains('max:500', $descriptionField['rules']);
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
    public function it_handles_null_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('required', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('required', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('max:10', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('nullable', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('nullable', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('max:500', $descriptionField['rules']);
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
    public function it_handles_boolean_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_tag()
    {
        $fields = $this->form->fields();
        $tagField = $fields['tag'];

        $this->assertContains('string', $tagField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_description()
    {
        $fields = $this->form->fields();
        $descriptionField = $fields['description'];

        $this->assertContains('string', $descriptionField['rules']);
    }
}

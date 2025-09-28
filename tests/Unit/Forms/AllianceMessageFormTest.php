<?php

namespace Tests\Unit\Forms;

use App\Forms\AllianceMessageForm;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceMessageFormTest extends TestCase
{
    use RefreshDatabase;

    private AllianceMessageForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new AllianceMessageForm();
    }

    /**
     * @test
     */
    public function it_returns_correct_create_configuration()
    {
        $config = $this->form->create();

        $this->assertEquals('POST', $config['method']);
        $this->assertStringContainsString('game.api.alliance-messages.store', $config['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_edit_configuration()
    {
        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create();
        $this->form->setModel(['alliance_id' => $alliance->id, 'player_id' => $player->id]);

        $config = $this->form->edit();

        $this->assertEquals('PATCH', $config['method']);
        $this->assertStringContainsString('game.api.alliance-messages.update', $config['action']);
        $this->assertStringContainsString($alliance->id, $config['action']);
        $this->assertStringContainsString($player->id, $config['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_fields_configuration()
    {
        $fields = $this->form->fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('alliance_id', $fields);
        $this->assertArrayHasKey('player_id', $fields);
        $this->assertArrayHasKey('subject', $fields);
        $this->assertArrayHasKey('content', $fields);
        $this->assertArrayHasKey('message_type', $fields);
        $this->assertArrayHasKey('is_important', $fields);
        $this->assertArrayHasKey('is_pinned', $fields);
    }

    /**
     * @test
     */
    public function it_has_correct_alliance_id_field_configuration()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];

        $this->assertEquals('Alliance', $allianceIdField['label']);
        $this->assertEquals('select', $allianceIdField['widget']);
        $this->assertTrue($allianceIdField['required']);
        $this->assertArrayHasKey('choices', $allianceIdField);
        $this->assertIsArray($allianceIdField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_player_id_field_configuration()
    {
        $fields = $this->form->fields();
        $playerIdField = $fields['player_id'];

        $this->assertEquals('Author', $playerIdField['label']);
        $this->assertEquals('select', $playerIdField['widget']);
        $this->assertTrue($playerIdField['required']);
        $this->assertArrayHasKey('choices', $playerIdField);
        $this->assertIsArray($playerIdField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_subject_field_configuration()
    {
        $fields = $this->form->fields();
        $subjectField = $fields['subject'];

        $this->assertEquals('Subject', $subjectField['label']);
        $this->assertTrue($subjectField['required']);
        $this->assertEquals(100, $subjectField['maxlength']);
        $this->assertIsArray($subjectField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_content_field_configuration()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertEquals('Message Content', $contentField['label']);
        $this->assertEquals('textarea', $contentField['widget']);
        $this->assertTrue($contentField['required']);
        $this->assertEquals(2000, $contentField['maxlength']);
        $this->assertIsArray($contentField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_message_type_field_configuration()
    {
        $fields = $this->form->fields();
        $messageTypeField = $fields['message_type'];

        $this->assertEquals('Message Type', $messageTypeField['label']);
        $this->assertEquals('select', $messageTypeField['widget']);
        $this->assertTrue($messageTypeField['required']);
        $this->assertArrayHasKey('choices', $messageTypeField);
        $this->assertIsArray($messageTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_important_field_configuration()
    {
        $fields = $this->form->fields();
        $importantField = $fields['is_important'];

        $this->assertEquals('Important Message', $importantField['label']);
        $this->assertEquals('checkbox', $importantField['widget']);
        $this->assertArrayNotHasKey('required', $importantField);
        $this->assertIsArray($importantField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_pinned_field_configuration()
    {
        $fields = $this->form->fields();
        $pinnedField = $fields['is_pinned'];

        $this->assertEquals('Pinned Message', $pinnedField['label']);
        $this->assertEquals('checkbox', $pinnedField['widget']);
        $this->assertArrayNotHasKey('required', $pinnedField);
        $this->assertIsArray($pinnedField['rules']);
    }

    /**
     * @test
     */
    public function it_includes_alliance_choices()
    {
        $alliance1 = Alliance::factory()->create(['name' => 'Alliance 1']);
        $alliance2 = Alliance::factory()->create(['name' => 'Alliance 2']);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance1->id, $allianceChoices);
        $this->assertArrayHasKey($alliance2->id, $allianceChoices);
        $this->assertEquals('Alliance 1', $allianceChoices[$alliance1->id]);
        $this->assertEquals('Alliance 2', $allianceChoices[$alliance2->id]);
    }

    /**
     * @test
     */
    public function it_includes_player_choices()
    {
        $player1 = Player::factory()->create(['name' => 'Player 1']);
        $player2 = Player::factory()->create(['name' => 'Player 2']);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player1->id, $playerChoices);
        $this->assertArrayHasKey($player2->id, $playerChoices);
        $this->assertEquals('Player 1', $playerChoices[$player1->id]);
        $this->assertEquals('Player 2', $playerChoices[$player2->id]);
    }

    /**
     * @test
     */
    public function it_includes_message_type_choices()
    {
        $fields = $this->form->fields();
        $messageTypeChoices = $fields['message_type']['choices'];

        $this->assertArrayHasKey('general', $messageTypeChoices);
        $this->assertArrayHasKey('announcement', $messageTypeChoices);
        $this->assertArrayHasKey('strategy', $messageTypeChoices);
        $this->assertArrayHasKey('recruitment', $messageTypeChoices);
        $this->assertEquals('General', $messageTypeChoices['general']);
        $this->assertEquals('Announcement', $messageTypeChoices['announcement']);
        $this->assertEquals('Strategy', $messageTypeChoices['strategy']);
        $this->assertEquals('Recruitment', $messageTypeChoices['recruitment']);
    }

    /**
     * @test
     */
    public function it_handles_empty_alliance_choices()
    {
        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertIsArray($allianceChoices);
        $this->assertEmpty($allianceChoices);
    }

    /**
     * @test
     */
    public function it_handles_empty_player_choices()
    {
        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertIsArray($playerChoices);
        $this->assertEmpty($playerChoices);
    }

    /**
     * @test
     */
    public function it_handles_multiple_alliances()
    {
        $alliances = Alliance::factory()->count(5)->create();

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertCount(5, $allianceChoices);
        foreach ($alliances as $alliance) {
            $this->assertArrayHasKey($alliance->id, $allianceChoices);
            $this->assertEquals($alliance->name, $allianceChoices[$alliance->id]);
        }
    }

    /**
     * @test
     */
    public function it_handles_multiple_players()
    {
        $players = Player::factory()->count(5)->create();

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertCount(5, $playerChoices);
        foreach ($players as $player) {
            $this->assertArrayHasKey($player->id, $playerChoices);
            $this->assertEquals($player->name, $playerChoices[$player->id]);
        }
    }

    /**
     * @test
     */
    public function it_handles_alliances_with_special_characters()
    {
        $alliance = Alliance::factory()->create(['name' => 'Alliance with "quotes" and \'apostrophes\'']);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance->id, $allianceChoices);
        $this->assertEquals('Alliance with "quotes" and \'apostrophes\'', $allianceChoices[$alliance->id]);
    }

    /**
     * @test
     */
    public function it_handles_players_with_special_characters()
    {
        $player = Player::factory()->create(['name' => 'Player with "quotes" and \'apostrophes\'']);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player->id, $playerChoices);
        $this->assertEquals('Player with "quotes" and \'apostrophes\'', $playerChoices[$player->id]);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_alliance_names()
    {
        $alliance = Alliance::factory()->create(['name' => '联盟 1']);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance->id, $allianceChoices);
        $this->assertEquals('联盟 1', $allianceChoices[$alliance->id]);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters_in_player_names()
    {
        $player = Player::factory()->create(['name' => '玩家 1']);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player->id, $playerChoices);
        $this->assertEquals('玩家 1', $playerChoices[$player->id]);
    }

    /**
     * @test
     */
    public function it_handles_long_alliance_names()
    {
        $longName = str_repeat('A', 100);
        $alliance = Alliance::factory()->create(['name' => $longName]);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance->id, $allianceChoices);
        $this->assertEquals($longName, $allianceChoices[$alliance->id]);
    }

    /**
     * @test
     */
    public function it_handles_long_player_names()
    {
        $longName = str_repeat('B', 100);
        $player = Player::factory()->create(['name' => $longName]);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player->id, $playerChoices);
        $this->assertEquals($longName, $playerChoices[$player->id]);
    }

    /**
     * @test
     */
    public function it_handles_numeric_alliance_names()
    {
        $alliance = Alliance::factory()->create(['name' => '123']);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance->id, $allianceChoices);
        $this->assertEquals('123', $allianceChoices[$alliance->id]);
    }

    /**
     * @test
     */
    public function it_handles_numeric_player_names()
    {
        $player = Player::factory()->create(['name' => '456']);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player->id, $playerChoices);
        $this->assertEquals('456', $playerChoices[$player->id]);
    }

    /**
     * @test
     */
    public function it_handles_empty_alliance_names()
    {
        $alliance = Alliance::factory()->create(['name' => '']);

        $fields = $this->form->fields();
        $allianceChoices = $fields['alliance_id']['choices'];

        $this->assertArrayHasKey($alliance->id, $allianceChoices);
        $this->assertEquals('', $allianceChoices[$alliance->id]);
    }

    /**
     * @test
     */
    public function it_handles_empty_player_names()
    {
        $player = Player::factory()->create(['name' => '']);

        $fields = $this->form->fields();
        $playerChoices = $fields['player_id']['choices'];

        $this->assertArrayHasKey($player->id, $playerChoices);
        $this->assertEquals('', $playerChoices[$player->id]);
    }

    /**
     * @test
     */
    public function it_handles_alliance_id_validation_rules()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];

        $this->assertContains('required', $allianceIdField['rules']);
        $this->assertContains('exists:alliances,id', $allianceIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_player_id_validation_rules()
    {
        $fields = $this->form->fields();
        $playerIdField = $fields['player_id'];

        $this->assertContains('required', $playerIdField['rules']);
        $this->assertContains('exists:players,id', $playerIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_subject_validation_rules()
    {
        $fields = $this->form->fields();
        $subjectField = $fields['subject'];

        $this->assertContains('required', $subjectField['rules']);
        $this->assertContains('string', $subjectField['rules']);
        $this->assertContains('max:100', $subjectField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_content_validation_rules()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('required', $contentField['rules']);
        $this->assertContains('string', $contentField['rules']);
        $this->assertContains('max:2000', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_message_type_validation_rules()
    {
        $fields = $this->form->fields();
        $messageTypeField = $fields['message_type'];

        $this->assertContains('required', $messageTypeField['rules']);
        $this->assertContains('in:general,announcement,strategy,recruitment', $messageTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_is_important_validation_rules()
    {
        $fields = $this->form->fields();
        $importantField = $fields['is_important'];

        $this->assertContains('boolean', $importantField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_is_pinned_validation_rules()
    {
        $fields = $this->form->fields();
        $pinnedField = $fields['is_pinned'];

        $this->assertContains('boolean', $pinnedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_subject_length_limits()
    {
        $fields = $this->form->fields();
        $subjectField = $fields['subject'];

        $this->assertEquals(100, $subjectField['maxlength']);
        $this->assertContains('max:100', $subjectField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_content_length_limits()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertEquals(2000, $contentField['maxlength']);
        $this->assertContains('max:2000', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_form_methods()
    {
        $createConfig = $this->form->create();
        $this->assertEquals('POST', $createConfig['method']);

        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create();
        $this->form->setModel(['alliance_id' => $alliance->id, 'player_id' => $player->id]);
        $editConfig = $this->form->edit();
        $this->assertEquals('PATCH', $editConfig['method']);
    }

    /**
     * @test
     */
    public function it_handles_form_actions()
    {
        $createConfig = $this->form->create();
        $this->assertStringContainsString('game.api.alliance-messages.store', $createConfig['action']);

        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create();
        $this->form->setModel(['alliance_id' => $alliance->id, 'player_id' => $player->id]);
        $editConfig = $this->form->edit();
        $this->assertStringContainsString('game.api.alliance-messages.update', $editConfig['action']);
        $this->assertStringContainsString($alliance->id, $editConfig['action']);
        $this->assertStringContainsString($player->id, $editConfig['action']);
    }

    /**
     * @test
     */
    public function it_handles_select_widgets()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];
        $messageTypeField = $fields['message_type'];

        $this->assertEquals('select', $allianceIdField['widget']);
        $this->assertEquals('select', $playerIdField['widget']);
        $this->assertEquals('select', $messageTypeField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_textarea_widget()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertEquals('textarea', $contentField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_checkbox_widgets()
    {
        $fields = $this->form->fields();
        $importantField = $fields['is_important'];
        $pinnedField = $fields['is_pinned'];

        $this->assertEquals('checkbox', $importantField['widget']);
        $this->assertEquals('checkbox', $pinnedField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_required_fields()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];
        $subjectField = $fields['subject'];
        $contentField = $fields['content'];
        $messageTypeField = $fields['message_type'];

        $this->assertTrue($allianceIdField['required']);
        $this->assertTrue($playerIdField['required']);
        $this->assertTrue($subjectField['required']);
        $this->assertTrue($contentField['required']);
        $this->assertTrue($messageTypeField['required']);
    }

    /**
     * @test
     */
    public function it_handles_optional_fields()
    {
        $fields = $this->form->fields();
        $importantField = $fields['is_important'];
        $pinnedField = $fields['is_pinned'];

        $this->assertArrayNotHasKey('required', $importantField);
        $this->assertArrayNotHasKey('required', $pinnedField);
    }

    /**
     * @test
     */
    public function it_handles_boolean_validation_for_checkboxes()
    {
        $fields = $this->form->fields();
        $importantField = $fields['is_important'];
        $pinnedField = $fields['is_pinned'];

        $this->assertContains('boolean', $importantField['rules']);
        $this->assertContains('boolean', $pinnedField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_exists_validation_for_foreign_keys()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];

        $this->assertContains('exists:alliances,id', $allianceIdField['rules']);
        $this->assertContains('exists:players,id', $playerIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_in_validation_for_message_type()
    {
        $fields = $this->form->fields();
        $messageTypeField = $fields['message_type'];

        $this->assertContains('in:general,announcement,strategy,recruitment', $messageTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_string_validation_for_text_fields()
    {
        $fields = $this->form->fields();
        $subjectField = $fields['subject'];
        $contentField = $fields['content'];

        $this->assertContains('string', $subjectField['rules']);
        $this->assertContains('string', $contentField['rules']);
    }
}

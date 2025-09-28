<?php

namespace Tests\Unit\Forms;

use App\Forms\AllianceInviteForm;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceInviteFormTest extends TestCase
{
    use RefreshDatabase;

    private AllianceInviteForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new AllianceInviteForm();
    }

    /**
     * @test
     */
    public function it_returns_correct_create_configuration()
    {
        $config = $this->form->create();

        $this->assertEquals('POST', $config['method']);
        $this->assertStringContainsString('game.api.alliance-invites.store', $config['action']);
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
        $this->assertStringContainsString('game.api.alliance-invites.update', $config['action']);
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
        $this->assertArrayHasKey('invited_by', $fields);
        $this->assertArrayHasKey('message', $fields);
        $this->assertArrayHasKey('expires_at', $fields);
        $this->assertArrayHasKey('status', $fields);
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

        $this->assertEquals('Player', $playerIdField['label']);
        $this->assertEquals('select', $playerIdField['widget']);
        $this->assertTrue($playerIdField['required']);
        $this->assertArrayHasKey('choices', $playerIdField);
        $this->assertIsArray($playerIdField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_invited_by_field_configuration()
    {
        $fields = $this->form->fields();
        $invitedByField = $fields['invited_by'];

        $this->assertEquals('Invited By', $invitedByField['label']);
        $this->assertEquals('select', $invitedByField['widget']);
        $this->assertTrue($invitedByField['required']);
        $this->assertArrayHasKey('choices', $invitedByField);
        $this->assertIsArray($invitedByField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_message_field_configuration()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertEquals('Invitation Message', $messageField['label']);
        $this->assertEquals('textarea', $messageField['widget']);
        $this->assertFalse($messageField['required']);
        $this->assertEquals(500, $messageField['maxlength']);
        $this->assertIsArray($messageField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_expires_at_field_configuration()
    {
        $fields = $this->form->fields();
        $expiresAtField = $fields['expires_at'];

        $this->assertEquals('Expires At', $expiresAtField['label']);
        $this->assertEquals('datetime-local', $expiresAtField['widget']);
        $this->assertTrue($expiresAtField['required']);
        $this->assertIsArray($expiresAtField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_status_field_configuration()
    {
        $fields = $this->form->fields();
        $statusField = $fields['status'];

        $this->assertEquals('Status', $statusField['label']);
        $this->assertEquals('select', $statusField['widget']);
        $this->assertTrue($statusField['required']);
        $this->assertArrayHasKey('choices', $statusField);
        $this->assertIsArray($statusField['rules']);
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
    public function it_includes_invited_by_choices()
    {
        $player1 = Player::factory()->create(['name' => 'Inviter 1']);
        $player2 = Player::factory()->create(['name' => 'Inviter 2']);

        $fields = $this->form->fields();
        $invitedByChoices = $fields['invited_by']['choices'];

        $this->assertArrayHasKey($player1->id, $invitedByChoices);
        $this->assertArrayHasKey($player2->id, $invitedByChoices);
        $this->assertEquals('Inviter 1', $invitedByChoices[$player1->id]);
        $this->assertEquals('Inviter 2', $invitedByChoices[$player2->id]);
    }

    /**
     * @test
     */
    public function it_includes_status_choices()
    {
        $fields = $this->form->fields();
        $statusChoices = $fields['status']['choices'];

        $this->assertArrayHasKey('pending', $statusChoices);
        $this->assertArrayHasKey('accepted', $statusChoices);
        $this->assertArrayHasKey('declined', $statusChoices);
        $this->assertArrayHasKey('expired', $statusChoices);
        $this->assertEquals('Pending', $statusChoices['pending']);
        $this->assertEquals('Accepted', $statusChoices['accepted']);
        $this->assertEquals('Declined', $statusChoices['declined']);
        $this->assertEquals('Expired', $statusChoices['expired']);
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
    public function it_handles_empty_invited_by_choices()
    {
        $fields = $this->form->fields();
        $invitedByChoices = $fields['invited_by']['choices'];

        $this->assertIsArray($invitedByChoices);
        $this->assertEmpty($invitedByChoices);
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
    public function it_handles_multiple_inviters()
    {
        $players = Player::factory()->count(5)->create();

        $fields = $this->form->fields();
        $invitedByChoices = $fields['invited_by']['choices'];

        $this->assertCount(5, $invitedByChoices);
        foreach ($players as $player) {
            $this->assertArrayHasKey($player->id, $invitedByChoices);
            $this->assertEquals($player->name, $invitedByChoices[$player->id]);
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
    public function it_handles_invited_by_validation_rules()
    {
        $fields = $this->form->fields();
        $invitedByField = $fields['invited_by'];

        $this->assertContains('required', $invitedByField['rules']);
        $this->assertContains('exists:players,id', $invitedByField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_message_validation_rules()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertContains('nullable', $messageField['rules']);
        $this->assertContains('string', $messageField['rules']);
        $this->assertContains('max:500', $messageField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_expires_at_validation_rules()
    {
        $fields = $this->form->fields();
        $expiresAtField = $fields['expires_at'];

        $this->assertContains('required', $expiresAtField['rules']);
        $this->assertContains('date', $expiresAtField['rules']);
        $this->assertContains('after:now', $expiresAtField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_status_validation_rules()
    {
        $fields = $this->form->fields();
        $statusField = $fields['status'];

        $this->assertContains('required', $statusField['rules']);
        $this->assertContains('in:pending,accepted,declined,expired', $statusField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_message_length_limits()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertEquals(500, $messageField['maxlength']);
        $this->assertContains('max:500', $messageField['rules']);
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
        $this->assertStringContainsString('game.api.alliance-invites.store', $createConfig['action']);

        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create();
        $this->form->setModel(['alliance_id' => $alliance->id, 'player_id' => $player->id]);
        $editConfig = $this->form->edit();
        $this->assertStringContainsString('game.api.alliance-invites.update', $editConfig['action']);
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
        $invitedByField = $fields['invited_by'];
        $statusField = $fields['status'];

        $this->assertEquals('select', $allianceIdField['widget']);
        $this->assertEquals('select', $playerIdField['widget']);
        $this->assertEquals('select', $invitedByField['widget']);
        $this->assertEquals('select', $statusField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_textarea_widget()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertEquals('textarea', $messageField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_datetime_local_widget()
    {
        $fields = $this->form->fields();
        $expiresAtField = $fields['expires_at'];

        $this->assertEquals('datetime-local', $expiresAtField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_required_fields()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];
        $invitedByField = $fields['invited_by'];
        $expiresAtField = $fields['expires_at'];
        $statusField = $fields['status'];

        $this->assertTrue($allianceIdField['required']);
        $this->assertTrue($playerIdField['required']);
        $this->assertTrue($invitedByField['required']);
        $this->assertTrue($expiresAtField['required']);
        $this->assertTrue($statusField['required']);
    }

    /**
     * @test
     */
    public function it_handles_optional_fields()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertFalse($messageField['required']);
    }

    /**
     * @test
     */
    public function it_handles_date_validation_for_datetime_field()
    {
        $fields = $this->form->fields();
        $expiresAtField = $fields['expires_at'];

        $this->assertContains('date', $expiresAtField['rules']);
        $this->assertContains('after:now', $expiresAtField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_exists_validation_for_foreign_keys()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];
        $invitedByField = $fields['invited_by'];

        $this->assertContains('exists:alliances,id', $allianceIdField['rules']);
        $this->assertContains('exists:players,id', $playerIdField['rules']);
        $this->assertContains('exists:players,id', $invitedByField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_in_validation_for_status()
    {
        $fields = $this->form->fields();
        $statusField = $fields['status'];

        $this->assertContains('in:pending,accepted,declined,expired', $statusField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_string_validation_for_text_field()
    {
        $fields = $this->form->fields();
        $messageField = $fields['message'];

        $this->assertContains('string', $messageField['rules']);
    }
}

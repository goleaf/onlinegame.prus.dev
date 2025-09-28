<?php

namespace Tests\Unit\Forms;

use App\Forms\AllianceMemberForm;
use App\Models\Game\Alliance;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceMemberFormTest extends TestCase
{
    use RefreshDatabase;

    private AllianceMemberForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new AllianceMemberForm();
    }

    /**
     * @test
     */
    public function it_returns_correct_create_configuration()
    {
        $config = $this->form->create();

        $this->assertEquals('POST', $config['method']);
        $this->assertStringContainsString('game.api.alliance-members.store', $config['action']);
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
        $this->assertStringContainsString('game.api.alliance-members.update', $config['action']);
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
        $this->assertArrayHasKey('role', $fields);
        $this->assertArrayHasKey('joined_at', $fields);
        $this->assertArrayHasKey('is_active', $fields);
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
    public function it_has_correct_role_field_configuration()
    {
        $fields = $this->form->fields();
        $roleField = $fields['role'];

        $this->assertEquals('Role', $roleField['label']);
        $this->assertEquals('select', $roleField['widget']);
        $this->assertTrue($roleField['required']);
        $this->assertArrayHasKey('choices', $roleField);
        $this->assertIsArray($roleField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_joined_at_field_configuration()
    {
        $fields = $this->form->fields();
        $joinedAtField = $fields['joined_at'];

        $this->assertEquals('Joined At', $joinedAtField['label']);
        $this->assertEquals('datetime-local', $joinedAtField['widget']);
        $this->assertTrue($joinedAtField['required']);
        $this->assertIsArray($joinedAtField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_active_field_configuration()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertEquals('Active Member', $activeField['label']);
        $this->assertEquals('checkbox', $activeField['widget']);
        $this->assertArrayNotHasKey('required', $activeField);
        $this->assertIsArray($activeField['rules']);
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
    public function it_includes_role_choices()
    {
        $fields = $this->form->fields();
        $roleChoices = $fields['role']['choices'];

        $this->assertArrayHasKey('member', $roleChoices);
        $this->assertArrayHasKey('officer', $roleChoices);
        $this->assertArrayHasKey('leader', $roleChoices);
        $this->assertEquals('Member', $roleChoices['member']);
        $this->assertEquals('Officer', $roleChoices['officer']);
        $this->assertEquals('Leader', $roleChoices['leader']);
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
    public function it_handles_role_validation_rules()
    {
        $fields = $this->form->fields();
        $roleField = $fields['role'];

        $this->assertContains('required', $roleField['rules']);
        $this->assertContains('in:member,officer,leader', $roleField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_joined_at_validation_rules()
    {
        $fields = $this->form->fields();
        $joinedAtField = $fields['joined_at'];

        $this->assertContains('required', $joinedAtField['rules']);
        $this->assertContains('date', $joinedAtField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_is_active_validation_rules()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertContains('boolean', $activeField['rules']);
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
        $this->assertStringContainsString('game.api.alliance-members.store', $createConfig['action']);

        $alliance = Alliance::factory()->create();
        $player = Player::factory()->create();
        $this->form->setModel(['alliance_id' => $alliance->id, 'player_id' => $player->id]);
        $editConfig = $this->form->edit();
        $this->assertStringContainsString('game.api.alliance-members.update', $editConfig['action']);
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
        $roleField = $fields['role'];

        $this->assertEquals('select', $allianceIdField['widget']);
        $this->assertEquals('select', $playerIdField['widget']);
        $this->assertEquals('select', $roleField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_datetime_local_widget()
    {
        $fields = $this->form->fields();
        $joinedAtField = $fields['joined_at'];

        $this->assertEquals('datetime-local', $joinedAtField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_checkbox_widget()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertEquals('checkbox', $activeField['widget']);
    }

    /**
     * @test
     */
    public function it_handles_required_fields()
    {
        $fields = $this->form->fields();
        $allianceIdField = $fields['alliance_id'];
        $playerIdField = $fields['player_id'];
        $roleField = $fields['role'];
        $joinedAtField = $fields['joined_at'];

        $this->assertTrue($allianceIdField['required']);
        $this->assertTrue($playerIdField['required']);
        $this->assertTrue($roleField['required']);
        $this->assertTrue($joinedAtField['required']);
    }

    /**
     * @test
     */
    public function it_handles_optional_fields()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertArrayNotHasKey('required', $activeField);
    }

    /**
     * @test
     */
    public function it_handles_boolean_validation_for_checkbox()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertContains('boolean', $activeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_date_validation_for_datetime_field()
    {
        $fields = $this->form->fields();
        $joinedAtField = $fields['joined_at'];

        $this->assertContains('date', $joinedAtField['rules']);
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
    public function it_handles_in_validation_for_role()
    {
        $fields = $this->form->fields();
        $roleField = $fields['role'];

        $this->assertContains('in:member,officer,leader', $roleField['rules']);
    }
}

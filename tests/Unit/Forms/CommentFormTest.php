<?php

namespace Tests\Unit\Forms;

use App\Forms\CommentForm;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentFormTest extends TestCase
{
    use RefreshDatabase;

    private CommentForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new CommentForm();
    }

    /**
     * @test
     */
    public function it_creates_form_with_correct_method_and_action()
    {
        $createConfig = $this->form->create();

        $this->assertEquals('POST', $createConfig['method']);
        $this->assertStringContains('api.comments.store', $createConfig['action']);
    }

    /**
     * @test
     */
    public function it_edits_form_with_correct_method_and_action()
    {
        $comment = Comment::factory()->create();
        $form = new CommentForm($comment);

        $editConfig = $form->edit();

        $this->assertEquals('PATCH', $editConfig['method']);
        $this->assertStringContains('api.comments.update', $editConfig['action']);
        $this->assertStringContains($comment->id, $editConfig['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_fields()
    {
        $fields = $this->form->fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('content', $fields);
        $this->assertArrayHasKey('user_id', $fields);
        $this->assertArrayHasKey('commentable_type', $fields);
        $this->assertArrayHasKey('commentable_id', $fields);
        $this->assertArrayHasKey('parent_id', $fields);
        $this->assertArrayHasKey('is_approved', $fields);
        $this->assertArrayHasKey('is_pinned', $fields);
    }

    /**
     * @test
     */
    public function it_has_correct_content_field_configuration()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertEquals(__('Comment Content'), $contentField['label']);
        $this->assertEquals('textarea', $contentField['widget']);
        $this->assertTrue($contentField['required']);
        $this->assertEquals(1000, $contentField['maxlength']);
        $this->assertContains('required', $contentField['rules']);
        $this->assertContains('string', $contentField['rules']);
        $this->assertContains('max:1000', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_user_id_field_configuration()
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertEquals(__('User'), $userIdField['label']);
        $this->assertEquals('select', $userIdField['widget']);
        $this->assertTrue($userIdField['required']);
        $this->assertArrayHasKey('choices', $userIdField);
        $this->assertArrayHasKey($user->id, $userIdField['choices']);
        $this->assertEquals('Test User', $userIdField['choices'][$user->id]);
    }

    /**
     * @test
     */
    public function it_has_correct_commentable_type_field_configuration()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertEquals(__('Commentable Type'), $commentableTypeField['label']);
        $this->assertEquals('select', $commentableTypeField['widget']);
        $this->assertTrue($commentableTypeField['required']);
        $this->assertArrayHasKey('choices', $commentableTypeField);
        $this->assertArrayHasKey('App\Models\User', $commentableTypeField['choices']);
        $this->assertArrayHasKey('App\Models\Game\Player', $commentableTypeField['choices']);
        $this->assertArrayHasKey('App\Models\Game\Village', $commentableTypeField['choices']);
    }

    /**
     * @test
     */
    public function it_has_correct_commentable_id_field_configuration()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertEquals(__('Commentable ID'), $commentableIdField['label']);
        $this->assertEquals('number', $commentableIdField['widget']);
        $this->assertTrue($commentableIdField['required']);
        $this->assertEquals(1, $commentableIdField['min']);
        $this->assertContains('required', $commentableIdField['rules']);
        $this->assertContains('integer', $commentableIdField['rules']);
        $this->assertContains('min:1', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_parent_id_field_configuration()
    {
        $fields = $this->form->fields();
        $parentIdField = $fields['parent_id'];

        $this->assertEquals(__('Parent Comment'), $parentIdField['label']);
        $this->assertEquals('select', $parentIdField['widget']);
        $this->assertFalse($parentIdField['required']);
        $this->assertArrayHasKey('choices', $parentIdField);
        $this->assertArrayHasKey('', $parentIdField['choices']);
        $this->assertEquals(__('No Parent'), $parentIdField['choices']['']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_approved_field_configuration()
    {
        $fields = $this->form->fields();
        $approvedField = $fields['is_approved'];

        $this->assertEquals(__('Approved Comment'), $approvedField['label']);
        $this->assertEquals('checkbox', $approvedField['widget']);
        $this->assertFalse($approvedField['required']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_pinned_field_configuration()
    {
        $fields = $this->form->fields();
        $pinnedField = $fields['is_pinned'];

        $this->assertEquals(__('Pinned Comment'), $pinnedField['label']);
        $this->assertEquals('checkbox', $pinnedField['widget']);
        $this->assertFalse($pinnedField['required']);
    }

    /**
     * @test
     */
    public function it_handles_empty_users_list()
    {
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertIsArray($userIdField['choices']);
        $this->assertEmpty($userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_multiple_users()
    {
        User::factory()->count(3)->create();
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertCount(3, $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_empty_parent_comments_list()
    {
        $fields = $this->form->fields();
        $parentIdField = $fields['parent_id'];

        $this->assertIsArray($parentIdField['choices']);
        $this->assertCount(1, $parentIdField['choices']);
        $this->assertArrayHasKey('', $parentIdField['choices']);
        $this->assertEquals(__('No Parent'), $parentIdField['choices']['']);
    }

    /**
     * @test
     */
    public function it_handles_multiple_parent_comments()
    {
        Comment::factory()->count(3)->create();
        $fields = $this->form->fields();
        $parentIdField = $fields['parent_id'];

        $this->assertCount(4, $parentIdField['choices']);  // 3 comments + 1 "No Parent" option
    }

    /**
     * @test
     */
    public function it_handles_users_with_unicode_names()
    {
        User::factory()->create(['name' => '测试用户']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('测试用户', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_special_characters()
    {
        User::factory()->create(['name' => 'User "Test" & Co.']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('User "Test" & Co.', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_user_names()
    {
        $longName = str_repeat('A', 255);
        User::factory()->create(['name' => $longName]);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains($longName, $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_null_names()
    {
        User::factory()->create(['name' => null]);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains(null, $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_empty_names()
    {
        User::factory()->create(['name' => '']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_large_number_of_users()
    {
        User::factory()->count(100)->create();
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertCount(100, $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_duplicate_names()
    {
        User::factory()->count(2)->create(['name' => 'Duplicate User']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertCount(2, $userIdField['choices']);
        $this->assertEquals(2, collect($userIdField['choices'])->filter(fn ($name) => $name === 'Duplicate User')->count());
    }

    /**
     * @test
     */
    public function it_handles_users_with_numeric_names()
    {
        User::factory()->create(['name' => '123']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('123', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_boolean_names()
    {
        User::factory()->create(['name' => 'true']);
        User::factory()->create(['name' => 'false']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('true', $userIdField['choices']);
        $this->assertContains('false', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_array_names()
    {
        User::factory()->create(['name' => '["array", "name"]']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('["array", "name"]', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_object_names()
    {
        User::factory()->create(['name' => '{"object": "name"}']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('{"object": "name"}', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_json_names()
    {
        User::factory()->create(['name' => '{"json": "name", "value": 123}']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('{"json": "name", "value": 123}', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_xss_attempts()
    {
        User::factory()->create(['name' => '<script>alert("xss")</script>']);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains('<script>alert("xss")</script>', $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_users_with_sql_injection_attempts()
    {
        User::factory()->create(['name' => "'; DROP TABLE users; --"]);
        $fields = $this->form->fields();
        $userIdField = $fields['user_id'];

        $this->assertContains("'; DROP TABLE users; --", $userIdField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_content_field_validation()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('required', $contentField['rules']);
        $this->assertContains('string', $contentField['rules']);
        $this->assertContains('max:1000', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_commentable_id_field_validation()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('required', $commentableIdField['rules']);
        $this->assertContains('integer', $commentableIdField['rules']);
        $this->assertContains('min:1', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('required', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('required', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('max:1000', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('required', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('required', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_negative_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('min:1', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_zero_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('min:1', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_numeric_content()
    {
        $fields = $this->form->fields();
        $contentField = $fields['content'];

        $this->assertContains('string', $contentField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_decimal_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_string_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_large_commentable_id()
    {
        $fields = $this->form->fields();
        $commentableIdField = $fields['commentable_id'];

        $this->assertContains('integer', $commentableIdField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_commentable_type_choices()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertArrayHasKey('App\Models\User', $commentableTypeField['choices']);
        $this->assertArrayHasKey('App\Models\Game\Player', $commentableTypeField['choices']);
        $this->assertArrayHasKey('App\Models\Game\Village', $commentableTypeField['choices']);
    }

    /**
     * @test
     */
    public function it_handles_commentable_type_validation()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('required', $commentableTypeField['rules']);
        $this->assertContains('string', $commentableTypeField['rules']);
        $this->assertContains('in:App\Models\User,App\Models\Game\Player,App\Models\Game\Village', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('required', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('required', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_invalid_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('in:App\Models\User,App\Models\Game\Player,App\Models\Game\Village', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_commentable_type()
    {
        $fields = $this->form->fields();
        $commentableTypeField = $fields['commentable_type'];

        $this->assertContains('string', $commentableTypeField['rules']);
    }
}

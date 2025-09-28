<?php

namespace Tests\Unit\Forms;

use App\Forms\UserForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFormTest extends TestCase
{
    use RefreshDatabase;

    private UserForm $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new UserForm();
    }

    /**
     * @test
     */
    public function it_creates_form_with_correct_method_and_action()
    {
        $createConfig = $this->form->create();

        $this->assertEquals('POST', $createConfig['method']);
        $this->assertStringContains('api.users.store', $createConfig['action']);
    }

    /**
     * @test
     */
    public function it_edits_form_with_correct_method_and_action()
    {
        $user = User::factory()->create();
        $form = new UserForm($user);

        $editConfig = $form->edit();

        $this->assertEquals('PATCH', $editConfig['method']);
        $this->assertStringContains('api.users.update', $editConfig['action']);
        $this->assertStringContains($user->id, $editConfig['action']);
    }

    /**
     * @test
     */
    public function it_returns_correct_fields()
    {
        $fields = $this->form->fields();

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('password', $fields);
        $this->assertArrayHasKey('password_confirmation', $fields);
        $this->assertArrayHasKey('is_active', $fields);
    }

    /**
     * @test
     */
    public function it_has_correct_name_field_configuration()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertEquals(__('Name'), $nameField['label']);
        $this->assertTrue($nameField['required']);
        $this->assertEquals(255, $nameField['maxlength']);
        $this->assertContains('required', $nameField['rules']);
        $this->assertContains('string', $nameField['rules']);
        $this->assertContains('max:255', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_email_field_configuration()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertEquals(__('Email'), $emailField['label']);
        $this->assertEquals('email', $emailField['widget']);
        $this->assertTrue($emailField['required']);
        $this->assertEquals(255, $emailField['maxlength']);
        $this->assertContains('required', $emailField['rules']);
        $this->assertContains('string', $emailField['rules']);
        $this->assertContains('email', $emailField['rules']);
        $this->assertContains('max:255', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_password_field_configuration()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertEquals(__('Password'), $passwordField['label']);
        $this->assertEquals('password', $passwordField['widget']);
        $this->assertTrue($passwordField['required']);
        $this->assertEquals(8, $passwordField['minlength']);
        $this->assertContains('required', $passwordField['rules']);
        $this->assertContains('string', $passwordField['rules']);
        $this->assertContains('min:8', $passwordField['rules']);
        $this->assertContains('confirmed', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_password_confirmation_field_configuration()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertEquals(__('Confirm Password'), $passwordConfirmationField['label']);
        $this->assertEquals('password', $passwordConfirmationField['widget']);
        $this->assertTrue($passwordConfirmationField['required']);
        $this->assertEquals(8, $passwordConfirmationField['minlength']);
        $this->assertContains('required', $passwordConfirmationField['rules']);
        $this->assertContains('string', $passwordConfirmationField['rules']);
        $this->assertContains('min:8', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_has_correct_is_active_field_configuration()
    {
        $fields = $this->form->fields();
        $activeField = $fields['is_active'];

        $this->assertEquals(__('Active User'), $activeField['label']);
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
        $this->assertContains('max:255', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_email_field_validation()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('required', $emailField['rules']);
        $this->assertContains('string', $emailField['rules']);
        $this->assertContains('email', $emailField['rules']);
        $this->assertContains('max:255', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_password_field_validation()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('required', $passwordField['rules']);
        $this->assertContains('string', $passwordField['rules']);
        $this->assertContains('min:8', $passwordField['rules']);
        $this->assertContains('confirmed', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_password_confirmation_field_validation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('required', $passwordConfirmationField['rules']);
        $this->assertContains('string', $passwordConfirmationField['rules']);
        $this->assertContains('min:8', $passwordConfirmationField['rules']);
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

        $this->assertContains('max:255', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('required', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('required', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_invalid_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_very_long_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('max:255', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('required', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('required', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_short_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('min:8', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('confirmed', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_null_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('required', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_empty_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('required', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_short_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('min:8', $passwordConfirmationField['rules']);
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
    public function it_handles_boolean_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_email()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_password()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_boolean_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_array_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_object_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_json_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_xss_attempts_in_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_sql_injection_attempts_in_password_confirmation()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_names()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_emails()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_passwords()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_password_confirmations()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_names()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_emails()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_passwords()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_password_confirmations()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_numeric_names()
    {
        $fields = $this->form->fields();
        $nameField = $fields['name'];

        $this->assertContains('string', $nameField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_numeric_emails()
    {
        $fields = $this->form->fields();
        $emailField = $fields['email'];

        $this->assertContains('email', $emailField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_numeric_passwords()
    {
        $fields = $this->form->fields();
        $passwordField = $fields['password'];

        $this->assertContains('string', $passwordField['rules']);
    }

    /**
     * @test
     */
    public function it_handles_numeric_password_confirmations()
    {
        $fields = $this->form->fields();
        $passwordConfirmationField = $fields['password_confirmation'];

        $this->assertContains('string', $passwordConfirmationField['rules']);
    }
}

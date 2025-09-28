<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserProfileManager;
use App\Models\User;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class UserProfileManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        Auth::login($this->user);
    }

    /**
     * @test
     */
    public function it_can_render_user_profile_manager()
    {
        Livewire::test(UserProfileManager::class)
            ->assertSee('UserProfileManager')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_mount_with_user()
    {
        $component = Livewire::test(UserProfileManager::class, ['user' => $this->user]);

        $this->assertEquals($this->user->id, $component->user->id);
        $this->assertEquals('John Doe', $component->name);
        $this->assertEquals('john@example.com', $component->email);
        $this->assertEquals('+1234567890', $component->phone);
        $this->assertEquals('US', $component->phone_country);
    }

    /**
     * @test
     */
    public function it_can_mount_without_user()
    {
        $component = Livewire::test(UserProfileManager::class);

        $this->assertEquals($this->user->id, $component->user->id);
    }

    /**
     * @test
     */
    public function it_can_update_profile()
    {
        Livewire::test(UserProfileManager::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSee('Profile updated successfully!');

        $this->user->refresh();
        $this->assertEquals('Jane Doe', $this->user->name);
        $this->assertEquals('jane@example.com', $this->user->email);
    }

    /**
     * @test
     */
    public function it_validates_required_fields()
    {
        Livewire::test(UserProfileManager::class)
            ->set('name', '')
            ->set('email', '')
            ->call('updateProfile')
            ->assertHasErrors(['name' => 'required'])
            ->assertHasErrors(['email' => 'required']);
    }

    /**
     * @test
     */
    public function it_validates_email_format()
    {
        Livewire::test(UserProfileManager::class)
            ->set('email', 'invalid-email')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'email']);
    }

    /**
     * @test
     */
    public function it_validates_email_uniqueness()
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        Livewire::test(UserProfileManager::class)
            ->set('email', 'other@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'unique']);
    }

    /**
     * @test
     */
    public function it_can_update_password()
    {
        $currentPassword = 'current-password';
        $newPassword = 'new-password-123';

        $this->user->update(['password' => Hash::make($currentPassword)]);

        Livewire::test(UserProfileManager::class)
            ->set('currentPassword', $currentPassword)
            ->set('newPassword', $newPassword)
            ->set('newPasswordConfirmation', $newPassword)
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSee('Password updated successfully!');

        $this->user->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->user->password));
    }

    /**
     * @test
     */
    public function it_validates_current_password()
    {
        $this->user->update(['password' => Hash::make('current-password')]);

        Livewire::test(UserProfileManager::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'new-password-123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);
    }

    /**
     * @test
     */
    public function it_validates_password_confirmation()
    {
        $this->user->update(['password' => Hash::make('current-password')]);

        Livewire::test(UserProfileManager::class)
            ->set('currentPassword', 'current-password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'different-password')
            ->call('updatePassword')
            ->assertHasErrors(['newPassword']);
    }

    /**
     * @test
     */
    public function it_can_toggle_password_form()
    {
        $component = Livewire::test(UserProfileManager::class);

        $this->assertFalse($component->showPasswordForm);

        $component->call('togglePasswordForm');
        $this->assertTrue($component->showPasswordForm);

        $component->call('togglePasswordForm');
        $this->assertFalse($component->showPasswordForm);
    }

    /**
     * @test
     */
    public function it_can_update_phone()
    {
        Livewire::test(UserProfileManager::class)
            ->set('phone', '+1987654321')
            ->set('phone_country', 'US')
            ->call('updatePhone')
            ->assertHasNoErrors()
            ->assertSee('Phone number updated successfully!');

        $this->user->refresh();
        $this->assertEquals('+1987654321', $this->user->phone);
        $this->assertEquals('US', $this->user->phone_country);
    }

    /**
     * @test
     */
    public function it_can_toggle_phone_form()
    {
        $component = Livewire::test(UserProfileManager::class);

        $this->assertFalse($component->showPhoneForm);

        $component->call('togglePhoneForm');
        $this->assertTrue($component->showPhoneForm);

        $component->call('togglePhoneForm');
        $this->assertFalse($component->showPhoneForm);
    }

    /**
     * @test
     */
    public function it_can_format_phone()
    {
        $component = Livewire::test(UserProfileManager::class)
            ->set('phone', '1234567890')
            ->set('phone_country', 'US')
            ->call('formatPhone');

        $this->assertStringContainsString('+1', $component->phone);
    }

    /**
     * @test
     */
    public function it_handles_phone_formatting_errors()
    {
        Livewire::test(UserProfileManager::class)
            ->set('phone', 'invalid-phone')
            ->set('phone_country', 'US')
            ->call('formatPhone')
            ->assertSee('Invalid phone number format.');
    }

    /**
     * @test
     */
    public function it_can_initialize_user_real_time()
    {
        // Mock GameIntegrationService
        $mockService = Mockery::mock(GameIntegrationService::class);
        $mockService->shouldReceive('initializeUserRealTime')->once();
        $this->app->instance(GameIntegrationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->call('initializeUserRealTime')
            ->assertDispatched('profile-initialized');
    }

    /**
     * @test
     */
    public function it_handles_real_time_initialization_errors()
    {
        // Mock GameIntegrationService to throw exception
        $mockService = Mockery::mock(GameIntegrationService::class);
        $mockService->shouldReceive('initializeUserRealTime')->andThrow(new \Exception('Service error'));
        $this->app->instance(GameIntegrationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->call('initializeUserRealTime')
            ->assertDispatched('error');
    }

    /**
     * @test
     */
    public function it_can_update_profile_with_integration()
    {
        // Mock GameNotificationService
        $mockService = Mockery::mock(GameNotificationService::class);
        $mockService->shouldReceive('sendNotification')->once();
        $this->app->instance(GameNotificationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->set('name', 'Updated Name')
            ->call('updateProfileWithIntegration')
            ->assertDispatched('profile-updated');

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
    }

    /**
     * @test
     */
    public function it_can_update_phone_with_integration()
    {
        // Mock GameNotificationService
        $mockService = Mockery::mock(GameNotificationService::class);
        $mockService->shouldReceive('sendNotification')->once();
        $this->app->instance(GameNotificationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->set('phone', '+1555123456')
            ->call('updatePhoneWithIntegration')
            ->assertDispatched('phone-updated');

        $this->user->refresh();
        $this->assertEquals('+1555123456', $this->user->phone);
    }

    /**
     * @test
     */
    public function it_handles_profile_update_errors()
    {
        // Mock GameNotificationService to throw exception
        $mockService = Mockery::mock(GameNotificationService::class);
        $mockService->shouldReceive('sendNotification')->andThrow(new \Exception('Service error'));
        $this->app->instance(GameNotificationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->set('name', 'Updated Name')
            ->call('updateProfileWithIntegration')
            ->assertDispatched('error');
    }

    /**
     * @test
     */
    public function it_handles_phone_update_errors()
    {
        // Mock GameNotificationService to throw exception
        $mockService = Mockery::mock(GameNotificationService::class);
        $mockService->shouldReceive('sendNotification')->andThrow(new \Exception('Service error'));
        $this->app->instance(GameNotificationService::class, $mockService);

        Livewire::test(UserProfileManager::class)
            ->set('phone', '+1555123456')
            ->call('updatePhoneWithIntegration')
            ->assertDispatched('error');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

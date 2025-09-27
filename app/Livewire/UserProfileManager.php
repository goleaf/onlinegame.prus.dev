<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use Intervention\Validation\Rules\Username;
use JonPurvis\Squeaky\Rules\Clean;
use Livewire\Component;
use Livewire\WithFileUploads;
use Propaganistas\LaravelPhone\Rules\Phone;
use Ziming\LaravelZxcvbn\Rules\ZxcvbnRule;

class UserProfileManager extends Component
{
    use WithFileUploads;

    public User $user;
    public $name;
    public $email;
    public $phone = '';
    public $phone_country = 'US';
    public $showPhoneForm = false;
    public $showPasswordForm = false;
    public $currentPassword = '';
    public $newPassword = '';
    public $newPasswordConfirmation = '';

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', new Username(), new Clean],
            'email' => 'required|email|unique:users,email',
            'phone' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'currentPassword' => 'required_with:newPassword',
            'newPassword' => [
                'required_with:currentPassword',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $this->email,
                    $this->name,
                ]),
            ],
            'newPasswordConfirmation' => 'required_with:newPassword',
        ];
    }

    public function mount(User $user = null)
    {
        $this->user = $user ?? auth()->user();

        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        $this->phone_country = $this->user->phone_country ?? 'US';

        // Initialize real-time features for the user
        $this->initializeUserRealTime();
    }

    public function updated($propertyName)
    {
        // Update email uniqueness rule to exclude current user
        if ($propertyName === 'email') {
            $this->rules['email'] = 'required|email|unique:users,email,' . $this->user->id;
        }

        $this->validateOnly($propertyName);

        if ($propertyName === 'phone' && !empty($this->phone)) {
            $this->rules['phone'][] = (new Phone)->country($this->phone_country);
        }
    }

    public function updateProfile()
    {
        $rules = $this->rules;
        $rules['email'] = 'required|email|unique:users,email,' . $this->user->id;

        if (!empty($this->phone)) {
            $rules['phone'][] = (new Phone)->country($this->phone_country);
        }

        $this->validate($rules);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_country' => $this->phone_country,
        ]);

        session()->flash('message', 'Profile updated successfully!');
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => [
                'required',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $this->email,
                    $this->name,
                ]),
            ],
            'newPasswordConfirmation' => 'required',
        ]);

        // Verify current password
        if (!\Hash::check($this->currentPassword, $this->user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $this->user->update([
            'password' => bcrypt($this->newPassword),
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation', 'showPasswordForm']);
        session()->flash('message', 'Password updated successfully!');
    }

    public function togglePasswordForm()
    {
        $this->showPasswordForm = !$this->showPasswordForm;
        if (!$this->showPasswordForm) {
            $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        }
    }

    public function updatePhone()
    {
        $rules = [
            'phone' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string', 'size:2'],
        ];

        if (!empty($this->phone)) {
            $rules['phone'][] = (new Phone)->country($this->phone_country);
            $rules['phone_country'][] = 'required_with:phone';
        }

        $this->validate($rules);

        $this->user->update([
            'phone' => $this->phone,
            'phone_country' => $this->phone_country,
        ]);

        $this->showPhoneForm = false;
        session()->flash('message', 'Phone number updated successfully!');
    }

    public function formatPhone()
    {
        if (!empty($this->phone) && !empty($this->phone_country)) {
            try {
                $phoneNumber = phone($this->phone, $this->phone_country);
                $this->phone = $phoneNumber->formatInternational();
            } catch (\Exception $e) {
                session()->flash('error', 'Invalid phone number format.');
            }
        }
    }

    public function togglePhoneForm()
    {
        $this->showPhoneForm = !$this->showPhoneForm;
    }

    public function render()
    {
        return view('livewire.user-profile-manager');
    }

    /**
     * Initialize user profile with real-time integration
     */
    public function initializeUserRealTime()
    {
        try {
            // Initialize real-time features for the user
            GameIntegrationService::initializeUserRealTime($this->user->id);
            
            $this->dispatch('profile-initialized', [
                'message' => 'User profile real-time features activated',
                'user_id' => $this->user->id,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize profile real-time features: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update profile with integration notifications
     */
    public function updateProfileWithIntegration()
    {
        try {
            $this->updateProfile();

            // Send notification about profile update
            GameNotificationService::sendNotification(
                $this->user->id,
                'profile_updated',
                [
                    'user_id' => $this->user->id,
                    'updated_fields' => ['name', 'email'],
                    'timestamp' => now()->toISOString(),
                ]
            );

            $this->dispatch('profile-updated', [
                'message' => 'Profile updated successfully with notifications',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update phone with integration notifications
     */
    public function updatePhoneWithIntegration()
    {
        try {
            $this->updatePhone();

            // Send notification about phone update
            GameNotificationService::sendNotification(
                $this->user->id,
                'phone_updated',
                [
                    'user_id' => $this->user->id,
                    'phone' => $this->phone,
                    'country' => $this->phone_country,
                    'timestamp' => now()->toISOString(),
                ]
            );

            $this->dispatch('phone-updated', [
                'message' => 'Phone number updated successfully with notifications',
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to update phone: ' . $e->getMessage(),
            ]);
        }
    }
}

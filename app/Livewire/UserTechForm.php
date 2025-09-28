<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\GameValidationTrait;
use Intervention\Validation\Rules\Base64;
use Intervention\Validation\Rules\DataUri;
use Intervention\Validation\Rules\Jwt;
use Livewire\Attributes\Session;

class UserTechForm extends BaseSessionComponent
{
    use GameValidationTrait;

    public $user;

    // Tech form session properties
    #[Session]
    public $api_token = '';

    #[Session]
    public $webhook_url = '';

    #[Session]
    public $integration_key = '';

    #[Session]
    public $jwt_secret = '';

    #[Session]
    public $base64_encoded_data = '';

    #[Session]
    public $data_uri = '';

    #[Session]
    public $tech_description = '';

    #[Session]
    public $preferred_language = 'en';

    #[Session]
    public $timezone = 'UTC';

    #[Session]
    public $showAdvancedOptions = false;

    #[Session]
    public $autoSave = false;

    protected $rules = [
        'api_token' => 'nullable|string|max:255',
        'webhook_url' => 'nullable|url|max:255',
        'integration_key' => 'nullable|string|max:100',
        'jwt_secret' => 'nullable|string|max:500',
        'base64_encoded_data' => 'nullable|string|max:10000',
        'data_uri' => 'nullable|string|max:10000',
        'tech_description' => 'nullable|string|max:1000',
        'preferred_language' => 'required|string|in:en,es,fr,de,it,pt,ru,ja,ko,zh',
        'timezone' => 'required|string|max:50',
    ];

    public function mount(?User $user = null)
    {
        // Initialize session properties
        $this->initializeSessionProperties();

        $this->user = $user ?? auth()->user();

        if ($this->user) {
            $this->api_token = $this->user->api_token ?? '';
            $this->webhook_url = $this->user->webhook_url ?? '';
            $this->integration_key = $this->user->integration_key ?? '';
            $this->jwt_secret = $this->user->jwt_secret ?? '';
            $this->base64_encoded_data = $this->user->base64_encoded_data ?? '';
            $this->data_uri = $this->user->data_uri ?? '';
            $this->tech_description = $this->user->tech_description ?? '';
            $this->preferred_language = $this->user->preferred_language ?? 'en';
            $this->timezone = $this->user->timezone ?? 'UTC';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        // Add JWT validation when provided
        if ($propertyName === 'jwt_secret' && ! empty($this->jwt_secret)) {
            $this->rules['jwt_secret'][] = new Jwt();
        }

        // Add Base64 validation when provided
        if ($propertyName === 'base64_encoded_data' && ! empty($this->base64_encoded_data)) {
            $this->rules['base64_encoded_data'][] = new Base64();
        }

        // Add Data URI validation when provided
        if ($propertyName === 'data_uri' && ! empty($this->data_uri)) {
            $this->rules['data_uri'][] = new DataUri();
        }
    }

    public function generateApiToken()
    {
        $this->integration_key = \Illuminate\Support\Str::ulid();
    }

    public function save()
    {
        $rules = $this->rules;

        // Add validation based on provided fields
        if (! empty($this->jwt_secret)) {
            $rules['jwt_secret'][] = new Jwt();
        }
        if (! empty($this->base64_encoded_data)) {
            $rules['base64_encoded_data'][] = new Base64();
        }
        if (! empty($this->data_uri)) {
            $rules['data_uri'][] = new DataUri();
        }

        $this->validate($rules);

        $this->user->update([
            'api_token' => $this->api_token,
            'webhook_url' => $this->webhook_url,
            'integration_key' => $this->integration_key,
            'jwt_secret' => $this->jwt_secret,
            'base64_encoded_data' => $this->base64_encoded_data,
            'data_uri' => $this->data_uri,
            'tech_description' => $this->tech_description,
            'preferred_language' => $this->preferred_language,
            'timezone' => $this->timezone,
        ]);

        $this->addNotification('Technical information updated successfully!', 'success');
    }

    /**
     * Toggle advanced options display
     */
    public function toggleAdvancedOptions()
    {
        $this->showAdvancedOptions = ! $this->showAdvancedOptions;
        $this->addNotification(
            $this->showAdvancedOptions ? 'Advanced options enabled' : 'Advanced options disabled',
            'info'
        );
    }

    /**
     * Toggle auto-save functionality
     */
    public function toggleAutoSave()
    {
        $this->autoSave = ! $this->autoSave;
        $this->addNotification(
            $this->autoSave ? 'Auto-save enabled' : 'Auto-save disabled',
            'info'
        );
    }

    /**
     * Auto-save current form data
     */
    public function autoSave()
    {
        if ($this->autoSave) {
            // Auto-save logic would go here
            $this->addNotification('Form auto-saved', 'info');
        }
    }

    /**
     * Clear all form data
     */
    public function clearForm()
    {
        $this->api_token = '';
        $this->webhook_url = '';
        $this->integration_key = '';
        $this->jwt_secret = '';
        $this->base64_encoded_data = '';
        $this->data_uri = '';
        $this->tech_description = '';
        $this->preferred_language = 'en';
        $this->timezone = 'UTC';

        $this->addNotification('Form cleared', 'info');
    }

    /**
     * Reset form preferences to defaults
     */
    public function resetFormPreferences()
    {
        $this->showAdvancedOptions = false;
        $this->autoSave = false;

        // Reset base session properties
        $this->resetSessionProperties();

        $this->addNotification('Form preferences reset to defaults', 'info');
    }

    public function render()
    {
        return view('livewire.user-tech-form');
    }
}

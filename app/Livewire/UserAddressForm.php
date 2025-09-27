<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\GameValidationTrait;
use Illuminate\Support\Facades\Validator;
use Intervention\Validation\Rules\Postalcode;
use Livewire\Component;
use JonPurvis\Squeaky\Rules\Clean;

class UserAddressForm extends Component
{
    use GameValidationTrait;

    public $user;
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $state = '';
    public $postal_code = '';
    public $country = 'US';

    protected $rules = [
        'address_line_1' => ['nullable', 'string', 'max:255', new Clean],
        'address_line_2' => ['nullable', 'string', 'max:255', new Clean],
        'city' => ['nullable', 'string', 'max:100', new Clean],
        'state' => ['nullable', 'string', 'max:100', new Clean],
        'postal_code' => ['nullable', 'string', 'max:20'],
        'country' => ['required', 'string', 'size:2'],
    ];

    public function mount(User $user = null)
    {
        $this->user = $user ?? auth()->user();
        
        if ($this->user) {
            $this->address_line_1 = $this->user->address_line_1 ?? '';
            $this->address_line_2 = $this->user->address_line_2 ?? '';
            $this->city = $this->user->city ?? '';
            $this->state = $this->user->state ?? '';
            $this->postal_code = $this->user->postal_code ?? '';
            $this->country = $this->user->country ?? 'US';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        // Add postal code validation when country is available
        if ($propertyName === 'postal_code' && !empty($this->postal_code) && !empty($this->country)) {
            $this->rules['postal_code'][] = new Postalcode($this->country);
        }
    }

    public function save()
    {
        $rules = $this->rules;
        
        // Add postal code validation if postal code is provided
        if (!empty($this->postal_code) && !empty($this->country)) {
            $rules['postal_code'][] = new Postalcode($this->country);
        }

        $this->validate($rules);

        $this->user->update([
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
        ]);

        session()->flash('message', 'Address updated successfully!');
    }

    public function render()
    {
        return view('livewire.user-address-form');
    }
}

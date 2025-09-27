<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Validate;

class UserAddressForm extends Component
{
    #[Validate('required|string|max:255')]
    public $street = '';

    #[Validate('required|string|max:100')]
    public $city = '';

    #[Validate('required|string|max:20')]
    public $postal_code = '';

    #[Validate('required|string|max:100')]
    public $state = '';

    #[Validate('required|string|max:100')]
    public $country = '';

    #[Validate('nullable|string|max:20')]
    public $phone = '';

    public $isEditing = false;

    public function mount()
    {
        $user = Auth::user();
        if ($user) {
            $this->street = $user->street ?? '';
            $this->city = $user->city ?? '';
            $this->postal_code = $user->postal_code ?? '';
            $this->state = $user->state ?? '';
            $this->country = $user->country ?? '';
            $this->phone = $user->phone ?? '';
        }
    }

    public function toggleEdit()
    {
        $this->isEditing = !$this->isEditing;
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();
        if ($user) {
            $user->update([
                'street' => $this->street,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'state' => $this->state,
                'country' => $this->country,
                'phone' => $this->phone,
            ]);

            $this->isEditing = false;
            session()->flash('message', 'Address updated successfully!');
        }
    }

    public function cancel()
    {
        $this->mount(); // Reset to original values
        $this->isEditing = false;
    }

    public function render()
    {
        return view('livewire.user-address-form');
    }
}
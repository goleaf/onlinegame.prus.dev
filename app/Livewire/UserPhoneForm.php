<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Propaganistas\LaravelPhone\Rules\Phone;

class UserPhoneForm extends Component
{
    use WithFileUploads;

    public $user;
    public $phone = '';
    public $phone_country = 'US';

    protected $rules = [
        'phone' => ['nullable', 'string'],
        'phone_country' => ['required_with:phone', 'string', 'size:2'],
    ];

    public function mount(User $user = null)
    {
        $this->user = $user ?? new User();

        if ($this->user->exists) {
            $this->phone = $this->user->phone;
            $this->phone_country = $this->user->phone_country ?? 'US';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);

        if ($propertyName === 'phone' && !empty($this->phone)) {
            $this->rules['phone'][] = (new Phone)->country($this->phone_country);
        }
    }

    public function save()
    {
        $rules = $this->rules;

        if (!empty($this->phone)) {
            $rules['phone'][] = (new Phone)->country($this->phone_country);
        }

        $this->validate($rules);

        $this->user->phone = $this->phone;
        $this->user->phone_country = $this->phone_country;
        $this->user->save();

        session()->flash('message', 'Phone number saved successfully!');

        $this->dispatch('phone-saved');
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

    public function render()
    {
        return view('livewire.user-phone-form');
    }
}

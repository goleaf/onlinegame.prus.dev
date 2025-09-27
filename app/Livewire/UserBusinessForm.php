<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\GameValidationTrait;
use Illuminate\Support\Facades\Validator;
use Intervention\Validation\Rules\Iban;
use Intervention\Validation\Rules\Bic;
use Intervention\Validation\Rules\Isbn;
use Intervention\Validation\Rules\Ean;
use Livewire\Component;
use JonPurvis\Squeaky\Rules\Clean;

class UserBusinessForm extends Component
{
    use GameValidationTrait;

    public $user;
    public $business_name = '';
    public $business_type = '';
    public $tax_number = '';
    public $registration_number = '';
    public $business_address = '';
    public $business_city = '';
    public $business_country = 'US';
    public $business_phone = '';
    public $business_email = '';
    public $business_website = '';
    public $business_description = '';
    public $bank_iban = '';
    public $bank_bic = '';
    public $product_isbn = '';
    public $product_ean = '';

    protected $rules = [
        'business_name' => 'required|string|max:255',
        'business_type' => 'required|in:sole_proprietorship,partnership,corporation,llc',
        'tax_number' => 'nullable|string|max:50',
        'registration_number' => 'nullable|string|max:50',
        'business_address' => 'nullable|string|max:500',
        'business_city' => 'nullable|string|max:100',
        'business_country' => 'required|string|size:2',
        'business_phone' => 'nullable|string|max:20',
        'business_email' => 'nullable|email|max:255',
        'business_website' => 'nullable|url|max:255',
        'business_description' => ['nullable', 'string', 'max:1000', new Clean],
        'bank_iban' => 'nullable|string|max:34',
        'bank_bic' => 'nullable|string|max:11',
        'product_isbn' => 'nullable|string|max:17',
        'product_ean' => 'nullable|string|max:18',
    ];

    public function mount(User $user = null)
    {
        $this->user = $user ?? auth()->user();
        
        if ($this->user) {
            $this->business_name = $this->user->business_name ?? '';
            $this->business_type = $this->user->business_type ?? '';
            $this->tax_number = $this->user->tax_number ?? '';
            $this->registration_number = $this->user->registration_number ?? '';
            $this->business_address = $this->user->business_address ?? '';
            $this->business_city = $this->user->business_city ?? '';
            $this->business_country = $this->user->business_country ?? 'US';
            $this->business_phone = $this->user->business_phone ?? '';
            $this->business_email = $this->user->business_email ?? '';
            $this->business_website = $this->user->business_website ?? '';
            $this->business_description = $this->user->business_description ?? '';
            $this->bank_iban = $this->user->bank_iban ?? '';
            $this->bank_bic = $this->user->bank_bic ?? '';
            $this->product_isbn = $this->user->product_isbn ?? '';
            $this->product_ean = $this->user->product_ean ?? '';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        // Add IBAN validation when provided
        if ($propertyName === 'bank_iban' && !empty($this->bank_iban)) {
            $this->rules['bank_iban'][] = new Iban();
        }
        
        // Add BIC validation when provided
        if ($propertyName === 'bank_bic' && !empty($this->bank_bic)) {
            $this->rules['bank_bic'][] = new Bic();
        }
        
        // Add ISBN validation when provided
        if ($propertyName === 'product_isbn' && !empty($this->product_isbn)) {
            $this->rules['product_isbn'][] = new Isbn();
        }
        
        // Add EAN validation when provided
        if ($propertyName === 'product_ean' && !empty($this->product_ean)) {
            $this->rules['product_ean'][] = new Ean();
        }
    }

    public function save()
    {
        $rules = $this->rules;
        
        // Add validation based on provided fields
        if (!empty($this->bank_iban)) {
            $rules['bank_iban'][] = new Iban();
        }
        if (!empty($this->bank_bic)) {
            $rules['bank_bic'][] = new Bic();
        }
        if (!empty($this->product_isbn)) {
            $rules['product_isbn'][] = new Isbn();
        }
        if (!empty($this->product_ean)) {
            $rules['product_ean'][] = new Ean();
        }

        $this->validate($rules);

        $this->user->update([
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'tax_number' => $this->tax_number,
            'registration_number' => $this->registration_number,
            'business_address' => $this->business_address,
            'business_city' => $this->business_city,
            'business_country' => $this->business_country,
            'business_phone' => $this->business_phone,
            'business_email' => $this->business_email,
            'business_website' => $this->business_website,
            'business_description' => $this->business_description,
            'bank_iban' => $this->bank_iban,
            'bank_bic' => $this->bank_bic,
            'product_isbn' => $this->product_isbn,
            'product_ean' => $this->product_ean,
        ]);

        session()->flash('message', 'Business information updated successfully!');
    }

    public function render()
    {
        return view('livewire.user-business-form');
    }
}

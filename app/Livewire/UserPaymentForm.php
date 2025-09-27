<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\GameValidationTrait;
use Illuminate\Support\Facades\Validator;
use Intervention\Validation\Rules\CreditCard;
use Intervention\Validation\Rules\Iban;
use Intervention\Validation\Rules\Bic;
use Livewire\Component;
use JonPurvis\Squeaky\Rules\Clean;

class UserPaymentForm extends Component
{
    use GameValidationTrait;

    public $user;
    public $payment_method = '';
    public $credit_card_number = '';
    public $credit_card_expiry = '';
    public $credit_card_cvv = '';
    public $bank_account_iban = '';
    public $bank_bic = '';
    public $bank_name = '';

    protected $rules = [
        'payment_method' => 'required|in:credit_card,bank_transfer',
        'credit_card_number' => 'nullable|string',
        'credit_card_expiry' => 'nullable|string|regex:/^\d{2}\/\d{2}$/',
        'credit_card_cvv' => 'nullable|string|size:3',
        'bank_account_iban' => 'nullable|string',
        'bank_bic' => 'nullable|string',
        'bank_name' => 'nullable|string|max:255',
    ];

    public function mount(User $user = null)
    {
        $this->user = $user ?? auth()->user();
        
        if ($this->user) {
            $this->payment_method = $this->user->payment_method ?? '';
            $this->credit_card_number = $this->user->credit_card_number ?? '';
            $this->credit_card_expiry = $this->user->credit_card_expiry ?? '';
            $this->credit_card_cvv = $this->user->credit_card_cvv ?? '';
            $this->bank_account_iban = $this->user->bank_account_iban ?? '';
            $this->bank_bic = $this->user->bank_bic ?? '';
            $this->bank_name = $this->user->bank_name ?? '';
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        // Add credit card validation when payment method is credit card
        if ($propertyName === 'credit_card_number' && $this->payment_method === 'credit_card' && !empty($this->credit_card_number)) {
            $this->rules['credit_card_number'][] = new CreditCard();
        }
        
        // Add IBAN validation when payment method is bank transfer
        if ($propertyName === 'bank_account_iban' && $this->payment_method === 'bank_transfer' && !empty($this->bank_account_iban)) {
            $this->rules['bank_account_iban'][] = new Iban();
        }
        
        // Add BIC validation when payment method is bank transfer
        if ($propertyName === 'bank_bic' && $this->payment_method === 'bank_transfer' && !empty($this->bank_bic)) {
            $this->rules['bank_bic'][] = new Bic();
        }
    }

    public function save()
    {
        $rules = $this->rules;
        
        // Add validation based on payment method
        if ($this->payment_method === 'credit_card') {
            if (!empty($this->credit_card_number)) {
                $rules['credit_card_number'][] = new CreditCard();
            }
        } elseif ($this->payment_method === 'bank_transfer') {
            if (!empty($this->bank_account_iban)) {
                $rules['bank_account_iban'][] = new Iban();
            }
            if (!empty($this->bank_bic)) {
                $rules['bank_bic'][] = new Bic();
            }
        }

        $this->validate($rules);

        $this->user->update([
            'payment_method' => $this->payment_method,
            'credit_card_number' => $this->credit_card_number,
            'credit_card_expiry' => $this->credit_card_expiry,
            'credit_card_cvv' => $this->credit_card_cvv,
            'bank_account_iban' => $this->bank_account_iban,
            'bank_bic' => $this->bank_bic,
            'bank_name' => $this->bank_name,
        ]);

        session()->flash('message', 'Payment information updated successfully!');
    }

    public function render()
    {
        return view('livewire.user-payment-form');
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

#[Title('Phone Statistics Dashboard')]
#[Layout('layouts.app')]
class PhoneStatsDashboard extends Component
{
    public $phoneStats = [];
    public $isLoading = false;

    public function mount()
    {
        $this->loadPhoneStats();
    }

    public function loadPhoneStats()
    {
        $this->isLoading = true;
        
        try {
            $this->phoneStats = [
                'total_phones' => \App\Models\User::whereNotNull('phone')->count(),
                'verified_phones' => \App\Models\User::whereNotNull('phone_verified_at')->count(),
                'unverified_phones' => \App\Models\User::whereNotNull('phone')->whereNull('phone_verified_at')->count(),
                'phone_countries' => \App\Models\User::whereNotNull('phone_country')->distinct('phone_country')->count(),
                'recent_verifications' => \App\Models\User::whereNotNull('phone_verified_at')
                    ->where('phone_verified_at', '>', now()->subDays(7))->count(),
                'verification_rate' => $this->getVerificationRate(),
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load phone statistics: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function getVerificationRate()
    {
        $total = \App\Models\User::whereNotNull('phone')->count();
        $verified = \App\Models\User::whereNotNull('phone_verified_at')->count();
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($verified / $total) * 100, 1);
    }

    public function refreshStats()
    {
        $this->loadPhoneStats();
        session()->flash('message', 'Phone statistics refreshed successfully!');
    }

    public function render()
    {
        return view('livewire.phone-stats-dashboard');
    }
}
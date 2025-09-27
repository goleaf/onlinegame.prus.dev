<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PhoneStatsDashboard extends Component
{
    public $stats = [];
    public $loading = true;
    public $refreshInterval = 300; // 5 minutes

    public function mount()
    {
        $this->loadStatistics();
    }

    public function loadStatistics()
    {
        $this->loading = true;
        
        try {
            $this->stats = [
                'total_users' => User::count(),
                'users_with_phone' => User::whereNotNull('phone')->count(),
                'phone_coverage_percentage' => $this->getPhoneCoveragePercentage(),
                'countries_with_phones' => $this->getCountriesWithPhones(),
                'phone_formats' => $this->getPhoneFormatStatistics(),
                'recent_phone_registrations' => $this->getRecentPhoneRegistrations(),
                'top_countries' => $this->getTopCountries(),
                'phone_validation_errors' => $this->getPhoneValidationErrors(),
            ];
        } catch (\Exception $e) {
            $this->stats = [
                'error' => 'Failed to load phone statistics: ' . $e->getMessage()
            ];
        }
        
        $this->loading = false;
    }

    private function getPhoneCoveragePercentage()
    {
        $total = User::count();
        $withPhone = User::whereNotNull('phone')->count();
        
        return $total > 0 ? round(($withPhone / $total) * 100, 2) : 0;
    }

    private function getCountriesWithPhones()
    {
        return User::whereNotNull('phone_country')
                   ->distinct('phone_country')
                   ->count();
    }

    private function getPhoneFormatStatistics()
    {
        return [
            'with_e164' => User::whereNotNull('phone_e164')->count(),
            'with_normalized' => User::whereNotNull('phone_normalized')->count(),
            'with_national' => User::whereNotNull('phone_national')->count(),
        ];
    }

    private function getRecentPhoneRegistrations()
    {
        return User::whereNotNull('phone')
                   ->where('created_at', '>=', now()->subDays(30))
                   ->count();
    }

    private function getTopCountries()
    {
        return User::whereNotNull('phone_country')
                   ->select('phone_country', DB::raw('count(*) as count'))
                   ->groupBy('phone_country')
                   ->orderBy('count', 'desc')
                   ->limit(10)
                   ->get()
                   ->map(function ($item) {
                       return [
                           'country' => $item->phone_country,
                           'count' => $item->count,
                           'country_name' => $this->getCountryName($item->phone_country)
                       ];
                   });
    }

    private function getPhoneValidationErrors()
    {
        // This would typically come from a logs table or validation tracking
        // For now, we'll return a placeholder
        return [
            'total_errors' => 0,
            'recent_errors' => 0,
            'error_types' => []
        ];
    }

    private function getCountryName($countryCode)
    {
        $countries = [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'AU' => 'Australia',
            'BE' => 'Belgium',
            'NL' => 'Netherlands',
        ];

        return $countries[$countryCode] ?? $countryCode;
    }

    public function refreshStatistics()
    {
        $this->loadStatistics();
    }

    public function render()
    {
        return view('livewire.phone-stats-dashboard');
    }
}

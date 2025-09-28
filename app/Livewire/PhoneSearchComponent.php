<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class PhoneSearchComponent extends Component
{
    use WithPagination;

    public $searchTerm = '';

    public $searchType = 'all';  // all, phone, normalized, e164

    public $countryFilter = '';

    public $perPage = 20;

    protected $queryString = [
        'searchTerm' => ['except' => ''],
        'searchType' => ['except' => 'all'],
        'countryFilter' => ['except' => ''],
    ];

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingSearchType()
    {
        $this->resetPage();
    }

    public function updatingCountryFilter()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->searchTerm = '';
        $this->searchType = 'all';
        $this->countryFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query();

        if ($this->searchTerm) {
            $cleanSearchTerm = preg_replace('/[^0-9+]/', '', $this->searchTerm);

            $query->where(function ($q) use ($cleanSearchTerm): void {
                switch ($this->searchType) {
                    case 'phone':
                        $q->where('phone', 'like', "%{$this->searchTerm}%");

                        break;
                    case 'normalized':
                        $q->where('phone_normalized', 'like', "%{$cleanSearchTerm}%");

                        break;
                    case 'e164':
                        $q->where('phone_e164', 'like', "%{$cleanSearchTerm}%");

                        break;
                    default:  // all
                        $q
                            ->where('phone', 'like', "%{$this->searchTerm}%")
                            ->orWhere('phone_normalized', 'like', "%{$cleanSearchTerm}%")
                            ->orWhere('phone_e164', 'like', "%{$cleanSearchTerm}%");

                        break;
                }
            });
        }

        if ($this->countryFilter) {
            $query->where('phone_country', $this->countryFilter);
        }

        $users = $query
            ->whereNotNull('phone')
            ->with(['player'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        $countries = User::whereNotNull('phone_country')
            ->distinct()
            ->pluck('phone_country')
            ->filter()
            ->sort()
            ->values();

        return view('livewire.phone-search-component', [
            'users' => $users,
            'countries' => $countries,
        ]);
    }
}

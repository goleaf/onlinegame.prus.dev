<?php

namespace App\Livewire;

use App\Models\Game\Player;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Propaganistas\LaravelPhone\Rules\Phone;

class PhoneContactManager extends Component
{
    use WithPagination;

    public $contacts = [];

    public $selectedContacts = [];

    public $searchTerm = '';

    public $filterByAlliance = '';

    public $filterByWorld = '';

    public $showAddContact = false;

    public $newContact = [
        'player_id' => '',
        'name' => '',
        'phone' => '',
        'phone_country' => 'US',
        'notes' => '',
        'category' => 'friend',
    ];

    protected $rules = [
        'newContact.player_id' => 'required|exists:players,id',
        'newContact.name' => 'required|string|max:255',
        'newContact.phone' => ['nullable', 'string'],
        'newContact.phone_country' => ['nullable', 'string', 'size:2'],
        'newContact.notes' => 'nullable|string|max:500',
        'newContact.category' => 'required|in:friend,alliance,enemy,neutral',
    ];

    public function mount()
    {
        $this->loadContacts();
    }

    public function loadContacts()
    {
        $query = User::whereNotNull('phone')
            ->with(['player.world', 'player.alliance']);

        if ($this->searchTerm) {
            $query->where(function ($q): void {
                $q->where('name', 'like', "%{$this->searchTerm}%")
                    ->orWhere('email', 'like', "%{$this->searchTerm}%")
                    ->orWhere('phone', 'like', "%{$this->searchTerm}%")
                    ->orWhereHas('player', function ($playerQuery): void {
                        $playerQuery->where('name', 'like', "%{$this->searchTerm}%");
                    });
            });
        }

        if ($this->filterByAlliance) {
            $query->whereHas('player', function ($q): void {
                $q->where('alliance_id', $this->filterByAlliance);
            });
        }

        if ($this->filterByWorld) {
            $query->whereHas('player', function ($q): void {
                $q->where('world_id', $this->filterByWorld);
            });
        }

        $this->contacts = $query->paginate(20);
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
        $this->loadContacts();
    }

    public function updatedFilterByAlliance()
    {
        $this->resetPage();
        $this->loadContacts();
    }

    public function updatedFilterByWorld()
    {
        $this->resetPage();
        $this->loadContacts();
    }

    public function toggleAddContact()
    {
        $this->showAddContact = ! $this->showAddContact;
        if (! $this->showAddContact) {
            $this->resetNewContact();
        }
    }

    public function resetNewContact()
    {
        $this->newContact = [
            'player_id' => '',
            'name' => '',
            'phone' => '',
            'phone_country' => 'US',
            'notes' => '',
            'category' => 'friend',
        ];
        $this->resetValidation();
    }

    public function addContact()
    {
        $this->validate();

        $player = Player::find($this->newContact['player_id']);
        if (! $player || ! $player->user) {
            session()->flash('error', 'Player not found or has no associated user.');

            return;
        }

        // Update user's phone if provided
        if ($this->newContact['phone']) {
            $player->user->update([
                'phone' => $this->newContact['phone'],
                'phone_country' => $this->newContact['phone_country'],
            ]);
        }

        // Create contact entry (this would be a separate contacts table in a real implementation)
        session()->flash('message', 'Contact added successfully!');
        $this->toggleAddContact();
        $this->loadContacts();
    }

    public function selectAllContacts()
    {
        $this->selectedContacts = $this->contacts->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedContacts = [];
    }

    public function sendBulkSms()
    {
        if (empty($this->selectedContacts)) {
            session()->flash('error', 'Please select contacts to send SMS to.');

            return;
        }

        // This would integrate with the SmsNotificationService
        session()->flash('message', 'Bulk SMS functionality would be implemented here.');
    }

    public function exportContacts()
    {
        if (empty($this->selectedContacts)) {
            session()->flash('error', 'Please select contacts to export.');

            return;
        }

        $contacts = User::whereIn('id', $this->selectedContacts)
            ->whereNotNull('phone')
            ->with(['player'])
            ->get();

        $csvData = "Name,Email,Phone,Country,E164,Player Name,Alliance,World\n";

        foreach ($contacts as $contact) {
            $csvData .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $contact->name,
                $contact->email,
                $contact->phone ?? '',
                $contact->phone_country ?? '',
                $contact->phone_e164 ?? '',
                $contact->player->name ?? '',
                $contact->player->alliance->name ?? '',
                $contact->player->world->name ?? ''
            );
        }

        $this->dispatch('download-csv', [
            'data' => base64_encode($csvData),
            'filename' => 'contacts_export.csv',
        ]);

        session()->flash('message', 'Contacts exported successfully!');
    }

    public function getAvailablePlayers()
    {
        return Player::with(['user', 'world', 'alliance'])
            ->whereHas('user')
            ->whereDoesntHave('user', function ($query): void {
                $query->whereNotNull('phone');
            })
            ->get();
    }

    public function getAvailableAlliances()
    {
        return Player::whereNotNull('alliance_id')
            ->with('alliance')
            ->get()
            ->pluck('alliance')
            ->unique('id')
            ->values();
    }

    public function getAvailableWorlds()
    {
        return Player::whereNotNull('world_id')
            ->with('world')
            ->get()
            ->pluck('world')
            ->unique('id')
            ->values();
    }

    public function render()
    {
        return view('livewire.phone-contact-manager', [
            'contacts' => $this->contacts,
            'availablePlayers' => $this->getAvailablePlayers(),
            'availableAlliances' => $this->getAvailableAlliances(),
            'availableWorlds' => $this->getAvailableWorlds(),
        ]);
    }
}

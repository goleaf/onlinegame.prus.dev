<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\Rules\Phone;

class PhoneBulkOperations extends Component
{
    use WithFileUploads;

    public $selectedUsers = [];
    public $operation = '';
    public $phoneData = [];
    public $csvFile;
    public $showCsvUpload = false;
    public $processing = false;
    public $results = [];

    protected $rules = [
        'csvFile' => 'nullable|file|mimes:csv,txt|max:10240', // 10MB max
    ];

    public function mount()
    {
        $this->operation = '';
        $this->phoneData = [];
    }

    public function updatedOperation()
    {
        $this->reset(['selectedUsers', 'phoneData', 'results']);
    }

    public function selectAllUsers()
    {
        $this->selectedUsers = User::whereNotNull('phone')->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedUsers = [];
    }

    public function processBulkOperation()
    {
        if (empty($this->selectedUsers) || empty($this->operation)) {
            session()->flash('error', 'Please select users and an operation.');
            return;
        }

        $this->processing = true;
        $this->results = [];

        try {
            switch ($this->operation) {
                case 'export':
                    $this->exportPhoneNumbers();
                    break;
                case 'validate':
                    $this->validatePhoneNumbers();
                    break;
                case 'format':
                    $this->formatPhoneNumbers();
                    break;
                case 'update_from_csv':
                    $this->updateFromCsv();
                    break;
                case 'delete':
                    $this->deletePhoneNumbers();
                    break;
                default:
                    session()->flash('error', 'Invalid operation selected.');
                    return;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Operation failed: ' . $e->getMessage());
        }

        $this->processing = false;
    }

    private function exportPhoneNumbers()
    {
        $users = User::whereIn('id', $this->selectedUsers)
                    ->whereNotNull('phone')
                    ->get();

        $csvData = "ID,Name,Email,Phone,Country,E164,Normalized,National\n";
        
        foreach ($users as $user) {
            $csvData .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $user->id,
                $user->name,
                $user->email,
                $user->phone ?? '',
                $user->phone_country ?? '',
                $user->phone_e164 ?? '',
                $user->phone_normalized ?? '',
                $user->phone_national ?? ''
            );
        }

        $this->results = [
            'operation' => 'export',
            'success' => true,
            'message' => 'Phone numbers exported successfully.',
            'csv_data' => $csvData,
            'count' => $users->count()
        ];

        session()->flash('message', 'Phone numbers exported successfully.');
    }

    private function validatePhoneNumbers()
    {
        $users = User::whereIn('id', $this->selectedUsers)
                    ->whereNotNull('phone')
                    ->get();

        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $phoneNumber = phone($user->phone, $user->phone_country);
                $valid++;
            } catch (\Exception $e) {
                $invalid++;
                $errors[] = "User {$user->name} ({$user->email}): {$e->getMessage()}";
            }
        }

        $this->results = [
            'operation' => 'validate',
            'success' => true,
            'valid' => $valid,
            'invalid' => $invalid,
            'errors' => $errors,
            'message' => "Validation complete: {$valid} valid, {$invalid} invalid."
        ];

        session()->flash('message', "Validation complete: {$valid} valid, {$invalid} invalid.");
    }

    private function formatPhoneNumbers()
    {
        $users = User::whereIn('id', $this->selectedUsers)
                    ->whereNotNull('phone')
                    ->get();

        $updated = 0;
        $errors = [];

        foreach ($users as $user) {
            try {
                $phoneNumber = phone($user->phone, $user->phone_country);
                
                $user->update([
                    'phone_e164' => $phoneNumber->formatE164(),
                    'phone_normalized' => preg_replace('/[^0-9]/', '', $user->phone),
                    'phone_national' => preg_replace('/[^0-9]/', '', $phoneNumber->formatNational()),
                ]);
                
                $updated++;
            } catch (\Exception $e) {
                $errors[] = "User {$user->name}: {$e->getMessage()}";
            }
        }

        $this->results = [
            'operation' => 'format',
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
            'message' => "Formatting complete: {$updated} phone numbers updated."
        ];

        session()->flash('message', "Formatting complete: {$updated} phone numbers updated.");
    }

    private function updateFromCsv()
    {
        if (!$this->csvFile) {
            session()->flash('error', 'Please upload a CSV file.');
            return;
        }

        $this->validate();

        $csvData = array_map('str_getcsv', file($this->csvFile->getRealPath()));
        $headers = array_shift($csvData);
        
        $updated = 0;
        $errors = [];

        foreach ($csvData as $row) {
            $data = array_combine($headers, $row);
            
            if (isset($data['email']) && isset($data['phone'])) {
                $user = User::where('email', $data['email'])->first();
                
                if ($user) {
                    try {
                        $user->update([
                            'phone' => $data['phone'],
                            'phone_country' => $data['country'] ?? 'US',
                        ]);
                        $updated++;
                    } catch (\Exception $e) {
                        $errors[] = "User {$data['email']}: {$e->getMessage()}";
                    }
                } else {
                    $errors[] = "User not found: {$data['email']}";
                }
            }
        }

        $this->results = [
            'operation' => 'update_from_csv',
            'success' => true,
            'updated' => $updated,
            'errors' => $errors,
            'message' => "CSV update complete: {$updated} users updated."
        ];

        session()->flash('message', "CSV update complete: {$updated} users updated.");
    }

    private function deletePhoneNumbers()
    {
        $updated = User::whereIn('id', $this->selectedUsers)
                      ->update([
                          'phone' => null,
                          'phone_country' => null,
                          'phone_e164' => null,
                          'phone_normalized' => null,
                          'phone_national' => null,
                      ]);

        $this->results = [
            'operation' => 'delete',
            'success' => true,
            'deleted' => $updated,
            'message' => "Phone numbers deleted for {$updated} users."
        ];

        session()->flash('message', "Phone numbers deleted for {$updated} users.");
    }

    public function render()
    {
        $users = User::whereNotNull('phone')
                    ->when(!empty($this->selectedUsers), function ($query) {
                        $query->whereIn('id', $this->selectedUsers);
                    })
                    ->paginate(20);

        return view('livewire.phone-bulk-operations', [
            'users' => $users,
        ]);
    }
}

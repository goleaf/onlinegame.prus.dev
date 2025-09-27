<?php

namespace App\Livewire\Admin;

use App\Services\UpdaterService;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
#[Title('Application Updater')]
#[Layout('layouts.app')]
class UpdaterComponent extends Component
{

    public $currentVersion = '';
    public $latestVersion = '';
    public $updateAvailable = false;
    public $behindCommits = 0;
    public $isUpdating = false;
    public $updateSteps = [];
    public $systemInfo = [];
    public $updateHistory = [];
    public $error = null;

    protected UpdaterService $updaterService;

    public function boot(UpdaterService $updaterService)
    {
        $this->updaterService = $updaterService;
    }

    public function mount()
    {
        $this->loadUpdateInfo();
        $this->loadSystemInfo();
        $this->loadUpdateHistory();
    }

    public function loadUpdateInfo()
    {
        try {
            $updateInfo = $this->updaterService->checkForUpdates();
            
            $this->currentVersion = $updateInfo['current_version'];
            $this->latestVersion = $updateInfo['latest_version'];
            $this->updateAvailable = $updateInfo['update_available'];
            $this->behindCommits = $updateInfo['behind_commits'] ?? 0;
            $this->error = $updateInfo['error'] ?? null;
            
        } catch (\Exception $e) {
            $this->error = 'Failed to check for updates: ' . $e->getMessage();
            session()->flash('error', $this->error);
        }
    }

    public function loadSystemInfo()
    {
        try {
            $this->systemInfo = $this->updaterService->getSystemInfo();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load system information: ' . $e->getMessage());
        }
    }

    public function loadUpdateHistory()
    {
        try {
            $this->updateHistory = $this->updaterService->getUpdateHistory();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load update history: ' . $e->getMessage());
        }
    }

    public function checkForUpdates()
    {
        $this->loadUpdateInfo();
        
        if ($this->updateAvailable) {
            session()->flash('message', "Update available! Current: {$this->currentVersion}, Latest: {$this->latestVersion}");
        } else {
            session()->flash('message', 'Application is up to date!');
        }
    }

    public function performUpdate()
    {
        if (!$this->updateAvailable) {
            session()->flash('error', 'No updates available');
            return;
        }

        $this->isUpdating = true;
        $this->updateSteps = [];
        $this->error = null;

        try {
            $updateResult = $this->updaterService->performUpdate();
            
            $this->updateSteps = $updateResult['steps'] ?? [];
            
            if ($updateResult['success']) {
                session()->flash('message', 'Application updated successfully!');
                $this->loadUpdateInfo(); // Refresh version info
                $this->loadUpdateHistory(); // Refresh history
            } else {
                $this->error = $updateResult['error'] ?? 'Update failed';
                session()->flash('error', $this->error);
            }
            
        } catch (\Exception $e) {
            $this->error = 'Update failed: ' . $e->getMessage();
            session()->flash('error', $this->error);
        } finally {
            $this->isUpdating = false;
        }
    }

    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            
            session()->flash('message', 'Application caches cleared successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear caches: ' . $e->getMessage());
        }
    }

    public function optimizeApplication()
    {
        try {
            \Artisan::call('config:cache');
            \Artisan::call('route:cache');
            \Artisan::call('view:cache');
            
            session()->flash('message', 'Application optimized successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to optimize application: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.updater-component');
    }
}

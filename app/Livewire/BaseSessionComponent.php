<?php

namespace App\Livewire;

use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Base Livewire Component with Session Properties Support
 *
 * This component provides common session properties that can be used
 * across multiple components to maintain user preferences and state.
 */
abstract class BaseSessionComponent extends Component
{
    // Common session properties that can be inherited
    #[Session]
    public $theme = 'light';

    #[Session]
    public $language = 'en';

    #[Session]
    public $timezone = 'UTC';

    #[Session]
    public $notifications = true;

    #[Session]
    public $autoRefresh = true;

    #[Session]
    public $refreshInterval = 30;

    #[Session]
    public $itemsPerPage = 15;

    #[Session]
    public $sortBy = 'created_at';

    #[Session]
    public $sortOrder = 'desc';

    #[Session]
    public $filters = [];

    #[Session]
    public $searchQuery = '';

    #[Session]
    public $selectedItems = [];

    #[Session]
    public $expandedSections = [];

    #[Session]
    public $sidebarCollapsed = false;

    #[Session]
    public $activeTab = 'overview';

    #[Session]
    public $lastVisited = null;

    /**
     * Initialize session properties with default values
     */
    public function initializeSessionProperties(): void
    {
        // Set default timezone if not set
        if (! $this->timezone) {
            $this->timezone = config('app.timezone', 'UTC');
        }

        // Set default language if not set
        if (! $this->language) {
            $this->language = app()->getLocale();
        }

        // Initialize filters as empty array if not set
        if (! is_array($this->filters)) {
            $this->filters = [];
        }

        // Initialize selected items as empty array if not set
        if (! is_array($this->selectedItems)) {
            $this->selectedItems = [];
        }

        // Initialize expanded sections as empty array if not set
        if (! is_array($this->expandedSections)) {
            $this->expandedSections = [];
        }

        // Set last visited timestamp
        $this->lastVisited = now();
    }

    /**
     * Toggle theme between light and dark
     */
    public function toggleTheme(): void
    {
        $this->theme = $this->theme === 'light' ? 'dark' : 'light';
    }

    /**
     * Update refresh settings
     */
    public function updateRefreshSettings(bool $autoRefresh, int $interval): void
    {
        $this->autoRefresh = $autoRefresh;
        $this->refreshInterval = max(5, min(300, $interval)); // Limit between 5-300 seconds
    }

    /**
     * Toggle notification settings
     */
    public function toggleNotifications(): void
    {
        $this->notifications = ! $this->notifications;
    }

    /**
     * Update pagination settings
     */
    public function updatePaginationSettings(int $itemsPerPage): void
    {
        $this->itemsPerPage = max(5, min(100, $itemsPerPage)); // Limit between 5-100 items
    }

    /**
     * Update sorting settings
     */
    public function updateSortingSettings(string $sortBy, string $sortOrder): void
    {
        $this->sortBy = $sortBy;
        $this->sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
    }

    /**
     * Update filters
     */
    public function updateFilters(array $filters): void
    {
        $this->filters = array_filter($filters, fn ($value) => ! empty($value));
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->filters = [];
        $this->searchQuery = '';
    }

    /**
     * Toggle section expansion
     */
    public function toggleSection(string $section): void
    {
        if (in_array($section, $this->expandedSections)) {
            $this->expandedSections = array_filter($this->expandedSections, fn ($s) => $s !== $section);
        } else {
            $this->expandedSections[] = $section;
        }
    }

    /**
     * Toggle sidebar collapse
     */
    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = ! $this->sidebarCollapsed;
    }

    /**
     * Update active tab
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Get session property value with fallback
     */
    protected function getSessionProperty(string $property, mixed $default = null): mixed
    {
        return $this->$property ?? $default;
    }

    /**
     * Set session property value
     */
    protected function setSessionProperty(string $property, mixed $value): void
    {
        $this->$property = $value;
    }

    /**
     * Reset all session properties to defaults
     */
    public function resetSessionProperties(): void
    {
        $this->theme = 'light';
        $this->language = app()->getLocale();
        $this->timezone = config('app.timezone', 'UTC');
        $this->notifications = true;
        $this->autoRefresh = true;
        $this->refreshInterval = 30;
        $this->itemsPerPage = 15;
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';
        $this->filters = [];
        $this->searchQuery = '';
        $this->selectedItems = [];
        $this->expandedSections = [];
        $this->sidebarCollapsed = false;
        $this->activeTab = 'overview';
        $this->lastVisited = now();
    }
}

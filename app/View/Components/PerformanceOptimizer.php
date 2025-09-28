<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PerformanceOptimizer extends Component
{
    public bool $enablePreconnect;

    public bool $enableDnsPrefetch;

    public bool $enablePerformanceMonitoring;

    public bool $enableServiceWorker;

    /**
     * Create a new component instance.
     */
    public function __construct(
        bool $enablePreconnect = true,
        bool $enableDnsPrefetch = true,
        bool $enablePerformanceMonitoring = true,
        bool $enableServiceWorker = false
    ) {
        $this->enablePreconnect = $enablePreconnect;
        $this->enableDnsPrefetch = $enableDnsPrefetch;
        $this->enablePerformanceMonitoring = $enablePerformanceMonitoring;
        $this->enableServiceWorker = $enableServiceWorker;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.performance-optimizer');
    }
}

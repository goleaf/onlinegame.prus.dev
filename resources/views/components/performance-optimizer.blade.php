{{-- Performance Optimization Component --}}

{{-- Resource Hints --}}
@if($enablePreconnect || $enableDnsPrefetch)
    @if($enablePreconnect)
        {!! App\Helpers\PerformanceHelper::getPreconnectLinks() !!}
    @endif
    
    @if($enableDnsPrefetch)
        {!! App\Helpers\PerformanceHelper::getDnsPrefetchLinks() !!}
    @endif
@endif

{{-- Performance Monitoring --}}
@if($enablePerformanceMonitoring)
    {!! App\Helpers\PerformanceHelper::getPerformanceScript() !!}
@endif

{{-- Service Worker --}}
@if($enableServiceWorker)
    {!! App\Helpers\PerformanceHelper::getServiceWorkerScript() !!}
@endif

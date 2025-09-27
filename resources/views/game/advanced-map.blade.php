@extends('layouts.app')

@section('title', 'Advanced Map - Geographic Features')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        @livewire('game.advanced-map-viewer')
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Add any additional JavaScript for the advanced map here
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize any map-specific functionality
        console.log('Advanced Map Viewer loaded');
    });
</script>
@endpush

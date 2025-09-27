@extends('layouts.game')

@section('title', 'Game Chat')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Game Chat</h1>
            <p class="text-gray-600 mt-2">Real-time communication with other players</p>
        </div>

        @livewire('game.chat-manager')
    </div>
@endsection

@push('styles')
<style>
    #messages-container {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e0 #f7fafc;
    }
    
    #messages-container::-webkit-scrollbar {
        width: 6px;
    }
    
    #messages-container::-webkit-scrollbar-track {
        background: #f7fafc;
    }
    
    #messages-container::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }
    
    #messages-container::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }
</style>
@endpush

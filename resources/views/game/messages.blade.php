@extends('layouts.game')

@section('title', 'Message Center')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Message Center</h1>
            <p class="text-gray-600 mt-2">Manage your game communications and stay connected with other players</p>
        </div>

        @livewire('game.message-manager')
    </div>
@endsection

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

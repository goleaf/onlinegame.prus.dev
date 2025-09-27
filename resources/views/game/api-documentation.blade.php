@extends('layouts.app')

@section('title', 'API Documentation - Game')

@section('content')
<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Larautilx API Documentation</h1>
                <div class="flex space-x-4">
                    <a href="{{ route('game.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            @livewire('game.api-documentation')
        </div>
    </div>
</div>
@endsection

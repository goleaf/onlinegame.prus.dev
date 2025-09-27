@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Game Chat</h1>
    
    @livewire('game.chat-manager')
</div>
@endsection
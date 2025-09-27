@extends('layouts.travian')

@section('title', 'Real-time Updates')

@section('content')
    <div class="real-time-updates">
        <livewire:game.real-time-updater />
    </div>
@endsection

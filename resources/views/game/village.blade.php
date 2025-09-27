@extends('layouts.travian')

@section('title', 'Village Management')

@section('content')
    <div class="village-management">
        <livewire:game.village-manager :village="$village" />
    </div>
@endsection

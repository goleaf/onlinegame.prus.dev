@extends('layouts.app')

@section('title', 'Game Dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1>Game Dashboard</h1>
                <p>Welcome to the game! This is a simple dashboard to test if the route is working.</p>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Villages</h5>
                                <h2 class="text-primary">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Points</h5>
                                <h2 class="text-success">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Alliance</h5>
                                <p class="text-info">No Alliance</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Status</h5>
                                <p class="text-success">Online</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h3>Game Navigation</h3>
                    <div class="list-group">
                        <a href="{{ route('game.village', 1) }}" class="list-group-item list-group-item-action">Village</a>
                        <a href="{{ route('game.troops') }}" class="list-group-item list-group-item-action">Troops</a>
                        <a href="{{ route('game.movements') }}" class="list-group-item list-group-item-action">Movements</a>
                        <a href="{{ route('game.alliance') }}" class="list-group-item list-group-item-action">Alliance</a>
                        <a href="{{ route('game.quests') }}" class="list-group-item list-group-item-action">Quests</a>
                        <a href="{{ route('game.technology') }}"
                           class="list-group-item list-group-item-action">Technology</a>
                        <a href="{{ route('game.reports') }}" class="list-group-item list-group-item-action">Reports</a>
                        <a href="{{ route('game.map') }}" class="list-group-item list-group-item-action">Map</a>
                        <a href="{{ route('game.statistics') }}"
                           class="list-group-item list-group-item-action">Statistics</a>
                        <a href="{{ route('game.battles') }}" class="list-group-item list-group-item-action">Battles</a>
                        <a href="{{ route('game.market') }}" class="list-group-item list-group-item-action">Market</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', text('game.dashboard.title', 'Game Dashboard'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1>@text('game.dashboard.title', 'Game Dashboard')</h1>
                <p>@text('game.dashboard.welcome', 'Welcome to the game! This is a simple dashboard to test if the route is working.')</p>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">@text('game.stats.villages', 'Villages')</h5>
                                <h2 class="text-primary">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">@text('game.stats.points', 'Points')</h5>
                                <h2 class="text-success">0</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">@text('game.stats.alliance', 'Alliance')</h5>
                                <p class="text-info">@text('game.stats.no_alliance', 'No Alliance')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">@text('game.stats.status', 'Status')</h5>
                                <p class="text-success">@text('game.stats.online', 'Online')</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h3>@text('game.navigation.title', 'Game Navigation')</h3>
                    <div class="list-group">
                        <a href="{{ route('game.village', 1) }}" class="list-group-item list-group-item-action">@text('game.navigation.village', 'Village')</a>
                        <a href="{{ route('game.troops') }}" class="list-group-item list-group-item-action">@text('game.navigation.troops', 'Troops')</a>
                        <a href="{{ route('game.movements') }}" class="list-group-item list-group-item-action">@text('game.navigation.movements', 'Movements')</a>
                        <a href="{{ route('game.alliance') }}" class="list-group-item list-group-item-action">@text('game.navigation.alliance', 'Alliance')</a>
                        <a href="{{ route('game.quests') }}" class="list-group-item list-group-item-action">@text('game.navigation.quests', 'Quests')</a>
                        <a href="{{ route('game.technology') }}"
                           class="list-group-item list-group-item-action">@text('game.navigation.technology', 'Technology')</a>
                        <a href="{{ route('game.reports') }}" class="list-group-item list-group-item-action">@text('game.navigation.reports', 'Reports')</a>
                        <a href="{{ route('game.map') }}" class="list-group-item list-group-item-action">@text('game.navigation.map', 'Map')</a>
                        <a href="{{ route('game.statistics') }}"
                           class="list-group-item list-group-item-action">@text('game.navigation.statistics', 'Statistics')</a>
                        <a href="{{ route('game.battles') }}" class="list-group-item list-group-item-action">@text('game.navigation.battles', 'Battles')</a>
                        <a href="{{ route('game.market') }}" class="list-group-item list-group-item-action">@text('game.navigation.market', 'Market')</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

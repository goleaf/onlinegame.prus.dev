@extends('layouts.app')

@section('title', text('game.no_player.title', 'No Player Found'))

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>@text('game.no_player.title', 'No Player Found')</h4>
                    </div>
                    <div class="card-body">
                        <p>@text('game.no_player.hello', 'Hello') {{ $user->name }},</p>
                        <p>@text('game.no_player.message', "You don't have a player account yet. Please create a player to start playing the game.")</p>

                        <div class="alert alert-info">
                            <h5>@text('game.no_player.how_to_create', 'How to create a player:')</h5>
                            <ol>
                                <li>@text('game.no_player.contact_admin', 'Contact an administrator to create a player account for you')</li>
                                <li>@text('game.no_player.use_system', 'Or use the player creation system if available')</li>
                            </ol>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary me-2">Admin Panel</a>
                            <a href="{{ route('logout') }}" class="btn btn-secondary">@text('game.logout', 'Logout')</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

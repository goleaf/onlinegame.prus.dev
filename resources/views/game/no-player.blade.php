@extends('layouts.app')

@section('title', 'No Player Found')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>No Player Found</h4>
                    </div>
                    <div class="card-body">
                        <p>Hello {{ $user->name }},</p>
                        <p>You don't have a player account yet. Please create a player to start playing the game.</p>

                        <div class="alert alert-info">
                            <h5>How to create a player:</h5>
                            <ol>
                                <li>Contact an administrator to create a player account for you</li>
                                <li>Or use the player creation system if available</li>
                            </ol>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('logout') }}" class="btn btn-secondary">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

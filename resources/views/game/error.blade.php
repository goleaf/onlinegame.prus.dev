@extends('layouts.app')

@section('title', 'Game Error')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Game Error</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <h5>An error occurred while loading the game:</h5>
                            <p><strong>Error:</strong> {{ $error }}</p>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('game.dashboard') }}" class="btn btn-primary me-2">Try Again</a>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-info me-2">Admin Panel</a>
                            <a href="{{ route('logout') }}" class="btn btn-secondary">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

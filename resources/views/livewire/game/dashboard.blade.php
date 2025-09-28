<div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Your Villages</h3>
                </div>
                <div class="card-body">
                    @if ($villages->count() > 0)
                        <div class="village-grid">
                            @foreach ($villages as $village)
                                <div class="village-card">
                                    <h5>{{ $village->name }}</h5>
                                    <p>Coordinates: {{ $village->coordinates }}</p>
                                    <p>Population: {{ $village->population }}</p>
                                    <p>Status: {{ $village->is_capital ? 'Capital' : 'Village' }}</p>
                                    <button wire:click.prevent="enterVillage({{ $village->id }})" class="btn btn-primary btn-sm">
                                        Enter Village
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <h4>No villages found</h4>
                            <p>Start by creating your first village!</p>
                            <button wire:click.prevent="createVillage" class="btn btn-success">
                                Create First Village
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Resources</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-tree text-success"></i> Wood</span>
                        <span class="badge bg-success">{{ number_format($resources['wood']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-mountain text-warning"></i> Clay</span>
                        <span class="badge bg-warning">{{ number_format($resources['clay']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-hammer text-secondary"></i> Iron</span>
                        <span class="badge bg-secondary">{{ number_format($resources['iron']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-seedling text-success"></i> Crop</span>
                        <span class="badge bg-success">{{ number_format($resources['crop']) }}</span>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('game.map') }}" class="btn btn-outline-primary">
                            <i class="fas fa-map"></i> World Map
                        </a>
                        <button wire:click="createVillage" class="btn btn-outline-success">
                            <i class="fas fa-plus"></i> Create Village
                        </button>
                        <a href="#" class="btn btn-outline-secondary">
                            <i class="fas fa-users"></i> Alliance
                        </a>
                        <a href="#" class="btn btn-outline-info">
                            <i class="fas fa-envelope"></i> Messages
                        </a>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4>Game Statistics</h4>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5>{{ $villages->count() }}</h5>
                            <small>Villages</small>
                        </div>
                        <div class="col-6">
                            <h5>{{ $villages->sum('population') }}</h5>
                            <small>Population</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>

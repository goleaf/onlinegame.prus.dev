@extends('layouts.travian')

@section('title', 'Market Manager')

@section('content')
    <div>
        <!-- Village Header -->
        <div class="village-info">
            <h3>{{ $village->name ?? 'Main Village' }}</h3>
            <p><strong>Coordinates:</strong> {{ $village->coordinates ?? '(0|0)' }}</p>
            <p><strong>Population:</strong> {{ $village->population ?? 100 }}</p>
        </div>

        <!-- Current Resources -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Current Resources</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($availableResources as $resource)
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <img src="{{ asset('img/r/' . $resource['type'] . '.gif') }}"
                                                 alt="{{ ucfirst($resource['type']) }}" class="img-fluid mb-2"
                                                 style="max-width: 40px;">
                                            <h6>{{ ucfirst($resource['type']) }}</h6>
                                            <h4 class="text-primary">{{ number_format($resource['amount']) }}</h4>
                                            <small class="text-muted">Available</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Trade -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Create New Trade</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="offerType">Offer Resource:</label>
                                    <select class="form-control" wire:model="newTrade.offer_type" id="offerType">
                                        <option value="">Select Resource</option>
                                        <option value="wood">Wood</option>
                                        <option value="clay">Clay</option>
                                        <option value="iron">Iron</option>
                                        <option value="crop">Crop</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="offerAmount">Offer Amount:</label>
                                    <input type="number" class="form-control" wire:model="newTrade.offer_amount"
                                           id="offerAmount" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="demandType">Demand Resource:</label>
                                    <select class="form-control" wire:model="newTrade.demand_type" id="demandType">
                                        <option value="">Select Resource</option>
                                        <option value="wood">Wood</option>
                                        <option value="clay">Clay</option>
                                        <option value="iron">Iron</option>
                                        <option value="crop">Crop</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="demandAmount">Demand Amount:</label>
                                    <input type="number" class="form-control" wire:model="newTrade.demand_amount"
                                           id="demandAmount" min="1">
                                </div>
                            </div>
                        </div>

                        @if ($newTrade['offer_type'] && $newTrade['demand_type'] && $newTrade['offer_amount'] && $newTrade['demand_amount'])
                            <div class="trade-preview">
                                <h6>Trade Preview:</h6>
                                <div class="trade-details">
                                    <div class="trade-offer">
                                        <img src="{{ asset('img/r/' . $newTrade['offer_type'] . '.gif') }}"
                                             alt="{{ ucfirst($newTrade['offer_type']) }}" class="resource-icon">
                                        <span>{{ number_format($newTrade['offer_amount']) }}
                                            {{ ucfirst($newTrade['offer_type']) }}</span>
                                    </div>
                                    <div class="trade-arrow">→</div>
                                    <div class="trade-demand">
                                        <img src="{{ asset('img/r/' . $newTrade['demand_type'] . '.gif') }}"
                                             alt="{{ ucfirst($newTrade['demand_type']) }}" class="resource-icon">
                                        <span>{{ number_format($newTrade['demand_amount']) }}
                                            {{ ucfirst($newTrade['demand_type']) }}</span>
                                    </div>
                                </div>
                                <div class="trade-ratio">
                                    <strong>Ratio:</strong> 1 {{ ucfirst($newTrade['offer_type']) }} =
                                    {{ number_format($newTrade['demand_amount'] / $newTrade['offer_amount'], 2) }}
                                    {{ ucfirst($newTrade['demand_type']) }}
                                </div>
                            </div>
                        @endif

                        <div class="mt-3">
                            <button class="btn btn-primary" wire:click="createTrade">
                                Create Trade
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Trades -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>My Trades</h5>
                    </div>
                    <div class="card-body">
                        @if ($myTrades && $myTrades->count() > 0)
                            <div class="list-group">
                                @foreach ($myTrades as $trade)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                {{ ucfirst($trade->offer_type) }} → {{ ucfirst($trade->demand_type) }}
                                            </h6>
                                            <span
                                                  class="badge bg-{{ $trade->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($trade->status) }}
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Offer:</strong> {{ number_format($trade->offer_amount) }}
                                            {{ ucfirst($trade->offer_type) }}<br>
                                            <strong>Demand:</strong> {{ number_format($trade->demand_amount) }}
                                            {{ ucfirst($trade->demand_type) }}<br>
                                            <strong>Ratio:</strong> 1 {{ ucfirst($trade->offer_type) }} =
                                            {{ number_format($trade->ratio, 2) }} {{ ucfirst($trade->demand_type) }}
                                        </p>
                                        <div class="trade-actions">
                                            @if ($trade->status === 'active')
                                                <button class="btn btn-sm btn-danger"
                                                        wire:click="cancelTrade({{ $trade->id }})">
                                                    Cancel Trade
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No active trades</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Trades -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Available Trades</h5>
                    </div>
                    <div class="card-body">
                        @if ($marketTrades && $marketTrades->count() > 0)
                            <div class="list-group">
                                @foreach ($marketTrades as $trade)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                {{ ucfirst($trade->offer_type) }} → {{ ucfirst($trade->demand_type) }}
                                            </h6>
                                            <span class="badge bg-primary">
                                                {{ ucfirst($trade->status) }}
                                            </span>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Offer:</strong> {{ number_format($trade->offer_amount) }}
                                            {{ ucfirst($trade->offer_type) }}<br>
                                            <strong>Demand:</strong> {{ number_format($trade->demand_amount) }}
                                            {{ ucfirst($trade->demand_type) }}<br>
                                            <strong>Ratio:</strong> 1 {{ ucfirst($trade->offer_type) }} =
                                            {{ number_format($trade->ratio, 2) }} {{ ucfirst($trade->demand_type) }}<br>
                                            <strong>Player:</strong> {{ $trade->player->name ?? 'Unknown' }}
                                        </p>
                                        <div class="trade-actions">
                                            <button class="btn btn-sm btn-success"
                                                    wire:click="acceptTrade({{ $trade->id }})">
                                                Accept Trade
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No available trades</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Trade History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Trade History</h5>
                    </div>
                    <div class="card-body">
                        @if ($tradeHistory && $tradeHistory->count() > 0)
                            <div class="list-group">
                                @foreach ($tradeHistory as $trade)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                {{ ucfirst($trade->offer_type) }} → {{ ucfirst($trade->demand_type) }}
                                            </h6>
                                            <small>{{ $trade->completed_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">
                                            <strong>Amount Traded:</strong> {{ number_format($trade->amount_traded) }}<br>
                                            <strong>Resources Exchanged:</strong>
                                            @foreach (json_decode($trade->resources_exchanged, true) as $resource => $amount)
                                                <span class="badge bg-info me-1">
                                                    {{ number_format($amount) }} {{ ucfirst($resource) }}
                                                </span>
                                            @endforeach
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No trade history</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .trade-preview {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 15px 0;
            }

            .trade-details {
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 10px 0;
            }

            .trade-offer,
            .trade-demand {
                display: flex;
                align-items: center;
                padding: 10px;
                background: white;
                border-radius: 5px;
                margin: 0 10px;
            }

            .trade-arrow {
                font-size: 24px;
                color: #6c757d;
            }

            .trade-ratio {
                text-align: center;
                margin-top: 10px;
            }

            .trade-actions {
                margin-top: 10px;
            }

            .resource-icon {
                width: 20px;
                height: 20px;
                margin-right: 5px;
            }
        </style>
    @endpush
@endsection

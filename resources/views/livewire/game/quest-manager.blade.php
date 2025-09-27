@extends('layouts.travian')

@section('title', 'Quest Manager')

@section('content')
    <div>
        <!-- Player Header -->
        <div class="village-info">
            <h3>{{ $player->name ?? 'Player' }}</h3>
            <p><strong>Tribe:</strong> {{ $player->tribe ?? 'Roman' }}</p>
            <p><strong>Points:</strong> {{ $player->points ?? 0 }}</p>
        </div>

        <!-- Quest Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Quest Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary">{{ $questStats['total_quests'] ?? 0 }}</h4>
                                    <small class="text-muted">Total Quests</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success">{{ $questStats['completed_quests'] ?? 0 }}</h4>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning">{{ $questStats['active_quests'] ?? 0 }}</h4>
                                    <small class="text-muted">Active</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info">{{ $questStats['available_quests'] ?? 0 }}</h4>
                                    <small class="text-muted">Available</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Quests -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Available Quests</h5>
                    </div>
                    <div class="card-body">
                        @if ($availableQuests && $availableQuests->count() > 0)
                            <div class="row">
                                @foreach ($availableQuests as $quest)
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">{{ $quest->name }}</h6>
                                                <p class="card-text">{{ $quest->description }}</p>
                                                <div class="quest-info">
                                                    <span
                                                          class="badge bg-{{ $quest->type === 'tutorial' ? 'primary' : ($quest->type === 'daily' ? 'success' : 'info') }}">
                                                        {{ ucfirst($quest->type) }}
                                                    </span>
                                                    @if ($quest->rewards)
                                                        <small class="text-muted">
                                                            Rewards:
                                                            {{ json_decode($quest->rewards, true)['experience'] ?? 0 }} XP
                                                        </small>
                                                    @endif
                                                </div>
                                                <div class="quest-actions mt-3">
                                                    <button class="btn btn-primary btn-sm"
                                                            wire:click="startQuest({{ $quest->id }})">
                                                        Start Quest
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No available quests</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Quests -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Active Quests</h5>
                    </div>
                    <div class="card-body">
                        @if ($activeQuests && $activeQuests->count() > 0)
                            <div class="list-group">
                                @foreach ($activeQuests as $quest)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $quest->name }}</h6>
                                            <span class="badge bg-warning">In Progress</span>
                                        </div>
                                        <p class="mb-1">{{ $quest->description }}</p>
                                        <div class="quest-progress">
                                            <div class="progress mb-2">
                                                <div class="progress-bar" style="width: {{ $quest->progress }}%"></div>
                                            </div>
                                            <small class="text-muted">Progress: {{ $quest->progress }}%</small>
                                        </div>
                                        <div class="quest-requirements">
                                            @if ($quest->requirements)
                                                @foreach (json_decode($quest->requirements, true) as $requirement => $value)
                                                    <div class="requirement-item">
                                                        <strong>{{ ucfirst($requirement) }}:</strong> {{ $value }}
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div class="quest-actions mt-2">
                                            <button class="btn btn-success btn-sm"
                                                    wire:click="completeQuest({{ $quest->id }})">
                                                Complete Quest
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No active quests</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Quests -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Completed Quests</h5>
                    </div>
                    <div class="card-body">
                        @if ($completedQuests && $completedQuests->count() > 0)
                            <div class="list-group">
                                @foreach ($completedQuests as $quest)
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $quest->name }}</h6>
                                            <span class="badge bg-success">Completed</span>
                                        </div>
                                        <p class="mb-1">{{ $quest->description }}</p>
                                        <small class="text-muted">
                                            Completed: {{ $quest->completed_at->diffForHumans() }}
                                        </small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No completed quests</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quest Details Modal -->
        @if ($selectedQuest)
            <div class="modal fade show" style="display: block;" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $selectedQuest->name }}</h5>
                            <button type="button" class="btn-close" wire:click="closeQuestModal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="quest-details">
                                <h6>Description</h6>
                                <p>{{ $selectedQuest->description }}</p>

                                <h6>Requirements</h6>
                                @if ($selectedQuest->requirements)
                                    <div class="requirements-list">
                                        @foreach (json_decode($selectedQuest->requirements, true) as $requirement => $value)
                                            <div class="requirement-item">
                                                <strong>{{ ucfirst($requirement) }}:</strong> {{ $value }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">No specific requirements</p>
                                @endif

                                <h6>Rewards</h6>
                                @if ($selectedQuest->rewards)
                                    <div class="rewards-list">
                                        @foreach (json_decode($selectedQuest->rewards, true) as $reward => $value)
                                            <div class="reward-item">
                                                <strong>{{ ucfirst($reward) }}:</strong> {{ $value }}
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">No rewards specified</p>
                                @endif

                                @if ($selectedQuest->type === 'daily')
                                    <div class="quest-timer">
                                        <small class="text-muted">
                                            This quest resets daily at midnight.
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeQuestModal">Close</button>
                            @if ($selectedQuest->status === 'pending')
                                <button type="button" class="btn btn-primary"
                                        wire:click="startQuest({{ $selectedQuest->id }})">
                                    Start Quest
                                </button>
                            @elseif($selectedQuest->status === 'in_progress')
                                <button type="button" class="btn btn-success"
                                        wire:click="completeQuest({{ $selectedQuest->id }})">
                                    Complete Quest
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        @endif
    </div>

    @push('styles')
        <style>
            .quest-info {
                margin: 10px 0;
            }

            .quest-progress {
                margin: 10px 0;
            }

            .quest-requirements {
                margin: 10px 0;
            }

            .requirement-item {
                margin: 5px 0;
                padding: 5px;
                background: #f8f9fa;
                border-radius: 3px;
            }

            .rewards-list {
                margin: 10px 0;
            }

            .reward-item {
                margin: 5px 0;
                padding: 5px;
                background: #d4edda;
                border-radius: 3px;
            }

            .quest-actions {
                margin-top: 15px;
            }

            .quest-timer {
                margin-top: 15px;
                padding: 10px;
                background: #fff3cd;
                border-radius: 5px;
                border: 1px solid #ffeaa7;
            }

            .modal.show {
                display: block;
            }

            .modal-backdrop.show {
                opacity: 0.5;
            }
        </style>
    @endpush
@endsection

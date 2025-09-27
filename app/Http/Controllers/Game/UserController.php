<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LarautilxIntegrationService;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Intervention\Validation\Rules\Username;
use JonPurvis\Squeaky\Rules\Clean;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FeatureToggleUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Propaganistas\LaravelPhone\Rules\Phone;
use Ziming\LaravelZxcvbn\Rules\ZxcvbnRule;

class UserController extends CrudController
{
    use ApiResponseTrait;

    protected Model $model;
    protected RateLimiterUtil $rateLimiter;

    protected array $validationRules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'phone' => 'nullable|string',
        'phone_country' => 'nullable|string|size:2',
    ];

    protected array $searchableFields = ['name', 'email', 'phone', 'phone_normalized', 'phone_e164'];
    protected array $relationships = ['player', 'players'];
    protected int $perPage = 20;

    public function __construct(User $user, RateLimiterUtil $rateLimiter)
    {
        $this->model = $user;
        $this->rateLimiter = $rateLimiter;
        parent::__construct($this->model);
    }

    /**
     * Get users with game statistics
     */
    public function withGameStats(Request $request)
    {
        // Rate limiting for user statistics
        $rateLimitKey = 'user_stats_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        $query = User::withGamePlayers()
            ->with($this->relationships);

        // Build filters array for eloquent filtering
        $filters = [];

        // Apply search filter
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $filters[] = [
                'type' => '$or',
                'value' => [
                    ['target' => 'name', 'type' => '$like', 'value' => $searchTerm],
                    ['target' => 'email', 'type' => '$like', 'value' => $searchTerm],
                    ['target' => 'phone', 'type' => '$like', 'value' => $searchTerm],
                    ['target' => 'phone_normalized', 'type' => '$like', 'value' => preg_replace('/[^0-9+]/', '', $searchTerm)],
                    ['target' => 'phone_e164', 'type' => '$like', 'value' => preg_replace('/[^0-9+]/', '', $searchTerm)],
                    [
                        'type' => '$has',
                        'target' => 'player',
                        'value' => [
                            ['target' => 'name', 'type' => '$like', 'value' => $searchTerm]
                        ]
                    ]
                ]
            ];
        }

        // Apply relationship filters
        if ($request->has('world_id')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'world_id', 'type' => '$eq', 'value' => $request->get('world_id')]
                ]
            ];
        }

        if ($request->has('tribe')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'tribe', 'type' => '$eq', 'value' => $request->get('tribe')]
                ]
            ];
        }

        if ($request->has('alliance_id')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'alliance_id', 'type' => '$eq', 'value' => $request->get('alliance_id')]
                ]
            ];
        }

        if ($request->has('is_online')) {
            if ($request->get('is_online') === 'true') {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_online', 'type' => '$eq', 'value' => true]
                    ]
                ];
            } else {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_online', 'type' => '$eq', 'value' => false]
                    ]
                ];
            }
        }

        if ($request->has('is_active')) {
            if ($request->get('is_active') === 'true') {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_active', 'type' => '$eq', 'value' => true]
                    ]
                ];
            } else {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_active', 'type' => '$eq', 'value' => false]
                    ]
                ];
            }
        }

        // Apply eloquent filtering
        if (!empty($filters)) {
            $query = $query->filter($filters);
        }

        // Apply sorting
        if ($request->has('sort_by')) {
            $direction = $request->get('sort_direction', 'desc');
            $sortBy = $request->get('sort_by');

            if (in_array($sortBy, ['name', 'email', 'created_at'])) {
                $query->orderBy($sortBy, $direction);
            } elseif ($sortBy === 'player_points') {
                $query
                    ->join('players', 'users.id', '=', 'players.user_id')
                    ->orderBy('players.points', $direction)
                    ->select('users.*');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate($request->get('per_page', $this->perPage));

        // Add game statistics to each user
        $users->getCollection()->transform(function ($user) {
            $user->game_stats = $user->getGameStats();
            return $user;
        });

        // Log the request
        LoggingUtil::info('Users with game stats retrieved', [
            'user_id' => auth()->id(),
            'filters' => $request->all(),
            'total_users' => $users->total(),
        ], 'user_management');

        return $this->paginatedResponse($users, 'Users with game statistics retrieved successfully.');
    }

    /**
     * Get online users
     */
    public function online(Request $request)
    {
        $query = User::onlineUsers()
            ->with(['player.world', 'player.alliance']);

        if ($request->has('world_id')) {
            $query->byWorld($request->get('world_id'));
        }

        $users = $query
            ->orderBy('players.last_active_at', 'desc')
            ->paginate($request->get('per_page', 50));

        // Log the request
        LoggingUtil::info('Online users retrieved', [
            'user_id' => auth()->id(),
            'world_id' => $request->get('world_id'),
            'total_online' => $users->total(),
        ], 'user_management');

        return $this->paginatedResponse($users, 'Online users retrieved successfully.');
    }

    /**
     * Get user activity statistics
     */
    public function activityStats(Request $request)
    {
        $integrationService = app(LarautilxIntegrationService::class);

        $stats = $integrationService->cacheGameData(
            'user_activity_stats',
            function () {
                return [
                    'total_users' => User::count(),
                    'users_with_players' => User::withGamePlayers()->count(),
                    'active_game_users' => User::activeGameUsers()->count(),
                    'online_users' => User::onlineUsers()->count(),
                    'users_by_tribe' => User::withGamePlayers()
                        ->join('players', 'users.id', '=', 'players.user_id')
                        ->selectRaw('players.tribe, COUNT(*) as count')
                        ->groupBy('players.tribe')
                        ->pluck('count', 'tribe'),
                    'users_by_world' => User::withGamePlayers()
                        ->join('players', 'users.id', '=', 'players.user_id')
                        ->selectRaw('players.world_id, COUNT(*) as count')
                        ->groupBy('players.world_id')
                        ->pluck('count', 'world_id'),
                    'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
                    'recent_activity' => User::whereHas('player', function ($q) {
                        $q->where('last_active_at', '>=', now()->subHours(24));
                    })->count(),
                ];
            },
            300  // 5 minutes cache
        );

        return $this->successResponse($stats, 'User activity statistics retrieved successfully.');
    }

    /**
     * Get user details with comprehensive information
     */
    public function details($userId)
    {
        $user = User::with([
            'player.world',
            'player.alliance',
            'player.villages' => function ($query) {
                $query->withStats();
            }
        ])->findOrFail($userId);

        $details = [
            'user' => $user,
            'game_stats' => $user->getGameStats(),
            'is_online' => $user->isOnline(),
            'last_activity' => $user->getLastActivity(),
            'villages' => $user->getVillages(),
            'capital_village' => $user->getCapitalVillage(),
            'activity_summary' => [
                'total_villages' => $user->getVillages()->count(),
                'total_population' => $user->getVillages()->sum('population'),
                'has_active_session' => $user->hasActiveGameSession(),
            ],
        ];

        // Log the request
        LoggingUtil::info('User details retrieved', [
            'requested_by' => auth()->id(),
            'target_user_id' => $userId,
            'user_has_player' => $user->player ? true : false,
        ], 'user_management');

        return $this->successResponse($details, 'User details retrieved successfully.');
    }

    /**
     * Update user status
     */
    public function updateStatus(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'last_active_at' => 'nullable|date',
        ]);

        // Update user's player status if they have one
        if ($user->player) {
            $user->player->update($validated);
        }

        // Log the status update
        LoggingUtil::info('User status updated', [
            'updated_by' => auth()->id(),
            'target_user_id' => $userId,
            'changes' => $validated,
        ], 'user_management');

        return $this->successResponse($user->fresh(['player']), 'User status updated successfully.');
    }

    /**
     * Create user with password strength validation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $request->email,
                    $request->name,
                ]),
            ],
            'phone' => 'nullable|string',
            'phone_country' => 'nullable|string|size:2',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'phone_country' => $validated['phone_country'] ?? null,
        ]);

        // Log the user creation
        LoggingUtil::info('User created via admin', [
            'created_by' => auth()->id(),
            'new_user_id' => $user->id,
            'new_user_email' => $user->email,
        ], 'user_management');

        return $this->successResponse($user, 'User created successfully.');
    }

    /**
     * Update user password with strength validation
     */
    public function updatePassword(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                'min:8',
                new ZxcvbnRule([
                    $user->email,
                    $user->name,
                ]),
            ],
        ]);

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        // Log the password update
        LoggingUtil::info('User password updated via admin', [
            'updated_by' => auth()->id(),
            'target_user_id' => $userId,
        ], 'user_management');

        return $this->successResponse($user, 'User password updated successfully.');
    }

    /**
     * Get user's game history
     */
    public function gameHistory($userId)
    {
        $user = User::with(['player'])->findOrFail($userId);

        if (!$user->player) {
            return $this->errorResponse('User does not have a game player.', 404);
        }

        $history = [
            'player_created_at' => $user->player->created_at,
            'last_active_at' => $user->player->last_active_at,
            'total_points' => $user->player->points,
            'village_count' => $user->player->villages->count(),
            'alliance_history' => $user->player->alliance_id ? [
                'current_alliance_id' => $user->player->alliance_id,
                'joined_at' => $user->player->updated_at,  // This would need a proper field
            ] : null,
            'world_info' => $user->player->world ? [
                'world_id' => $user->player->world->id,
                'world_name' => $user->player->world->name,
                'joined_at' => $user->player->created_at,
            ] : null,
        ];

        return $this->successResponse($history, 'User game history retrieved successfully.');
    }

    /**
     * Search users with advanced filtering
     */
    public function search(Request $request)
    {
        $query = User::query();

        // Build filters array for eloquent filtering
        $filters = [];

        // Apply search filters
        if ($request->has('name')) {
            $filters[] = ['target' => 'name', 'type' => '$like', 'value' => $request->get('name')];
        }

        if ($request->has('email')) {
            $filters[] = ['target' => 'email', 'type' => '$like', 'value' => $request->get('email')];
        }

        if ($request->has('phone')) {
            $phoneTerm = $request->get('phone');
            $cleanPhoneTerm = preg_replace('/[^0-9+]/', '', $phoneTerm);
            
            $filters[] = [
                'type' => '$or',
                'value' => [
                    ['target' => 'phone', 'type' => '$like', 'value' => $phoneTerm],
                    ['target' => 'phone_normalized', 'type' => '$like', 'value' => $cleanPhoneTerm],
                    ['target' => 'phone_e164', 'type' => '$like', 'value' => $cleanPhoneTerm]
                ]
            ];
        }

        if ($request->has('has_player')) {
            if ($request->get('has_player') === 'true') {
                $filters[] = ['type' => '$has', 'target' => 'player'];
                $query = $query->withGamePlayers();
            } else {
                $filters[] = ['type' => '$doesntHas', 'target' => 'player'];
            }
        }

        if ($request->has('world_id')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'world_id', 'type' => '$eq', 'value' => $request->get('world_id')]
                ]
            ];
        }

        if ($request->has('tribe')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'tribe', 'type' => '$eq', 'value' => $request->get('tribe')]
                ]
            ];
        }

        if ($request->has('alliance_id')) {
            $filters[] = [
                'type' => '$has',
                'target' => 'player',
                'value' => [
                    ['target' => 'alliance_id', 'type' => '$eq', 'value' => $request->get('alliance_id')]
                ]
            ];
        }

        if ($request->has('is_online')) {
            if ($request->get('is_online') === 'true') {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_online', 'type' => '$eq', 'value' => true]
                    ]
                ];
            }
        }

        if ($request->has('is_active')) {
            if ($request->get('is_active') === 'true') {
                $filters[] = [
                    'type' => '$has',
                    'target' => 'player',
                    'value' => [
                        ['target' => 'is_active', 'type' => '$eq', 'value' => true]
                    ]
                ];
            }
        }

        // Apply eloquent filtering
        if (!empty($filters)) {
            $query = $query->filter($filters);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $users = $query
            ->with(['player.world', 'player.alliance'])
            ->paginate($request->get('per_page', $this->perPage));

        // Add game stats to each user
        $users->getCollection()->transform(function ($user) {
            $user->game_stats = $user->getGameStats();
            $user->is_online = $user->isOnline();
            return $user;
        });

        return $this->paginatedResponse($users, 'Users found successfully.');
    }

    /**
     * Get feature toggles for user
     */
    public function featureToggles($userId)
    {
        $user = User::findOrFail($userId);

        $features = [
            'advanced_map' => FeatureToggleUtil::isEnabled('advanced_map'),
            'real_time_updates' => FeatureToggleUtil::isEnabled('real_time_updates'),
            'enhanced_statistics' => FeatureToggleUtil::isEnabled('enhanced_statistics'),
            'geographic_features' => FeatureToggleUtil::isEnabled('geographic_features'),
            'larautilx_integration' => FeatureToggleUtil::isEnabled('larautilx_integration'),
        ];

        return $this->successResponse($features, 'User feature toggles retrieved successfully.');
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'status' => 'required|array',
            'status.is_active' => 'boolean',
            'status.is_online' => 'boolean',
        ]);

        $updatedCount = 0;

        foreach ($validated['user_ids'] as $userId) {
            $user = User::find($userId);
            if ($user && $user->player) {
                $user->player->update($validated['status']);
                $updatedCount++;
            }
        }

        // Log the bulk update
        LoggingUtil::info('Bulk user status update', [
            'updated_by' => auth()->id(),
            'user_ids' => $validated['user_ids'],
            'status' => $validated['status'],
            'updated_count' => $updatedCount,
        ], 'user_management');

        return $this->successResponse([
            'updated_count' => $updatedCount,
            'total_requested' => count($validated['user_ids']),
        ], "Successfully updated {$updatedCount} users.");
    }
}

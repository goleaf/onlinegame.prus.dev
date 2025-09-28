<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LarautilxIntegrationService;
use App\Traits\GameValidationTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Database\Eloquent\Model;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class APIDocumentationController extends CrudController
{
    use ApiResponseTrait;
    use GameValidationTrait;

    protected Model $model;

    protected LarautilxIntegrationService $integrationService;

    protected array $validationRules = [];

    protected array $searchableFields = [];

    protected array $relationships = [];

    protected int $perPage = 10;

    public function __construct(LarautilxIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
        // API Documentation Controller doesn't have a specific model
        parent::__construct(new User());
    }

    /**
     * Get comprehensive API documentation for Larautilx integration
     */
    public function getLarautilxAPIDocumentation()
    {
        try {
            $documentation = [
                'title' => 'Larautilx Integration API Documentation',
                'version' => '1.0.0',
                'description' => 'Comprehensive API documentation for Larautilx integration in the Online Game application.',
                'base_url' => url('/game/api'),
                'authentication' => [
                    'type' => 'Bearer Token',
                    'description' => 'All API endpoints require authentication using Bearer token in the Authorization header.',
                    'example' => 'Authorization: Bearer {your-token}',
                ],
                'endpoints' => $this->getAllEndpoints(),
                'schemas' => $this->getSchemas(),
                'examples' => $this->getExamples(),
                'error_codes' => $this->getErrorCodes(),
                'rate_limiting' => $this->getRateLimitingInfo(),
                'larautilx_components' => $this->getLarautilxComponentsInfo(),
            ];

            LoggingUtil::info('Larautilx API documentation generated', [
                'user_id' => auth()->id(),
                'endpoints_count' => count($documentation['endpoints']),
                'schemas_count' => count($documentation['schemas']),
            ], 'api_documentation');

            return $this->successResponse($documentation, 'Larautilx API documentation generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating Larautilx API documentation', [
                'error' => $e->getMessage(),
            ], 'api_documentation');

            return $this->errorResponse('Failed to generate API documentation.', 500);
        }
    }

    /**
     * Get all API endpoints
     */
    protected function getAllEndpoints(): array
    {
        return [
            'larautilx_management' => [
                'GET /larautilx/status' => [
                    'description' => 'Get Larautilx integration status',
                    'parameters' => [],
                    'response' => [
                        'status' => 'string',
                        'version' => 'string',
                        'components_integrated' => 'array',
                    ],
                ],
                'GET /larautilx/cache/stats' => [
                    'description' => 'Get cache statistics',
                    'parameters' => [],
                    'response' => [
                        'driver' => 'string',
                        'total_keys' => 'array',
                        'memory_usage' => 'string',
                    ],
                ],
                'POST /larautilx/cache/clear' => [
                    'description' => 'Clear cache by tags or all cache',
                    'parameters' => [
                        'tags' => 'array (optional) - Cache tags to clear',
                    ],
                    'response' => [
                        'message' => 'string',
                    ],
                ],
                'POST /larautilx/test/filtering' => [
                    'description' => 'Test FilteringUtil functionality',
                    'parameters' => [],
                    'response' => [
                        'data' => 'array',
                        'message' => 'string',
                    ],
                ],
                'POST /larautilx/test/pagination' => [
                    'description' => 'Test PaginationUtil functionality',
                    'parameters' => [
                        'per_page' => 'integer (optional)',
                        'page' => 'integer (optional)',
                    ],
                    'response' => [
                        'data' => 'object',
                        'message' => 'string',
                    ],
                ],
                'POST /larautilx/test/caching' => [
                    'description' => 'Test CachingUtil functionality',
                    'parameters' => [],
                    'response' => [
                        'data' => 'object',
                        'message' => 'string',
                    ],
                ],
                'GET /larautilx/docs' => [
                    'description' => 'Get API documentation',
                    'parameters' => [],
                    'response' => [
                        'documentation' => 'object',
                    ],
                ],
            ],
            'crud_operations' => [
                'players' => [
                    'GET /players' => [
                        'description' => 'Get all players with filtering, search, and pagination',
                        'parameters' => [
                            'search' => 'string (optional)',
                            'filter' => 'object (optional)',
                            'sort' => 'string (optional)',
                            'per_page' => 'integer (optional)',
                            'page' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'array',
                            'meta' => 'object',
                            'links' => 'object',
                        ],
                    ],
                    'GET /players/{id}' => [
                        'description' => 'Get a specific player by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'data' => 'object',
                        ],
                    ],
                    'POST /players' => [
                        'description' => 'Create a new player',
                        'parameters' => [
                            'name' => 'string (required)',
                            'tribe' => 'string (required) - roman, teuton, or gaul',
                            'alliance_id' => 'integer (optional)',
                            'world_id' => 'integer (required)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'PUT /players/{id}' => [
                        'description' => 'Update a player by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                            'name' => 'string (optional)',
                            'tribe' => 'string (optional)',
                            'alliance_id' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'DELETE /players/{id}' => [
                        'description' => 'Delete a player by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'message' => 'string',
                        ],
                    ],
                ],
                'villages' => [
                    'GET /villages' => [
                        'description' => 'Get all villages with filtering, search, and pagination',
                        'parameters' => [
                            'search' => 'string (optional)',
                            'filter' => 'object (optional)',
                            'sort' => 'string (optional)',
                            'per_page' => 'integer (optional)',
                            'page' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'array',
                            'meta' => 'object',
                            'links' => 'object',
                        ],
                    ],
                    'GET /villages/{id}' => [
                        'description' => 'Get a specific village by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'data' => 'object',
                        ],
                    ],
                    'POST /villages' => [
                        'description' => 'Create a new village',
                        'parameters' => [
                            'name' => 'string (required)',
                            'player_id' => 'integer (required)',
                            'world_id' => 'integer (required)',
                            'x_coordinate' => 'integer (required)',
                            'y_coordinate' => 'integer (required)',
                            'population' => 'integer (optional)',
                            'culture_points' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'PUT /villages/{id}' => [
                        'description' => 'Update a village by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                            'name' => 'string (optional)',
                            'population' => 'integer (optional)',
                            'culture_points' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'DELETE /villages/{id}' => [
                        'description' => 'Delete a village by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'message' => 'string',
                        ],
                    ],
                ],
                'tasks' => [
                    'GET /tasks' => [
                        'description' => 'Get all tasks with filtering, search, and pagination',
                        'parameters' => [
                            'search' => 'string (optional)',
                            'filter' => 'object (optional)',
                            'sort' => 'string (optional)',
                            'per_page' => 'integer (optional)',
                            'page' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'array',
                            'meta' => 'object',
                            'links' => 'object',
                        ],
                    ],
                    'GET /tasks/{id}' => [
                        'description' => 'Get a specific task by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'data' => 'object',
                        ],
                    ],
                    'POST /tasks' => [
                        'description' => 'Create a new task',
                        'parameters' => [
                            'title' => 'string (required)',
                            'description' => 'string (optional)',
                            'type' => 'string (required) - building, combat, resource, exploration, or alliance',
                            'status' => 'string (required) - available, active, completed, or expired',
                            'progress' => 'integer (optional)',
                            'target' => 'integer (required)',
                            'rewards' => 'object (optional)',
                            'deadline' => 'datetime (optional)',
                            'world_id' => 'integer (required)',
                            'player_id' => 'integer (required)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'PUT /tasks/{id}' => [
                        'description' => 'Update a task by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                            'title' => 'string (optional)',
                            'description' => 'string (optional)',
                            'status' => 'string (optional)',
                            'progress' => 'integer (optional)',
                        ],
                        'response' => [
                            'data' => 'object',
                            'message' => 'string',
                        ],
                    ],
                    'DELETE /tasks/{id}' => [
                        'description' => 'Delete a task by ID',
                        'parameters' => [
                            'id' => 'integer (required)',
                        ],
                        'response' => [
                            'message' => 'string',
                        ],
                    ],
                ],
            ],
            'ai_management' => [
                'GET /ai/status' => [
                    'description' => 'Get AI service status',
                    'parameters' => [],
                    'response' => [
                        'available' => 'boolean',
                        'current_provider' => 'string',
                        'available_providers' => 'array',
                        'providers_count' => 'integer',
                    ],
                ],
                'POST /ai/village-names' => [
                    'description' => 'Generate village names using AI',
                    'parameters' => [
                        'count' => 'integer (optional, default: 5)',
                        'tribe' => 'string (optional, default: roman) - roman, teuton, or gaul',
                    ],
                    'response' => [
                        'names' => 'array',
                        'count' => 'integer',
                        'tribe' => 'string',
                    ],
                ],
                'POST /ai/alliance-names' => [
                    'description' => 'Generate alliance names using AI',
                    'parameters' => [
                        'count' => 'integer (optional, default: 5)',
                    ],
                    'response' => [
                        'names' => 'array',
                        'count' => 'integer',
                    ],
                ],
                'POST /ai/quest-description' => [
                    'description' => 'Generate quest description using AI',
                    'parameters' => [
                        'quest_type' => 'string (required)',
                        'context' => 'array (optional)',
                    ],
                    'response' => [
                        'description' => 'string',
                        'quest_type' => 'string',
                        'context' => 'array',
                    ],
                ],
                'POST /ai/battle-report' => [
                    'description' => 'Generate battle report using AI',
                    'parameters' => [
                        'attacker' => 'string (required)',
                        'defender' => 'string (required)',
                        'attacker_troops' => 'integer (required)',
                        'defender_troops' => 'integer (required)',
                        'result' => 'string (required) - victory, defeat, or draw',
                    ],
                    'response' => [
                        'report' => 'string',
                        'battle_data' => 'object',
                    ],
                ],
                'POST /ai/custom-content' => [
                    'description' => 'Generate custom content using AI',
                    'parameters' => [
                        'prompt' => 'string (required)',
                        'provider' => 'string (optional) - openai or gemini',
                        'model' => 'string (optional)',
                        'temperature' => 'float (optional)',
                        'max_tokens' => 'integer (optional)',
                        'json_mode' => 'boolean (optional)',
                    ],
                    'response' => [
                        'content' => 'string',
                        'prompt' => 'string',
                        'options' => 'object',
                    ],
                ],
            ],
            'system_management' => [
                'GET /system/config' => [
                    'description' => 'Get system configuration settings',
                    'parameters' => [
                        'key' => 'string (optional)',
                    ],
                    'response' => [
                        'config' => 'object',
                    ],
                ],
                'PUT /system/config' => [
                    'description' => 'Update system configuration settings',
                    'parameters' => [
                        'key' => 'string (required)',
                        'value' => 'mixed (required)',
                    ],
                    'response' => [
                        'message' => 'string',
                    ],
                ],
                'GET /system/scheduled-tasks' => [
                    'description' => 'Get scheduled tasks summary',
                    'parameters' => [],
                    'response' => [
                        'tasks' => 'array',
                    ],
                ],
                'GET /system/health' => [
                    'description' => 'Get system health status',
                    'parameters' => [],
                    'response' => [
                        'app_env' => 'string',
                        'debug_mode' => 'boolean',
                        'database' => 'object',
                        'cache' => 'object',
                        'storage' => 'object',
                        'larautilx_integration' => 'object',
                    ],
                ],
                'GET /system/metrics' => [
                    'description' => 'Get system performance metrics',
                    'parameters' => [],
                    'response' => [
                        'memory_usage_mb' => 'float',
                        'database_queries' => 'integer',
                        'cache_hits' => 'integer',
                        'cache_misses' => 'integer',
                        'larautilx_components' => 'object',
                    ],
                ],
                'POST /system/clear-caches' => [
                    'description' => 'Clear system caches',
                    'parameters' => [
                        'tags' => 'array (optional)',
                    ],
                    'response' => [
                        'message' => 'string',
                    ],
                ],
            ],
            'user_management' => [
                'GET /users' => [
                    'description' => 'Get all users with filtering, search, and pagination',
                    'parameters' => [
                        'search' => 'string (optional)',
                        'filter' => 'object (optional)',
                        'sort' => 'string (optional)',
                        'per_page' => 'integer (optional)',
                        'page' => 'integer (optional)',
                    ],
                    'response' => [
                        'data' => 'array',
                        'meta' => 'object',
                        'links' => 'object',
                    ],
                ],
                'GET /users/{id}' => [
                    'description' => 'Get a specific user by ID',
                    'parameters' => [
                        'id' => 'integer (required)',
                    ],
                    'response' => [
                        'data' => 'object',
                    ],
                ],
                'POST /users' => [
                    'description' => 'Create a new user',
                    'parameters' => [
                        'name' => 'string (required)',
                        'email' => 'string (required)',
                        'password' => 'string (required)',
                    ],
                    'response' => [
                        'data' => 'object',
                        'message' => 'string',
                    ],
                ],
                'PUT /users/{id}' => [
                    'description' => 'Update a user by ID',
                    'parameters' => [
                        'id' => 'integer (required)',
                        'name' => 'string (optional)',
                        'email' => 'string (optional)',
                        'password' => 'string (optional)',
                    ],
                    'response' => [
                        'data' => 'object',
                        'message' => 'string',
                    ],
                ],
                'DELETE /users/{id}' => [
                    'description' => 'Delete a user by ID',
                    'parameters' => [
                        'id' => 'integer (required)',
                    ],
                    'response' => [
                        'message' => 'string',
                    ],
                ],
            ],
            'dashboard' => [
                'GET /larautilx/dashboard' => [
                    'description' => 'Get comprehensive Larautilx integration dashboard data',
                    'parameters' => [],
                    'response' => [
                        'integration_status' => 'object',
                        'ai_service_status' => 'object',
                        'feature_toggles' => 'object',
                        'system_health' => 'object',
                        'performance_metrics' => 'object',
                        'scheduled_tasks' => 'object',
                        'configuration_status' => 'object',
                        'usage_statistics' => 'object',
                        'recent_activity' => 'object',
                    ],
                ],
                'GET /larautilx/integration-summary' => [
                    'description' => 'Get detailed Larautilx integration summary',
                    'parameters' => [],
                    'response' => [
                        'package_info' => 'object',
                        'integrated_components' => 'object',
                        'game_integration' => 'object',
                        'api_endpoints' => 'object',
                        'configuration_files' => 'object',
                    ],
                ],
                'POST /larautilx/test-components' => [
                    'description' => 'Test Larautilx components',
                    'parameters' => [
                        'components' => 'array (optional)',
                    ],
                    'response' => [
                        'results' => 'object',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get API schemas
     */
    protected function getSchemas(): array
    {
        return [
            'Player' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'tribe' => ['type' => 'string', 'enum' => ['roman', 'teuton', 'gaul']],
                    'alliance_id' => ['type' => 'integer', 'nullable' => true],
                    'world_id' => ['type' => 'integer'],
                    'points' => ['type' => 'integer'],
                    'is_active' => ['type' => 'boolean'],
                    'is_online' => ['type' => 'boolean'],
                    'last_active_at' => ['type' => 'string', 'format' => 'date-time'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Village' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'player_id' => ['type' => 'integer'],
                    'world_id' => ['type' => 'integer'],
                    'x_coordinate' => ['type' => 'integer'],
                    'y_coordinate' => ['type' => 'integer'],
                    'population' => ['type' => 'integer'],
                    'culture_points' => ['type' => 'integer'],
                    'is_capital' => ['type' => 'boolean'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Task' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'type' => ['type' => 'string', 'enum' => ['building', 'combat', 'resource', 'exploration', 'alliance']],
                    'status' => ['type' => 'string', 'enum' => ['available', 'active', 'completed', 'expired']],
                    'progress' => ['type' => 'integer'],
                    'target' => ['type' => 'integer'],
                    'rewards' => ['type' => 'object', 'nullable' => true],
                    'deadline' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'world_id' => ['type' => 'integer'],
                    'player_id' => ['type' => 'integer'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'email_verified_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'ApiResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                    'meta' => ['type' => 'object', 'nullable' => true],
                ],
            ],
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'errors' => ['type' => 'object', 'nullable' => true],
                    'debug' => ['type' => 'object', 'nullable' => true],
                ],
            ],
        ];
    }

    /**
     * Get API examples
     */
    protected function getExamples(): array
    {
        return [
            'create_player' => [
                'request' => [
                    'name' => 'TestPlayer',
                    'tribe' => 'roman',
                    'world_id' => 1,
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Player created successfully.',
                    'data' => [
                        'id' => 1,
                        'name' => 'TestPlayer',
                        'tribe' => 'roman',
                        'world_id' => 1,
                        'points' => 0,
                        'is_active' => true,
                        'is_online' => false,
                        'created_at' => '2024-01-01T00:00:00.000000Z',
                        'updated_at' => '2024-01-01T00:00:00.000000Z',
                    ],
                ],
            ],
            'generate_village_names' => [
                'request' => [
                    'count' => 3,
                    'tribe' => 'roman',
                ],
                'response' => [
                    'success' => true,
                    'message' => 'Village names generated successfully.',
                    'data' => [
                        'names' => ['Roma Nova', 'Augusta', 'Caesarea'],
                        'count' => 3,
                        'tribe' => 'roman',
                    ],
                ],
            ],
            'error_response' => [
                'request' => [
                    'name' => '',
                    'tribe' => 'invalid',
                ],
                'response' => [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => [
                        'name' => ['The name field is required.'],
                        'tribe' => ['The selected tribe is invalid.'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get error codes
     */
    protected function getErrorCodes(): array
    {
        return [
            '400' => [
                'description' => 'Bad Request',
                'meaning' => 'The request was invalid or cannot be served.',
            ],
            '401' => [
                'description' => 'Unauthorized',
                'meaning' => 'Authentication is required and has failed or has not been provided.',
            ],
            '403' => [
                'description' => 'Forbidden',
                'meaning' => 'The request was valid, but the server is refusing action.',
            ],
            '404' => [
                'description' => 'Not Found',
                'meaning' => 'The requested resource could not be found.',
            ],
            '422' => [
                'description' => 'Unprocessable Entity',
                'meaning' => 'The request was well-formed but was unable to be followed due to semantic errors.',
            ],
            '429' => [
                'description' => 'Too Many Requests',
                'meaning' => 'Rate limit exceeded.',
            ],
            '500' => [
                'description' => 'Internal Server Error',
                'meaning' => 'An unexpected condition was encountered.',
            ],
        ];
    }

    /**
     * Get rate limiting information
     */
    protected function getRateLimitingInfo(): array
    {
        return [
            'description' => 'API rate limiting is implemented using Larautilx RateLimiterUtil.',
            'limits' => [
                'general' => '1000 requests per hour',
                'ai_endpoints' => '60 requests per minute',
                'cache_operations' => '100 requests per minute',
                'system_operations' => '50 requests per minute',
            ],
            'headers' => [
                'X-RateLimit-Limit' => 'The rate limit ceiling for that given request',
                'X-RateLimit-Remaining' => 'The number of requests left for the time window',
                'X-RateLimit-Reset' => 'The time at which the current rate limit window resets',
            ],
        ];
    }

    /**
     * Get Larautilx components information
     */
    protected function getLarautilxComponentsInfo(): array
    {
        return [
            'traits' => [
                'ApiResponseTrait' => 'Standardized JSON responses for API endpoints',
                'Auditable' => 'Model change tracking and auditing',
                'FileProcessingTrait' => 'File upload and processing operations',
            ],
            'utilities' => [
                'CachingUtil' => 'Tag-aware caching with automatic expiration',
                'ConfigUtil' => 'Dynamic configuration management',
                'FeatureToggleUtil' => 'Feature flag management',
                'FilteringUtil' => 'Declarative collection and query filtering',
                'LoggingUtil' => 'Structured logging with context',
                'PaginationUtil' => 'Consistent pagination for collections and queries',
                'QueryParameterUtil' => 'Request parameter parsing and validation',
                'RateLimiterUtil' => 'API rate limiting and throttling',
                'SchedulerUtil' => 'Scheduled task monitoring and management',
            ],
            'controllers' => [
                'CrudController' => 'Generic CRUD operations for models',
            ],
            'middleware' => [
                'AccessLogMiddleware' => 'Request logging and access tracking',
            ],
            'llm_providers' => [
                'OpenAIProvider' => 'OpenAI GPT models integration',
                'GeminiProvider' => 'Google Gemini models integration',
                'LLMProviderInterface' => 'Standardized LLM provider interface',
            ],
            'models' => [
                'AccessLog' => 'Request access logging model',
            ],
            'enums' => [
                'LogLevel' => 'Logging level enumeration',
            ],
        ];
    }
}

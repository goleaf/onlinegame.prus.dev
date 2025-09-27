<?php

namespace App\Services;

use App\Models\Game\BuildingType;
use App\Models\Game\Player;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Support\Collection;
use Mateffy\Introspect\Facades\Introspect;

class IntrospectService
{
    /**
     * Get comprehensive model analysis for all game models
     */
    public function getModelAnalysis(): array
    {
        $models = [
            'User' => User::class,
            'Player' => Player::class,
            'Village' => Village::class,
            'World' => World::class,
            'BuildingType' => BuildingType::class,
            'UnitType' => UnitType::class,
        ];

        $analysis = [];

        foreach ($models as $name => $class) {
            $modelDetail = Introspect::model($class);

            $analysis[$name] = [
                'class' => $class,
                'schema' => $modelDetail->schema(),
                'fillable' => $this->getModelFillable($class),
                'casts' => $this->getModelCasts($class),
                'relationships' => $this->getModelRelationships($class),
            ];
        }

        return $analysis;
    }

    /**
     * Get model fillable attributes
     */
    public function getModelFillable(string $modelClass): array
    {
        try {
            $model = new $modelClass;
            return $model->getFillable();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get model casts
     */
    public function getModelCasts(string $modelClass): array
    {
        try {
            $model = new $modelClass;
            return $model->getCasts();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get model relationships using Introspect
     */
    public function getModelRelationships(string $modelClass): array
    {
        $relationships = [];

        try {
            // Get models that have relationships with the target model
            $relatedModels = Introspect::models()
                ->whereHasRelationship(class_basename($modelClass))
                ->get();

            foreach ($relatedModels as $relatedModel) {
                $relationships[] = [
                    'model' => $relatedModel,
                    'type' => 'related',
                ];
            }
        } catch (\Exception $e) {
            // Fallback to manual relationship detection
            $relationships = $this->getManualRelationships($modelClass);
        }

        return $relationships;
    }

    /**
     * Get manual relationships for models
     */
    private function getManualRelationships(string $modelClass): array
    {
        $relationships = [];

        try {
            $reflection = new \ReflectionClass($modelClass);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (str_contains($method->getName(), 'belongsTo') ||
                        str_contains($method->getName(), 'hasMany') ||
                        str_contains($method->getName(), 'hasOne') ||
                        str_contains($method->getName(), 'belongsToMany')) {
                    $relationships[] = [
                        'method' => $method->getName(),
                        'type' => 'relationship',
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }

        return $relationships;
    }

    /**
     * Get route analysis for game routes
     */
    public function getRouteAnalysis(): array
    {
        $gameRoutes = Introspect::routes()
            ->wherePathStartsWith('game')
            ->get();

        $apiRoutes = Introspect::routes()
            ->wherePathStartsWith('game/api')
            ->get();

        $authRoutes = Introspect::routes()
            ->whereUsesMiddleware('auth')
            ->get();

        return [
            'game_routes' => $gameRoutes,
            'api_routes' => $apiRoutes,
            'auth_routes' => $authRoutes,
            'total_routes' => count($gameRoutes) + count($apiRoutes),
        ];
    }

    /**
     * Get view analysis for game views
     */
    public function getViewAnalysis(): array
    {
        $gameViews = Introspect::views()
            ->whereNameContains('game')
            ->get();

        $livewireViews = Introspect::views()
            ->whereNameContains('livewire')
            ->get();

        $componentViews = Introspect::views()
            ->whereNameContains('components')
            ->get();

        return [
            'game_views' => $gameViews,
            'livewire_views' => $livewireViews,
            'component_views' => $componentViews,
            'total_views' => count($gameViews) + count($livewireViews) + count($componentViews),
        ];
    }

    /**
     * Get class analysis for game-related classes
     */
    public function getClassAnalysis(): array
    {
        $gameControllers = Introspect::classes()
            ->whereName('\App\Http\Controllers\Game')
            ->get();

        $gameServices = Introspect::classes()
            ->whereName('\App\Services')
            ->get();

        $livewireComponents = Introspect::classes()
            ->whereName('\App\Livewire\Game')
            ->get();

        return [
            'game_controllers' => $gameControllers,
            'game_services' => $gameServices,
            'livewire_components' => $livewireComponents,
            'total_classes' => count($gameControllers) + count($gameServices) + count($livewireComponents),
        ];
    }

    /**
     * Generate comprehensive codebase documentation
     */
    public function generateDocumentation(): array
    {
        return [
            'models' => $this->getModelAnalysis(),
            'routes' => $this->getRouteAnalysis(),
            'views' => $this->getViewAnalysis(),
            'classes' => $this->getClassAnalysis(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get models with specific properties
     */
    public function getModelsWithProperty(string $property): Collection
    {
        return Introspect::models()
            ->whereHasProperty($property)
            ->get();
    }

    /**
     * Get models with specific fillable attributes
     */
    public function getModelsWithFillable(string $fillable): Collection
    {
        return Introspect::models()
            ->whereHasFillable($fillable)
            ->get();
    }

    /**
     * Get routes using specific middleware
     */
    public function getRoutesWithMiddleware(string $middleware): Collection
    {
        return Introspect::routes()
            ->whereUsesMiddleware($middleware)
            ->get();
    }

    /**
     * Get views that use specific components
     */
    public function getViewsUsingComponent(string $component): Collection
    {
        return Introspect::views()
            ->whereUses($component)
            ->get();
    }

    /**
     * Get comprehensive model schema for API documentation
     */
    public function getModelSchemas(): array
    {
        $models = [
            'User' => User::class,
            'Player' => Player::class,
            'Village' => Village::class,
            'World' => World::class,
            'BuildingType' => BuildingType::class,
            'UnitType' => UnitType::class,
        ];

        $schemas = [];

        foreach ($models as $name => $class) {
            try {
                $modelDetail = Introspect::model($class);
                $schemas[$name] = $modelDetail->schema();
            } catch (\Exception $e) {
                $schemas[$name] = [
                    'type' => 'object',
                    'properties' => [],
                    'error' => 'Could not generate schema: ' . $e->getMessage()
                ];
            }
        }

        return $schemas;
    }

    /**
     * Analyze model relationships and dependencies
     */
    public function getModelDependencies(): array
    {
        $models = [
            'User' => User::class,
            'Player' => Player::class,
            'Village' => Village::class,
            'World' => World::class,
            'BuildingType' => BuildingType::class,
            'UnitType' => UnitType::class,
        ];

        $dependencies = [];

        foreach ($models as $name => $class) {
            try {
                $modelDetail = Introspect::model($class);

                $dependencies[$name] = [
                    'class' => $class,
                    'schema' => $modelDetail->schema(),
                    'fillable' => $this->getModelFillable($class),
                    'casts' => $this->getModelCasts($class),
                    'relationships' => $this->getModelRelationships($class),
                ];
            } catch (\Exception $e) {
                $dependencies[$name] = [
                    'class' => $class,
                    'error' => 'Could not analyze: ' . $e->getMessage(),
                    'fillable' => $this->getModelFillable($class),
                    'casts' => $this->getModelCasts($class),
                    'relationships' => $this->getModelRelationships($class),
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Get performance metrics for models
     */
    public function getModelPerformanceMetrics(): array
    {
        $models = [
            'User' => User::class,
            'Player' => Player::class,
            'Village' => Village::class,
            'World' => World::class,
            'BuildingType' => BuildingType::class,
            'UnitType' => UnitType::class,
        ];

        $metrics = [];

        foreach ($models as $name => $class) {
            $fillable = $this->getModelFillable($class);
            $casts = $this->getModelCasts($class);
            $relationships = $this->getModelRelationships($class);

            $metrics[$name] = [
                'fillable_count' => count($fillable),
                'cast_count' => count($casts),
                'relationship_count' => count($relationships),
                'complexity_score' => $this->calculateModelComplexity($fillable, $casts, $relationships),
            ];
        }

        return $metrics;
    }

    /**
     * Calculate model complexity score
     */
    private function calculateModelComplexity(array $fillable, array $casts, array $relationships): int
    {
        $score = 0;

        $score += count($fillable) * 2;
        $score += count($casts) * 3;
        $score += count($relationships) * 5;

        return $score;
    }
}

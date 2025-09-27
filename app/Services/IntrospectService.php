<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\Game\BuildingType;
use App\Models\Game\UnitType;
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
                'properties' => $modelDetail->properties(),
                'schema' => $modelDetail->schema(),
                'fillable' => $modelDetail->fillable ?? [],
                'casts' => $modelDetail->casts ?? [],
                'relationships' => $this->getModelRelationships($class),
            ];
        }

        return $analysis;
    }

    /**
     * Get model relationships using Introspect
     */
    public function getModelRelationships(string $modelClass): array
    {
        $relationships = [];
        
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
            $modelDetail = Introspect::model($class);
            $schemas[$name] = $modelDetail->schema();
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
            $modelDetail = Introspect::model($class);
            
            $dependencies[$name] = [
                'class' => $class,
                'properties' => $modelDetail->properties(),
                'fillable' => $modelDetail->fillable ?? [],
                'casts' => $modelDetail->casts ?? [],
                'relationships' => $this->getModelRelationships($class),
            ];
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
            $modelDetail = Introspect::model($class);
            
            $metrics[$name] = [
                'property_count' => count($modelDetail->properties()),
                'fillable_count' => count($modelDetail->fillable ?? []),
                'cast_count' => count($modelDetail->casts ?? []),
                'relationship_count' => count($this->getModelRelationships($class)),
                'complexity_score' => $this->calculateModelComplexity($modelDetail),
            ];
        }

        return $metrics;
    }

    /**
     * Calculate model complexity score
     */
    private function calculateModelComplexity($modelDetail): int
    {
        $score = 0;
        
        $score += count($modelDetail->properties()) * 1;
        $score += count($modelDetail->fillable ?? []) * 2;
        $score += count($modelDetail->casts ?? []) * 3;
        $score += count($this->getModelRelationships($modelDetail->class)) * 5;
        
        return $score;
    }
}

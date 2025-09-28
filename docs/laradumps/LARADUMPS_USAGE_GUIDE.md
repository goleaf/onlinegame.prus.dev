# Laradumps Usage Guide

## Quick Start

### 1. Install Laradumps Desktop App
Download and install from [laradumps.dev](https://laradumps.dev)

### 2. Start Debugging
```bash
# Start your Laravel application
php artisan serve

# Or use the dev script (includes Laradumps)
composer run dev
```

### 3. View Debug Output
- Launch the Laradumps desktop app
- Navigate to your game components
- Perform actions (attacks, tasks, movements)
- View real-time debug output in the desktop app

## Debug Commands Reference

### Basic Debugging
```php
// Simple debug
ds('Hello World');

// Debug with data
ds('User data', $userData);

// Debug with label
ds('Important data', $data)->label('User Registration');
```

### Advanced Debugging
```php
// Debug with multiple labels
ds('Complex data', $data)
    ->label('Component Name')
    ->toScreen('Screen Name');

// Debug with color
ds('Error occurred', $errorData)
    ->label('Error Handler')
    ->color('red');

// Debug with size
ds('Large dataset', $largeData)
    ->label('Data Processing')
    ->maxDepth(3);
```

### Performance Monitoring
```php
// Track execution time
$startTime = microtime(true);
// ... your code ...
$endTime = microtime(true);

ds('Performance metrics', [
    'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
])->label('Performance Monitor');
```

### Database Query Debugging
```php
// Debug queries (automatically captured)
// No additional code needed - Laradumps will capture all queries
// Slow queries (>100ms) are automatically highlighted
```

### Error Handling
```php
try {
    // Your code
} catch (\Exception $e) {
    ds('Exception caught', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ])->label('Error Handler');
}
```

## Game-Specific Debugging

### Battle System
```php
// Attack launch debugging
ds('Launching attack', [
    'from_village' => $this->village->name,
    'to_village' => $targetVillage->name,
    'game_distance' => $distance,
    'real_world_distance_km' => $realWorldDistance,
    'travel_time' => $travelTime,
    'attacking_troops' => $this->attackingTroops,
    'total_attack_power' => array_sum(array_column($this->attackingTroops, 'attack')),
    'from_coordinates' => "({$this->village->x_coordinate}|{$this->village->y_coordinate})",
    'to_coordinates' => "({$targetVillage->x_coordinate}|{$targetVillage->y_coordinate})"
])->label('BattleManager Attack Launch');
```

### Task System
```php
// Task completion debugging
ds('Task completed successfully', [
    'task_id' => $taskId,
    'reference_number' => $task->reference_number,
    'task_title' => $task->title,
    'completed_at' => $task->completed_at,
    'rewards' => $task->rewards,
    'player_id' => $this->player->id
])->label('TaskManager Task Completed');
```

### Movement System
```php
// Movement creation debugging
ds('Creating movement', [
    'from_village' => $this->village->name,
    'to_village' => $targetVillage->name,
    'movement_type' => $this->movementType,
    'game_distance' => $distance,
    'real_world_distance_km' => $realWorldDistance,
    'travel_time' => $this->travelTime,
    'selected_troops' => $this->selectedTroops,
    'troop_quantities' => $this->troopQuantities,
    'from_coordinates' => "({$this->village->x_coordinate}|{$this->village->y_coordinate})",
    'to_coordinates' => "({$targetVillage->x_coordinate}|{$targetVillage->y_coordinate})"
])->label('MovementManager Create Movement');
```

### Geographic Data
```php
// Geographic calculations debugging
ds('Geographic calculations', [
    'game_coordinates' => "({$village->x_coordinate}|{$village->y_coordinate})",
    'real_world_coordinates' => $village->getRealWorldCoordinates(),
    'distance_km' => $village->realWorldDistanceTo($targetVillage),
    'geohash' => $village->getGeohash(),
    'bearing' => $village->bearingTo($targetVillage)
])->label('Geographic Service');
```

## Filtering and Organization

### Using Labels
```php
// Component-specific labels
ds($data)->label('BattleManager');
ds($data)->label('TaskManager');
ds($data)->label('MovementManager');
ds($data)->label('EnhancedGameDashboard');

// Action-specific labels
ds($data)->label('Attack Launch');
ds($data)->label('Task Completed');
ds($data)->label('Movement Created');
ds($data)->label('Village Selected');
```

### Using Screens
```php
// Organize by screens
ds($data)->toScreen('Game Actions');
ds($data)->toScreen('Performance');
ds($data)->toScreen('Errors');
ds($data)->toScreen('Database');
```

## Configuration Options

### Environment-Specific Settings
```yaml
# Development (current)
observers:
    auto_invoke_app: true
    queries: true
    slow_queries: true
    logs: true

# Production (recommended)
observers:
    auto_invoke_app: false
    queries: false
    slow_queries: false
    logs: false
```

### Performance Tuning
```yaml
# Optimize for performance
config:
    sleep: 0
    macos_auto_launch: false

# Query monitoring
slow_queries:
    threshold_in_ms: 100

queries:
    explain: true
```

## Best Practices

### 1. Use Descriptive Labels
```php
// Good
ds($data)->label('BattleManager Attack Launch');

// Bad
ds($data)->label('Debug');
```

### 2. Include Context
```php
// Good
ds('User action', [
    'user_id' => $user->id,
    'action' => 'attack_launched',
    'timestamp' => now(),
    'village_id' => $village->id
]);

// Bad
ds($user);
```

### 3. Avoid Sensitive Data
```php
// Good
ds('User data', [
    'user_id' => $user->id,
    'email' => '***@example.com', // Masked
    'created_at' => $user->created_at
]);

// Bad
ds('User data', [
    'password' => $user->password, // Never debug passwords!
    'api_key' => $user->api_key
]);
```

### 4. Use Conditional Debugging
```php
// Only debug in development
if (app()->environment('local')) {
    ds('Debug data', $data)->label('Development Debug');
}

// Debug with environment check
ds('Production data', $data)
    ->label('Production Debug')
    ->when(app()->environment('production'));
```

### 5. Clean Up Debug Statements
```php
// Remove debug statements before production
// Use environment checks
if (config('app.debug')) {
    ds('Debug info', $data);
}
```

## Troubleshooting

### Common Issues

1. **No Debug Output**
   - Check if Laradumps desktop app is running
   - Verify connection to localhost:9191
   - Check `laradumps.yaml` configuration

2. **Performance Issues**
   - Disable observers in production
   - Increase sleep time in config
   - Use conditional debugging

3. **Missing Data**
   - Verify component integration
   - Check label names
   - Ensure proper data structure

### Debug Connection
```php
// Test Laradumps connection
ds('Connection test', [
    'timestamp' => now(),
    'environment' => app()->environment(),
    'laravel_version' => app()->version()
])->label('Connection Test');
```

## Advanced Features

### Custom Observers
```php
// Create custom observer
class GameObserver
{
    public function handle($event)
    {
        ds('Game event', $event)->label('Game Observer');
    }
}
```

### Performance Profiling
```php
// Profile specific operations
$profiler = new \LaraDumps\LaraDumps\Profiler();

$profiler->start('database_queries');
// Database operations
$profiler->end('database_queries');

$profiler->start('game_logic');
// Game logic
$profiler->end('game_logic');

ds('Performance profile', $profiler->getResults())->label('Performance Profiler');
```

### Memory Monitoring
```php
// Monitor memory usage
ds('Memory usage', [
    'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
    'limit_mb' => ini_get('memory_limit')
])->label('Memory Monitor');
```

## Integration with Other Tools

### Laravel Telescope
```php
// Combine with Telescope
ds('Telescope entry', [
    'entry_id' => $entry->id,
    'type' => $entry->type,
    'content' => $entry->content
])->label('Telescope Integration');
```

### Laravel Debugbar
```php
// Complement Debugbar
ds('Debugbar data', [
    'queries_count' => count(DB::getQueryLog()),
    'execution_time' => microtime(true) - LARAVEL_START
])->label('Debugbar Integration');
```

---

**Note**: This guide covers the most common Laradumps usage patterns for your online game project. For more advanced features, refer to the [official Laradumps documentation](https://laradumps.dev).

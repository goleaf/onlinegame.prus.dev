# Laradumps Integration Guide

## Overview
Laradumps has been successfully integrated into the online game's Livewire components to provide comprehensive debugging capabilities. This guide explains how to use the debugging features and what information is being tracked.

## Installation & Configuration
- **Package**: `laradumps/laradumps` (v4.5.2)
- **Configuration**: `laradumps.yaml` (auto-generated)
- **Desktop App**: Required for viewing debug output

## Integrated Components

### 1. BattleManager (`app/Livewire/Game/BattleManager.php`)

**Debug Points:**
- **Mount**: Player and village data on component initialization
- **Attack Launch**: Attack details, troop selection, and travel calculations
- **Battle Simulation**: Power calculations, randomness factors, and results
- **Error Handling**: Attack failures and exceptions

**Key Debug Labels:**
- `BattleManager Mount`
- `BattleManager Attack Launch`
- `BattleManager Attack Success`
- `BattleManager Attack Error`
- `BattleManager Battle Simulation`

**Example Usage:**
```php
// View attack launch details with geographic data
ds('Launching attack', [
    'from_village' => $this->village->name,
    'to_village' => $this->selectedTarget->name,
    'game_distance' => $distance,
    'real_world_distance_km' => $realWorldDistance,
    'travel_time' => $travelTime,
    'attacking_troops' => $this->attackingTroops,
    'total_attack_power' => array_sum(array_column($this->attackingTroops, 'attack')),
    'from_coordinates' => "({$this->village->x_coordinate}|{$this->village->y_coordinate})",
    'to_coordinates' => "({$this->selectedTarget->x_coordinate}|{$this->selectedTarget->y_coordinate})"
])->label('BattleManager Attack Launch');
```

### 2. TaskManager (`app/Livewire/Game/TaskManager.php`)

**Debug Points:**
- **Mount**: World and player initialization
- **Task Operations**: Start, complete, and abandon task operations
- **Data Loading**: Task data loading with statistics
- **Error Handling**: Task operation failures

**Key Debug Labels:**
- `TaskManager Mount`
- `TaskManager Start Task`
- `TaskManager Task Started`
- `TaskManager Complete Task`
- `TaskManager Task Completed`
- `TaskManager Load Task Data`
- `TaskManager Task Data Loaded`

**Example Usage:**
```php
// View task completion details
ds('Task completed successfully', [
    'task_id' => $taskId,
    'reference_number' => $task->reference_number,
    'task_title' => $task->title,
    'completed_at' => $task->completed_at,
    'rewards' => $task->rewards
])->label('TaskManager Task Completed');
```

### 3. EnhancedGameDashboard (`app/Livewire/Game/EnhancedGameDashboard.php`)

**Debug Points:**
- **Mount**: Player and world data initialization
- **Game Tick Processing**: Real-time game updates
- **Village Selection**: Village switching and data loading
- **Error Handling**: Game tick and data loading errors

**Key Debug Labels:**
- `EnhancedGameDashboard Mount`
- `EnhancedGameDashboard Game Tick Start`
- `EnhancedGameDashboard Game Tick Success`
- `EnhancedGameDashboard Game Tick Error`
- `EnhancedGameDashboard Village Selection`
- `EnhancedGameDashboard Village Data Loaded`

**Example Usage:**
```php
// View game tick processing
ds('Processing game tick', [
    'player_id' => $this->player->id,
    'current_village' => $this->currentVillage?->name,
    'last_update_time' => $this->lastUpdateTime
])->label('EnhancedGameDashboard Game Tick Start');
```

### 4. MovementManager (`app/Livewire/Game/MovementManager.php`)

**Debug Points:**
- **Mount**: Village and player initialization
- **Movement Creation**: Movement planning and execution
- **Data Loading**: Movement data with filtering and statistics
- **Error Handling**: Movement creation and data loading errors

**Key Debug Labels:**
- `MovementManager Mount`
- `MovementManager Create Movement`
- `MovementManager Movement Created`
- `MovementManager Create Movement Failed`
- `MovementManager Load Movement Data`
- `MovementManager Movement Data Loaded`

**Example Usage:**
```php
// View movement creation details with geographic data
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

## Debug Information Captured

### Common Data Points
- **User/Player IDs**: For tracking user actions
- **Village Information**: Names, IDs, and related data
- **Timestamps**: Action times and durations
- **Error Details**: Messages and stack traces
- **Statistics**: Counts, totals, and calculated values

### Game-Specific Data
- **Battle Data**: Troop counts, attack power, distances
- **Task Data**: Status, progress, rewards
- **Movement Data**: Types, status, travel times
- **Resource Data**: Production rates, capacities

### Geographic Data (New)
- **Game Coordinates**: X/Y grid positions
- **Real-World Coordinates**: Latitude/longitude mapping
- **Distances**: Both game and real-world distances
- **Geohash**: Geographic hash for location indexing
- **Travel Times**: Calculated using geographic service
- **Coordinate Conversion**: Game ↔ Real-world mapping

### Reference Numbers (New)
- **Movement References**: MOV-YYYYMM#### format
- **Task References**: TSK-YYYYMM#### format
- **Battle References**: Generated for attack movements
- **Unique Identifiers**: For tracking and debugging

### Caching (New)
- **SmartCache Integration**: Automatic query optimization
- **Cache Keys**: Context-aware caching strategies
- **Performance**: Reduced database load
- **Cache Duration**: Optimized TTL for different data types

### Analytics Tracking (New)
- **Fathom Integration**: User behavior tracking
- **Event Tracking**: Game actions and interactions
- **Performance Metrics**: User engagement analytics
- **Custom Events**: Battle, movement, task, and village interactions

### Error Handling (New)
- **Comprehensive Validation**: Input validation and error checking
- **Exception Handling**: Try-catch blocks with detailed error logging
- **User-Friendly Messages**: Clear error notifications
- **Debug Information**: Detailed error context in Laradumps

### Performance Monitoring (New)
- **Component Load Times**: Mount time tracking
- **Database Query Optimization**: Performance metrics
- **Memory Usage**: Resource consumption monitoring
- **Response Time Tracking**: Real-time performance insights

## Usage Instructions

### 1. Install Laradumps Desktop App
Download and install the Laradumps desktop application from [laradumps.dev](https://laradumps.dev)

### 2. Start Debugging
1. Launch the Laradumps desktop app
2. Start your Laravel application
3. Navigate to game components
4. Perform actions (attacks, tasks, movements)
5. View debug output in the Laradumps app

### 3. Filter Debug Output
Use the label system to filter specific component actions:
- Filter by component: `BattleManager`, `TaskManager`, etc.
- Filter by action: `Mount`, `Attack Launch`, `Task Started`, etc.

### 4. Analyze Performance
Monitor:
- Database query performance
- Component loading times
- Error rates and types
- User interaction patterns

## Best Practices

### 1. Development Environment
- Enable Laradumps only in development
- Use appropriate log levels
- Monitor performance impact

### 2. Production Considerations
- Disable Laradumps in production
- Use conditional debugging
- Implement proper error logging

### 3. Debug Data Management
- Use descriptive labels
- Include relevant context
- Avoid sensitive information
- Clean up debug statements

## Configuration Options

The `laradumps.yaml` file includes:
- **App Settings**: Host, port, project path
- **Observers**: Auto-invoke, testing, queries, logs
- **Log Levels**: Info, warning, error, debug
- **Performance**: Sleep settings, query thresholds

## Troubleshooting

### Common Issues
1. **No Debug Output**: Check Laradumps desktop app connection
2. **Performance Issues**: Adjust sleep settings or disable observers
3. **Missing Data**: Verify component integration and labels

### Support
- Laradumps Documentation: [laradumps.dev](https://laradumps.dev)
- Laravel Debugging: [Laravel Debugging Guide](https://laravel.com/docs/debugging)

## Geographic Service Integration

### New Features Added
- **GeographicService**: Comprehensive geographic calculations
- **Real-World Mapping**: Game coordinates to lat/lon conversion
- **Distance Calculations**: Both game and real-world distances
- **Travel Time**: More accurate travel time calculations
- **Geohash Support**: Geographic indexing and searching
- **Coordinate Conversion**: Multiple coordinate format support

### Geographic Debug Data
- **Game Coordinates**: X/Y grid positions (e.g., "(100|200)")
- **Real-World Coordinates**: Latitude/longitude pairs
- **Distances**: Game distance and real-world distance in km
- **Geohash**: Geographic hash for location indexing
- **Travel Times**: Calculated using speed and distance
- **Bearing**: Direction from one point to another

### Usage Examples
```php
// Get real-world coordinates
$coords = $village->getRealWorldCoordinates();
// Returns: ['lat' => 50.123, 'lon' => 8.456]

// Calculate real-world distance
$distance = $village1->realWorldDistanceTo($village2);
// Returns: 15.7 (km)

// Get geohash
$geohash = $village->getGeohash();
// Returns: "u1x0y0z0"

// Calculate bearing
$bearing = $village1->bearingTo($village2);
// Returns: 45.5 (degrees)
```

## Performance Optimizations

### Caching Strategy
- **SmartCache Integration**: Automatic query optimization
- **Context-Aware Keys**: Unique cache keys based on filters and parameters
- **Optimized TTL**: Different cache durations for different data types
- **Database Load Reduction**: Significant performance improvements

### Reference Number System
- **Unique Identifiers**: MOV-YYYYMM#### and TSK-YYYYMM#### formats
- **Automatic Generation**: Reference numbers generated on creation
- **Debug Tracking**: Easy identification of specific operations
- **Audit Trail**: Complete tracking of game actions

### Cache Examples
```php
// Village target data caching
$cacheKey = "village_{$villageId}_battle_target_data";
$target = SmartCache::remember($cacheKey, now()->addMinutes(1), function () use ($villageId) {
    return Village::with(['player', 'troops.unitType'])->find($villageId);
});

// Movement data caching
$cacheKey = "village_{$villageId}_movements_{$filterByType}_{$filterByStatus}";
$movements = SmartCache::remember($cacheKey, now()->addMinutes(2), function () {
    return Movement::byVillage($villageId)->withVillageInfo()->get();
});
```

### Analytics Tracking Examples
```php
// Battle target selection tracking
$this->dispatch('fathom-track', name: 'battle target selected', value: $villageId);

// Attack launch tracking
$totalAttackPower = array_sum(array_column($this->attackingTroops, 'attack'));
$this->dispatch('fathom-track', name: 'attack launched', value: $totalAttackPower);

// Movement creation tracking
$totalTroops = array_sum($this->troopQuantities);
$this->dispatch('fathom-track', name: 'movement created', value: $totalTroops);

// Task completion tracking
$this->dispatch('fathom-track', name: 'task completed', value: $taskId);

// Village selection tracking
$this->dispatch('fathom-track', name: 'village selected', value: $villageId);
```

## Future Enhancements

Potential improvements:
- Add more granular debugging points
- Implement performance monitoring
- Add user action tracking
- Create debug dashboards
- Integrate with error reporting systems
- **Geographic Features**: Map visualization, route planning, territory analysis
- **Real-World Integration**: Weather data, time zones, seasonal effects
- **Advanced Caching**: Redis clustering, cache warming, intelligent invalidation
- **Reference System**: Extended to all game entities, barcode generation
- **Analytics Dashboard**: Real-time user behavior insights
- **A/B Testing**: Feature flag integration with analytics
- **Performance Monitoring**: Advanced metrics and alerting

---

**Note**: This integration provides comprehensive debugging capabilities for the online game components. Use responsibly and ensure proper configuration for your development environment.

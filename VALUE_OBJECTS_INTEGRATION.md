# Immutable Value Objects Integration

This document describes the integration of immutable value objects using the Bag package into the online game project.

## Overview

We've integrated immutable value objects to improve data integrity, type safety, and code maintainability. These value objects are immutable, meaning once created, their state cannot be changed, ensuring data consistency throughout the application.

## Installed Package

- **immutablephp/immutable**: The Bag package for creating immutable value objects in PHP and Laravel

## Created Value Objects

### 1. Coordinates
**File**: `app/ValueObjects/Coordinates.php`

Represents geographic coordinates with both game coordinates (x, y) and real-world coordinates (latitude, longitude).

```php
use App\ValueObjects\Coordinates;

// Create coordinates
$coords = new Coordinates(
    x: 100,
    y: 200,
    latitude: 40.7128,
    longitude: -74.0060,
    elevation: 10.5,
    geohash: 'dr5regy'
);

// Calculate distance
$distance = $coords->distanceTo($otherCoords);

// Check if within radius
$isNearby = $coords->isWithinRadius($center, 50);
```

### 2. ResourceAmounts
**File**: `app/ValueObjects/ResourceAmounts.php`

Represents the four main game resources: wood, clay, iron, and crop.

```php
use App\ValueObjects\ResourceAmounts;

// Create resource amounts
$resources = new ResourceAmounts(
    wood: 1000,
    clay: 2000,
    iron: 1500,
    crop: 800
);

// Add resources
$newResources = $resources->add(new ResourceAmounts(wood: 100, clay: 100, iron: 100, crop: 100));

// Check affordability
$canAfford = $resources->canAfford(new ResourceAmounts(wood: 500, clay: 500, iron: 500, crop: 500));

// Get total amount
$total = $resources->getTotal();
```

### 3. PlayerStats
**File**: `app/ValueObjects/PlayerStats.php`

Represents player statistics including points, population, villages, and military strength.

```php
use App\ValueObjects\PlayerStats;

// Create player stats
$stats = new PlayerStats(
    points: 100000,
    population: 5000,
    villagesCount: 10,
    totalAttackPoints: 8000,
    totalDefensePoints: 6000,
    isActive: true,
    isOnline: true
);

// Get ranking category
$category = $stats->getRankingCategory(); // 'experienced'

// Check military balance
$isBalanced = $stats->hasBalancedMilitary();

// Get efficiency score
$efficiency = $stats->getEfficiencyScore();
```

### 4. BattleResult
**File**: `app/ValueObjects/BattleResult.php`

Represents the outcome of a battle with losses, loot, and statistics.

```php
use App\ValueObjects\BattleResult;
use App\ValueObjects\ResourceAmounts;

// Create battle result
$loot = new ResourceAmounts(wood: 1000, clay: 2000, iron: 1500, crop: 800);
$result = new BattleResult(
    status: 'victory',
    attackerLosses: 100,
    defenderLosses: 200,
    loot: $loot,
    duration: 3600
);

// Check if victory
$isVictory = $result->isVictory();

// Get battle efficiency
$efficiency = $result->getBattleEfficiency();

// Get severity
$severity = $result->getSeverity(); // 'moderate'
```

### 5. TroopCounts
**File**: `app/ValueObjects/TroopCounts.php`

Represents army composition with different troop types.

```php
use App\ValueObjects\TroopCounts;

// Create troop counts
$army = new TroopCounts(
    spearmen: 100,
    swordsmen: 50,
    archers: 75,
    cavalry: 25,
    mountedArchers: 10,
    catapults: 5,
    rams: 3,
    spies: 2,
    settlers: 1
);

// Get army composition
$composition = $army->getComposition();

// Get army type
$type = $army->getArmyType(); // 'infantry-heavy'

// Check if balanced
$isBalanced = $army->isBalanced();
```

### 6. VillageResources
**File**: `app/ValueObjects/VillageResources.php`

Represents all resources for a village including amounts, production, and capacity.

```php
use App\ValueObjects\VillageResources;
use App\ValueObjects\ResourceAmounts;

// Create village resources
$amounts = new ResourceAmounts(wood: 1000, clay: 2000, iron: 1500, crop: 800);
$production = new ResourceAmounts(wood: 100, clay: 200, iron: 150, crop: 80);
$capacity = new ResourceAmounts(wood: 2000, clay: 4000, iron: 3000, crop: 1600);

$villageResources = new VillageResources($amounts, $production, $capacity);

// Check storage status
$isNearlyFull = $villageResources->isStorageNearlyFull();

// Get utilization percentage
$utilization = $villageResources->getUtilizationPercentage();

// Get time to fill storage
$timeToFill = $villageResources->getTimeToFillStorage();
```

## Service Integration

### ValueObjectService
**File**: `app/Services/ValueObjectService.php`

A service class that helps integrate value objects with existing Eloquent models.

```php
use App\Services\ValueObjectService;

$service = app(ValueObjectService::class);

// Create value objects from models
$villageResources = $service->createVillageResources($village);
$coordinates = $service->createCoordinatesFromVillage($village);
$playerStats = $service->createPlayerStatsFromPlayer($player);

// Update database from value objects
$service->updateVillageResourcesInDatabase($village, $villageResources);
```

## Livewire Integration

### VillageResourcesComponent
**File**: `app/Livewire/Game/VillageResourcesComponent.php`

Example Livewire component that uses value objects.

```php
use App\Livewire\Game\VillageResourcesComponent;

// In your Blade template
<livewire:game.village-resources :village="$village" />
```

## Model Integration

### Village Model
The Village model now has a `coordinates` attribute that returns a Coordinates value object:

```php
$village = Village::find(1);
$coordinates = $village->coordinates;
$distance = $coordinates->distanceTo($otherCoordinates);
```

### Player Model
The Player model now has a `stats` attribute that returns a PlayerStats value object:

```php
$player = Player::find(1);
$stats = $player->stats;
$category = $stats->getRankingCategory();
```

## Benefits

1. **Immutability**: Once created, value objects cannot be modified, preventing accidental data corruption
2. **Type Safety**: Strong typing ensures data integrity
3. **Rich Behavior**: Value objects contain business logic relevant to the data they represent
4. **Testability**: Easy to unit test with clear, predictable behavior
5. **Code Clarity**: Business logic is encapsulated within the value objects
6. **Laravel Integration**: Seamless integration with Eloquent models and Livewire components

## Usage Examples

### In Controllers
```php
public function updateVillageResources(Village $village, Request $request)
{
    $service = app(ValueObjectService::class);
    $resources = $service->createVillageResources($village);
    
    // Add new resources
    $additionalResources = ResourceAmounts::fromArray($request->input('resources'));
    $newResources = $resources->addResources($additionalResources);
    
    // Update database
    $service->updateVillageResourcesInDatabase($village, $newResources);
    
    return response()->json(['success' => true]);
}
```

### In Livewire Components
```php
public function upgradeBuilding(Village $village, string $resourceType)
{
    $service = app(ValueObjectService::class);
    $resources = $service->createVillageResources($village);
    
    if ($resources->canAfford($this->getUpgradeCost($resourceType))) {
        // Perform upgrade logic
        $this->performUpgrade($village, $resourceType);
        $this->loadResources(); // Refresh the component
    } else {
        $this->addError('resources', 'Insufficient resources');
    }
}
```

## Testing

Unit tests are available in `tests/Unit/ValueObjectsTest.php` to verify the correct behavior of all value objects.

## Future Enhancements

1. **Database Casting**: Implement custom Eloquent casts for automatic serialization/deserialization
2. **Validation**: Add validation rules for value object creation
3. **Collections**: Create specialized collections for value objects
4. **Events**: Add domain events for value object changes
5. **Caching**: Implement caching strategies for frequently accessed value objects

## Migration Guide

To migrate existing code to use value objects:

1. Replace direct property access with value object methods
2. Update form validation to work with value objects
3. Modify API responses to serialize value objects properly
4. Update tests to use value object assertions
5. Gradually refactor business logic into value object methods

This integration provides a solid foundation for maintaining data integrity and improving code quality throughout the application.

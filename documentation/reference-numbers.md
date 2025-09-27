# Laravel Referenceable Integration

## Overview

The game system now uses the Laravel Referenceable package to generate unique reference numbers for all major game entities. This provides better tracking, debugging, and user experience.

## Package Installation

```bash
composer require eg-mohamed/referenceable
php artisan referenceable:install
```

## Integrated Models

### Core Game Models

| Model | Reference Format | Example | Description |
|-------|------------------|---------|-------------|
| Movement | `MOV-{YEAR}{MONTH}{SEQ}` | MOV-2025090001 | Attack/defense movements |
| Battle | `BTL-{YEAR}{MONTH}{SEQ}` | BTL-2025090001 | Battle records |
| Report | `RPT-{YEAR}{MONTH}{SEQ}` | RPT-2025090001 | Battle reports |
| TrainingQueue | `TRN-{YEAR}{MONTH}{SEQ}` | TRN-2025090001 | Unit training orders |
| Task | `TSK-{YEAR}{MONTH}{SEQ}` | TSK-2025090001 | Player tasks |
| GameEvent | `EVT-{YEAR}{MONTH}{SEQ}` | EVT-2025090001 | Game events |
| BuildingQueue | `BLD-{YEAR}{MONTH}{SEQ}` | BLD-2025090001 | Building orders |
| Quest | `QST-{YEAR}{MONTH}{SEQ}` | QST-2025090001 | Quest templates |

### Player & Social Models

| Model | Reference Format | Example | Description |
|-------|------------------|---------|-------------|
| Achievement | `ACH-{YEAR}{MONTH}{SEQ}` | ACH-2025090001 | Achievement templates |
| Alliance | `ALL-{YEAR}{MONTH}{SEQ}` | ALL-2025090001 | Alliances |
| PlayerAchievement | `PACH-{YEAR}{MONTH}{SEQ}` | PACH-2025090001 | Player achievements |
| AllianceMember | `AM-{YEAR}{MONTH}{SEQ}` | AM-2025090001 | Alliance memberships |
| PlayerQuest | `PQ-{YEAR}{MONTH}{SEQ}` | PQ-2025090001 | Player quest progress |
| PlayerNote | `PN-{YEAR}{MONTH}{SEQ}` | PN-2025090001 | Player notes |

### System Models

| Model | Reference Format | Example | Description |
|-------|------------------|---------|-------------|
| ResourceProductionLog | `RPL-{YEAR}{MONTH}{SEQ}` | RPL-2025090001 | Resource production logs |
| PlayerStatistic | `PS-{YEAR}{MONTH}{SEQ}` | PS-2025090001 | Player statistics |
| GameTask | `GT-{YEAR}{MONTH}{SEQ}` | GT-2025090001 | System game tasks |

### Advanced Game Models

| Model | Reference Format | Example | Description |
|-------|------------------|---------|-------------|
| AllianceWar | `WAR-{YEAR}{MONTH}{SEQ}` | WAR-2025090001 | Alliance wars |
| Hero | `HERO-{YEAR}{MONTH}{SEQ}` | HERO-2025090001 | Player heroes |
| SiegeWeapon | `SW-{YEAR}{MONTH}{SEQ}` | SW-2025090001 | Siege weapons |

## Database Schema

All models have a `reference_number` column with:
- Type: `VARCHAR(255)`
- Unique constraint
- Index for performance
- Nullable (for existing records)

## Usage

### Generating Reference Numbers

```php
// Create a new model instance
$movement = new Movement();
$movement->player_id = 1;
$movement->from_village_id = 1;
$movement->to_village_id = 2;
$movement->type = 'attack';
$movement->status = 'travelling';
$movement->save();

// Generate reference number
$movement->generateReference();

echo $movement->reference_number; // MOV-2025090001
```

### Finding by Reference

```php
// Find a model by reference number
$movement = Movement::findByReference('MOV-2025090001');
```

### Query Scopes

```php
// Find models with reference numbers
$movements = Movement::withReference()->get();

// Find models without reference numbers
$movements = Movement::withoutReference()->get();

// Find models where reference starts with specific prefix
$movements = Movement::referenceStartsWith('MOV-202509')->get();
```

## Livewire Integration

### BattleManager
- Generates reference numbers for attack movements
- Displays reference numbers in attack notifications

### MovementManager
- Generates reference numbers for all movements
- Displays reference numbers in movement lists

### TroopManager
- Generates reference numbers for training queues
- Displays reference numbers in training notifications

## UI Display

Reference numbers are displayed in:
- Movement lists
- Battle reports
- Task lists
- Training notifications
- Attack notifications

## Benefits

1. **Unique Tracking**: Every game action has a unique identifier
2. **Better Support**: Players can reference specific actions when reporting issues
3. **Audit Trail**: Complete tracking of all game activities
4. **User Experience**: Players can track their actions more easily
5. **Debugging**: Developers can quickly locate specific game events

## Configuration

Each model has the following configuration:

```php
// Referenceable configuration
protected $referenceColumn = 'reference_number';
protected $referenceStrategy = 'template';
protected $referenceTemplate = [
    'format' => 'PREFIX-{YEAR}{MONTH}{SEQ}',
    'sequence_length' => 4,
];
protected $referencePrefix = 'PREFIX';
```

## Migration Status

All migrations have been created and applied:
- ✅ movements table
- ✅ battles table
- ✅ reports table
- ✅ training_queues table
- ✅ player_tasks table
- ✅ game_events table
- ✅ building_queues table
- ✅ quests table
- ✅ achievements table
- ✅ alliances table
- ✅ player_achievements table
- ✅ alliance_members table
- ✅ player_quests table
- ✅ player_notes table
- ✅ resource_production_logs table
- ✅ player_statistics table
- ✅ game_tasks table

## Testing

All models have been tested for reference number generation:
- ✅ Movement: MOV-2025090002
- ✅ Battle: BTL-2025090003
- ✅ Report: RPT-2025090003
- ✅ TrainingQueue: TRN-2025090001
- ✅ Task: TSK-2025090001
- ✅ GameEvent: EVT-2025090001
- ✅ BuildingQueue: BLD-2025090001
- ✅ Quest: QST-2025090001
- ✅ Achievement: ACH-2025090001
- ✅ Alliance: ALL-2025090002
- ✅ PlayerAchievement: PACH-2025090001
- ✅ AllianceMember: AM-2025090004
- ✅ PlayerQuest: PQ-2025090003
- ✅ PlayerNote: PN-2025090002

## Future Enhancements

- Add reference numbers to additional models as needed
- Implement reference number search functionality
- Add reference number filtering in admin panels
- Create reference number reports and analytics

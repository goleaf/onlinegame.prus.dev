# RabbitMQ Integration Documentation

## Overview

This Laravel game application now includes RabbitMQ integration for asynchronous message processing of game events and notifications. The integration uses the `usmonaliyev/laravel-simple-rabbitmq` package.

## Features

- **Game Events**: Player actions, building completions, battle results, resource updates
- **Notifications**: Email, in-game, and push notifications
- **Asynchronous Processing**: Non-blocking event handling
- **Scalable Architecture**: Easy to add new event types and handlers

## Installation

The RabbitMQ integration is already installed and configured. Here's what was added:

### 1. Package Installation

```bash
composer require usmonaliyev/laravel-simple-rabbitmq
```

### 2. Configuration Files

- `config/simple-mq.php` - RabbitMQ connection and queue configuration
- `routes/amqp-handlers.php` - Event handler registration

### 3. Environment Variables

```env
# Simple RabbitMQ Configuration
SIMPLE_MQ_CONNECTION=default
SIMPLE_MQ_QUEUE=game_events

SIMPLE_MQ_HOST=127.0.0.1
SIMPLE_MQ_PORT=5672
SIMPLE_MQ_USERNAME=guest
SIMPLE_MQ_PASSWORD=guest
SIMPLE_MQ_VHOST=/
```

## Architecture

### Components

1. **RabbitMQService** (`app/Services/RabbitMQService.php`)
   - Central service for publishing messages
   - Provides methods for different event types

2. **Event Handlers** (`app/AMQP/Handlers/`)
   - `GameEventHandler.php` - Processes game events
   - `NotificationHandler.php` - Processes notifications

3. **Game Services Integration**
   - `GameTickService` - Publishes battle results, building completions, resource updates
   - `BuildingService` - Publishes building start events
   - `TroopService` - Publishes training start events

## Usage

### Publishing Events

```php
use App\Services\RabbitMQService;

$rabbitMQ = new RabbitMQService();

// Publish player action
$rabbitMQ->publishPlayerAction(123, 'attack', ['target' => 'village_456']);

// Publish building completion
$rabbitMQ->publishBuildingCompleted(789, 101, 'barracks');

// Publish battle result
$rabbitMQ->publishBattleResult(123, 456, [
    'result' => 'attacker_wins',
    'attacker_losses' => ['warrior' => 10],
    'defender_losses' => ['warrior' => 50],
    'resources_looted' => ['wood' => 1000]
]);

// Publish resource update
$rabbitMQ->publishResourceUpdate(789, [
    'wood' => 5000,
    'clay' => 3000,
    'iron' => 2000,
    'crop' => 1000
]);

// Publish notifications
$rabbitMQ->publishEmailNotification(
    'player@example.com',
    'Battle Report - Victory!',
    ['player_name' => 'TestPlayer', 'battle_result' => 'victory']
);

$rabbitMQ->publishInGameNotification(
    123,
    'Your barracks has been completed!',
    ['building_name' => 'barracks', 'level' => 2]
);
```

### Consuming Messages

Start the consumer to process messages:

```bash
# Start consumer for default connection and game_events queue
php artisan amqp:consume default game_events

# Or use the custom command
php artisan rabbitmq:start-consumer
```

## Testing

### Automated Tests

```bash
# Test all event types
php artisan rabbitmq:test

# Test specific event types
php artisan rabbitmq:test --type=game-events
php artisan rabbitmq:test --type=notifications
php artisan rabbitmq:test --type=custom
```

### Demo Interface

Access the demo interface at `/game/rabbitmq-demo` to:

- Publish test events
- View message logs
- Test different event types

## Event Types

### Game Events

1. **Player Actions**
   - Login/logout
   - Building start/complete
   - Training start/complete
   - Attack/defend actions

2. **Building Events**
   - Building completion
   - Building start
   - Building cancellation

3. **Battle Events**
   - Battle results
   - Attack/defense outcomes
   - Resource looting

4. **Resource Events**
   - Resource production updates
   - Storage capacity changes
   - Resource consumption

### Notifications

1. **Email Notifications**
   - Battle reports
   - Building completions
   - System announcements

2. **In-Game Notifications**
   - Real-time updates
   - Achievement notifications
   - Event reminders

3. **Push Notifications**
   - Mobile notifications
   - Web push notifications
   - System alerts

## Configuration

### Queue Settings

The `game_events` queue is configured with:

- **TTL**: 1 hour (3,600,000 ms)
- **Max Length**: 10,000 messages
- **Dead Letter Exchange**: None (messages expire)

### Connection Settings

- **Host**: 127.0.0.1
- **Port**: 5672
- **Username**: guest
- **Password**: guest
- **Virtual Host**: /

## Monitoring

### Logs

All RabbitMQ events are logged with:

- Event type
- Player/village information
- Timestamp
- Event data

### Health Checks

Monitor RabbitMQ health:

```bash
# Check queue status
php artisan queue:monitor

# Check consumer status
ps aux | grep "amqp:consume"
```

## Troubleshooting

### Common Issues

1. **Connection Failed**
   - Ensure RabbitMQ server is running
   - Check connection credentials
   - Verify network connectivity

2. **Messages Not Processing**
   - Start the consumer: `php artisan amqp:consume default game_events`
   - Check handler registration in `routes/amqp-handlers.php`
   - Verify queue configuration

3. **High Memory Usage**
   - Monitor consumer memory usage
   - Restart consumer periodically
   - Check for memory leaks in handlers

### Debug Commands

```bash
# Test RabbitMQ connection
php artisan rabbitmq:test

# View queue status
php artisan queue:work --once

# Check handler registration
php artisan route:list | grep amqp
```

## Performance Considerations

### Optimization Tips

1. **Batch Processing**
   - Process multiple messages in batches
   - Use database transactions for related events

2. **Memory Management**
   - Restart consumers periodically
   - Monitor memory usage
   - Use efficient data structures

3. **Error Handling**
   - Implement retry logic
   - Use dead letter queues for failed messages
   - Log errors for debugging

## Security

### Best Practices

1. **Authentication**
   - Use strong credentials
   - Rotate passwords regularly
   - Limit network access

2. **Data Protection**
   - Encrypt sensitive data
   - Validate message content
   - Implement rate limiting

3. **Access Control**
   - Restrict queue access
   - Monitor message flow
   - Audit event logs

## Future Enhancements

### Planned Features

1. **Advanced Routing**
   - Topic-based routing
   - Message filtering
   - Priority queues

2. **Monitoring Dashboard**
   - Real-time metrics
   - Performance charts
   - Alert system

3. **Scalability Improvements**
   - Multiple consumers
   - Load balancing
   - Auto-scaling

## Support

For issues or questions:

1. Check the logs in `storage/logs/laravel.log`
2. Review RabbitMQ server logs
3. Test with the demo interface
4. Use the test commands for debugging

## Changelog

### Version 1.0.0

- Initial RabbitMQ integration
- Game event publishing
- Notification system
- Demo interface
- Test commands
- Documentation

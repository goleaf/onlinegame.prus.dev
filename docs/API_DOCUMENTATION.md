# Online Game API Documentation

## Overview

The Online Game API is a comprehensive REST API built with Laravel and documented using Scramble. It provides complete functionality for managing game players, villages, buildings, and game mechanics.

## Quick Start

### Base URL

```
http://your-domain.com/api
```

### Authentication

All API endpoints require authentication using Laravel Sanctum Bearer tokens.

```bash
Authorization: Bearer your-token-here
```

### API Documentation

- **Interactive Documentation**: `/docs/api`
- **OpenAPI Specification**: `/docs/api.json`

## API Endpoints

### Core Game Operations

| Method | Endpoint                                  | Description            |
| ------ | ----------------------------------------- | ---------------------- |
| GET    | `/api/user`                               | Get authenticated user |
| GET    | `/api/game/villages`                      | Get player's villages  |
| POST   | `/api/game/create-village`                | Create new village     |
| GET    | `/api/game/village/{id}`                  | Get village details    |
| POST   | `/api/game/village/{id}/upgrade-building` | Upgrade building       |
| GET    | `/api/game/player/stats`                  | Get player statistics  |

### Player Management

| Method | Endpoint                              | Description                 |
| ------ | ------------------------------------- | --------------------------- |
| GET    | `/api/game/players`                   | List all players            |
| POST   | `/api/game/players`                   | Create new player           |
| GET    | `/api/game/players/with-stats`        | Get players with statistics |
| GET    | `/api/game/players/top`               | Get top players             |
| GET    | `/api/game/players/stats/{playerId}`  | Get player statistics       |
| GET    | `/api/game/players/{id}`              | Get specific player         |
| PUT    | `/api/game/players/{id}`              | Update player               |
| DELETE | `/api/game/players/{id}`              | Delete player               |
| PUT    | `/api/game/players/{playerId}/status` | Update player status        |

### Village Management

| Method | Endpoint                                   | Description                  |
| ------ | ------------------------------------------ | ---------------------------- |
| GET    | `/api/game/villages`                       | List all villages            |
| POST   | `/api/game/villages`                       | Create new village           |
| GET    | `/api/game/villages/with-stats`            | Get villages with statistics |
| GET    | `/api/game/villages/by-coordinates`        | Find villages by coordinates |
| GET    | `/api/game/villages/{id}`                  | Get specific village         |
| PUT    | `/api/game/villages/{id}`                  | Update village               |
| DELETE | `/api/game/villages/{id}`                  | Delete village               |
| GET    | `/api/game/villages/{villageId}/details`   | Get village details          |
| GET    | `/api/game/villages/{villageId}/nearby`    | Find nearby villages         |
| PUT    | `/api/game/villages/{villageId}/resources` | Update village resources     |

### User Management

| Method | Endpoint                                   | Description                    |
| ------ | ------------------------------------------ | ------------------------------ |
| GET    | `/api/game/users`                          | List all users                 |
| POST   | `/api/game/users`                          | Create new user                |
| GET    | `/api/game/users/with-game-stats`          | Get users with game statistics |
| GET    | `/api/game/users/activity-stats`           | Get user activity statistics   |
| GET    | `/api/game/users/online`                   | Get online users               |
| GET    | `/api/game/users/search`                   | Search users                   |
| POST   | `/api/game/users/bulk-update-status`       | Bulk update user status        |
| GET    | `/api/game/users/{id}`                     | Get specific user              |
| PUT    | `/api/game/users/{id}`                     | Update user                    |
| DELETE | `/api/game/users/{id}`                     | Delete user                    |
| GET    | `/api/game/users/{userId}/details`         | Get user details               |
| GET    | `/api/game/users/{userId}/feature-toggles` | Get user feature toggles       |
| GET    | `/api/game/users/{userId}/game-history`    | Get user game history          |
| PUT    | `/api/game/users/{userId}/status`          | Update user status             |

### Task Management

| Method | Endpoint                                  | Description                |
| ------ | ----------------------------------------- | -------------------------- |
| GET    | `/api/game/tasks`                         | List all tasks             |
| POST   | `/api/game/tasks`                         | Create new task            |
| GET    | `/api/game/tasks/with-stats`              | Get tasks with statistics  |
| GET    | `/api/game/tasks/overdue`                 | Get overdue tasks          |
| GET    | `/api/game/tasks/player/{playerId}/stats` | Get player task statistics |
| GET    | `/api/game/tasks/{id}`                    | Get specific task          |
| PUT    | `/api/game/tasks/{id}`                    | Update task                |
| DELETE | `/api/game/tasks/{id}`                    | Delete task                |
| POST   | `/api/game/tasks/{taskId}/start`          | Start task                 |
| POST   | `/api/game/tasks/{taskId}/complete`       | Complete task              |
| PUT    | `/api/game/tasks/{taskId}/progress`       | Update task progress       |

### AI Integration

| Method | Endpoint                           | Description                  |
| ------ | ---------------------------------- | ---------------------------- |
| GET    | `/api/game/ai/status`              | Get AI service status        |
| POST   | `/api/game/ai/village-names`       | Generate village names       |
| POST   | `/api/game/ai/alliance-names`      | Generate alliance names      |
| POST   | `/api/game/ai/quest-description`   | Generate quest description   |
| POST   | `/api/game/ai/player-message`      | Generate player message      |
| POST   | `/api/game/ai/battle-report`       | Generate battle report       |
| POST   | `/api/game/ai/world-event`         | Generate world event         |
| POST   | `/api/game/ai/strategy-suggestion` | Generate strategy suggestion |
| POST   | `/api/game/ai/custom-content`      | Generate custom content      |
| POST   | `/api/game/ai/switch-provider`     | Switch AI provider           |

### System Management

| Method | Endpoint                           | Description                 |
| ------ | ---------------------------------- | --------------------------- |
| GET    | `/api/game/system/health`          | Get system health           |
| GET    | `/api/game/system/config`          | Get system configuration    |
| PUT    | `/api/game/system/config`          | Update system configuration |
| GET    | `/api/game/system/logs`            | Get system logs             |
| GET    | `/api/game/system/metrics`         | Get system metrics          |
| GET    | `/api/game/system/scheduled-tasks` | Get scheduled tasks         |
| POST   | `/api/game/system/clear-caches`    | Clear system caches         |

### Larautilx Integration

| Method | Endpoint                                  | Description                   |
| ------ | ----------------------------------------- | ----------------------------- |
| GET    | `/api/game/larautilx/status`              | Get Larautilx status          |
| GET    | `/api/game/larautilx/docs`                | Get Larautilx documentation   |
| POST   | `/api/game/larautilx/test/caching`        | Test caching functionality    |
| POST   | `/api/game/larautilx/test/filtering`      | Test filtering functionality  |
| POST   | `/api/game/larautilx/test/pagination`     | Test pagination functionality |
| GET    | `/api/game/larautilx/cache/stats`         | Get cache statistics          |
| POST   | `/api/game/larautilx/cache/clear`         | Clear all caches              |
| POST   | `/api/game/larautilx/cache/player/clear`  | Clear player caches           |
| POST   | `/api/game/larautilx/cache/village/clear` | Clear village caches          |
| POST   | `/api/game/larautilx/cache/world/clear`   | Clear world caches            |
| GET    | `/api/game/larautilx/dashboard`           | Get Larautilx dashboard data  |
| GET    | `/api/game/larautilx/integration-summary` | Get integration summary       |
| POST   | `/api/game/larautilx/test-components`     | Test Larautilx components     |

### Artifact System

| Method | Endpoint                              | Description               |
| ------ | ------------------------------------- | ------------------------- |
| GET    | `/api/game/artifacts`                 | List all artifacts        |
| POST   | `/api/game/artifacts`                 | Create new artifact       |
| GET    | `/api/game/artifacts/server-wide`     | Get server-wide artifacts |
| POST   | `/api/game/artifacts/generate-random` | Generate random artifact  |
| GET    | `/api/game/artifacts/{id}`            | Get specific artifact     |
| PUT    | `/api/game/artifacts/{id}`            | Update artifact           |
| DELETE | `/api/game/artifacts/{id}`            | Delete artifact           |
| POST   | `/api/game/artifacts/{id}/activate`   | Activate artifact         |
| POST   | `/api/game/artifacts/{id}/deactivate` | Deactivate artifact       |
| GET    | `/api/game/artifacts/{id}/effects`    | Get artifact effects      |

### Message System

| Method | Endpoint                                          | Description                  |
| ------ | ------------------------------------------------- | ---------------------------- |
| GET    | `/api/game/messages/inbox`                        | Get inbox messages           |
| GET    | `/api/game/messages/sent`                         | Get sent messages            |
| GET    | `/api/game/messages/alliance`                     | Get alliance messages        |
| GET    | `/api/game/messages/conversation/{otherPlayerId}` | Get conversation with player |
| GET    | `/api/game/messages/stats`                        | Get message statistics       |
| GET    | `/api/game/messages/players`                      | Get players for messaging    |
| POST   | `/api/game/messages/send`                         | Send private message         |
| POST   | `/api/game/messages/send-alliance`                | Send alliance message        |
| POST   | `/api/game/messages/bulk-mark-read`               | Bulk mark messages as read   |
| POST   | `/api/game/messages/bulk-delete`                  | Bulk delete messages         |
| GET    | `/api/game/messages/{messageId}`                  | Get specific message         |
| POST   | `/api/game/messages/{messageId}/mark-read`        | Mark message as read         |
| DELETE | `/api/game/messages/{messageId}`                  | Delete message               |

### Alliance System

| Method | Endpoint                             | Description            |
| ------ | ------------------------------------ | ---------------------- |
| GET    | `/api/game/alliances`                | List all alliances     |
| POST   | `/api/game/alliances`                | Create new alliance    |
| GET    | `/api/game/alliances/{id}`           | Get specific alliance  |
| PUT    | `/api/game/alliances/{id}`           | Update alliance        |
| DELETE | `/api/game/alliances/{id}`           | Disband alliance       |
| POST   | `/api/game/alliances/{id}/join`      | Join alliance          |
| POST   | `/api/game/alliances/leave`          | Leave alliance         |
| GET    | `/api/game/alliances/{id}/members`   | Get alliance members   |
| GET    | `/api/game/alliances/{id}/wars`      | Get alliance wars      |
| GET    | `/api/game/alliances/{id}/diplomacy` | Get alliance diplomacy |

### Battle System

| Method | Endpoint                        | Description            |
| ------ | ------------------------------- | ---------------------- |
| GET    | `/api/game/battles`             | List all battles       |
| POST   | `/api/game/battles`             | Create battle report   |
| GET    | `/api/game/battles/my-battles`  | Get player's battles   |
| GET    | `/api/game/battles/statistics`  | Get battle statistics  |
| GET    | `/api/game/battles/leaderboard` | Get battle leaderboard |
| GET    | `/api/game/battles/war/{warId}` | Get war battles        |
| GET    | `/api/game/battles/{id}`        | Get specific battle    |

### Quest & Achievement System

| Method | Endpoint                                    | Description                 |
| ------ | ------------------------------------------- | --------------------------- |
| GET    | `/api/game/quests`                          | List all quests             |
| GET    | `/api/game/quests/my-quests`                | Get player's quests         |
| GET    | `/api/game/quests/statistics`               | Get quest statistics        |
| GET    | `/api/game/quests/achievements`             | Get player achievements     |
| GET    | `/api/game/quests/achievements/leaderboard` | Get achievement leaderboard |
| GET    | `/api/game/quests/{id}`                     | Get specific quest          |
| POST   | `/api/game/quests/{id}/start`               | Start quest                 |
| POST   | `/api/game/quests/{id}/complete`            | Complete quest              |

### Report System

| Method | Endpoint                           | Description              |
| ------ | ---------------------------------- | ------------------------ |
| GET    | `/api/game/reports`                | List all reports         |
| GET    | `/api/game/reports/statistics`     | Get report statistics    |
| GET    | `/api/game/reports/unread-count`   | Get unread reports count |
| POST   | `/api/game/reports/mark-all-read`  | Mark all reports as read |
| GET    | `/api/game/reports/{id}`           | Get specific report      |
| POST   | `/api/game/reports/{id}/mark-read` | Mark report as read      |
| DELETE | `/api/game/reports/{id}`           | Delete report            |

### Notification System

| Method | Endpoint                                 | Description                    |
| ------ | ---------------------------------------- | ------------------------------ |
| GET    | `/api/game/notifications`                | List all notifications         |
| POST   | `/api/game/notifications`                | Create notification            |
| GET    | `/api/game/notifications/statistics`     | Get notification statistics    |
| GET    | `/api/game/notifications/unread-count`   | Get unread notifications count |
| POST   | `/api/game/notifications/mark-all-read`  | Mark all notifications as read |
| GET    | `/api/game/notifications/{id}`           | Get specific notification      |
| POST   | `/api/game/notifications/{id}/mark-read` | Mark notification as read      |
| DELETE | `/api/game/notifications/{id}`           | Delete notification            |

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data here
  },
  "message": "Operation completed successfully"
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header:

```bash
curl -H "Authorization: Bearer your-token-here" \
     -H "Content-Type: application/json" \
     http://your-domain.com/api/game/villages
```

## Rate Limiting

API requests are rate-limited to prevent abuse. Rate limits are applied per user and endpoint.

## Error Codes

| Code | Description           |
| ---- | --------------------- |
| 200  | Success               |
| 201  | Created               |
| 400  | Bad Request           |
| 401  | Unauthorized          |
| 403  | Forbidden             |
| 404  | Not Found             |
| 422  | Validation Error      |
| 429  | Too Many Requests     |
| 500  | Internal Server Error |

## Development

### Local Development

```bash
# Start the development server
php artisan serve

# Access API documentation
http://localhost:8000/docs/api
```

### Testing

```bash
# Run API tests
php artisan test --filter=ApiTest

# Generate API documentation
php artisan scramble:export
```

## Support

For API support and questions:

- **Email**: api@game.example.com
- **Documentation**: `/docs/api`
- **OpenAPI Spec**: `/docs/api.json`

## License

This API is licensed under the MIT License.

<?php

use Usmonaliyev\SimpleRabbit\Facades\ActionMQ;
use App\AMQP\Handlers\GameEventHandler;
use App\AMQP\Handlers\NotificationHandler;

// Register game event handlers
ActionMQ::register('game_event', [GameEventHandler::class, 'handle']);

// Register notification handlers
ActionMQ::register('notification', [NotificationHandler::class, 'handle']);
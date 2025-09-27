<?php

// Test file to demonstrate translation checker functionality

// Example of using translation keys
echo __('messages.welcome');
echo __('messages.hello');
echo __('messages.game');
echo __('messages.player');
echo __('messages.village');
echo __('messages.resources');
echo __('messages.attack');
echo __('messages.defense');

// Example with parameters
echo __('messages.welcome', ['name' => 'Player']);
echo __('messages.hello', ['name' => 'Admin']);

// Example with pluralization
echo trans_choice('messages.players', 1);
echo trans_choice('messages.players', 5);

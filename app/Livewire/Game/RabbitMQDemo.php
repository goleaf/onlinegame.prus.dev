<?php

namespace App\Livewire\Game;

use App\Services\RabbitMQService;
use Livewire\Component;

class RabbitMQDemo extends Component
{
    public $playerId = 1;

    public $villageId = 1;

    public $message = '';

    public $messages = [];

    protected $rabbitMQ;

    public function mount()
    {
        $this->rabbitMQ = new RabbitMQService();
    }

    public function publishPlayerAction()
    {
        $this->rabbitMQ->publishPlayerAction(
            $this->playerId,
            'demo_action',
            [
                'action_type' => 'demo',
                'timestamp' => now()->toISOString(),
                'village_id' => $this->villageId,
            ]
        );

        $this->addMessage('Player action published successfully!');
    }

    public function publishBuildingEvent()
    {
        $this->rabbitMQ->publishBuildingCompleted(
            $this->villageId,
            1,
            'demo_building'
        );

        $this->addMessage('Building completion event published successfully!');
    }

    public function publishBattleEvent()
    {
        $this->rabbitMQ->publishBattleResult(
            $this->playerId,
            2,
            [
                'result' => 'attacker_wins',
                'demo' => true,
                'timestamp' => now()->toISOString(),
                'defensive_bonus' => 0.12,
                'battle_power' => [
                    'attacker' => 1800,
                    'defender' => 1500,
                ],
            ]
        );

        $this->addMessage('Battle result event with defensive bonuses published successfully!');
    }

    public function publishResourceUpdate()
    {
        $this->rabbitMQ->publishResourceUpdate(
            $this->villageId,
            [
                'wood' => rand(1000, 5000),
                'clay' => rand(1000, 5000),
                'iron' => rand(1000, 5000),
                'crop' => rand(1000, 5000),
            ]
        );

        $this->addMessage('Resource update event published successfully!');
    }

    public function publishNotification()
    {
        $this->rabbitMQ->publishInGameNotification(
            $this->playerId,
            'Demo notification from RabbitMQ!',
            [
                'type' => 'demo',
                'timestamp' => now()->toISOString(),
            ]
        );

        $this->addMessage('In-game notification published successfully!');
    }

    public function publishEmailNotification()
    {
        $this->rabbitMQ->publishEmailNotification(
            'demo@example.com',
            'Demo Email from RabbitMQ',
            [
                'player_id' => $this->playerId,
                'message' => 'This is a demo email notification sent via RabbitMQ!',
                'timestamp' => now()->toISOString(),
            ]
        );

        $this->addMessage('Email notification published successfully!');
    }

    public function publishCustomEvent()
    {
        $this->rabbitMQ->publishGameEvent('demo_custom_event', [
            'player_id' => $this->playerId,
            'village_id' => $this->villageId,
            'custom_data' => [
                'demo' => true,
                'message' => 'This is a custom demo event!',
                'timestamp' => now()->toISOString(),
            ],
        ]);

        $this->addMessage('Custom game event published successfully!');
    }

    public function publishSpyEvent()
    {
        $this->rabbitMQ->publishPlayerAction(
            $this->playerId,
            'spy_caught',
            [
                'target_village_id' => 2,
                'target_village_name' => 'Demo Target Village',
                'trap_level' => 3,
                'spy_defense' => 15,
                'demo' => true,
            ]
        );

        $this->addMessage('Spy caught event published successfully!');
    }

    public function clearMessages()
    {
        $this->messages = [];
    }

    private function addMessage($message)
    {
        $this->messages[] = [
            'text' => $message,
            'timestamp' => now()->format('H:i:s'),
        ];
    }

    public function render()
    {
        return view('livewire.game.rabbitmq-demo');
    }
}

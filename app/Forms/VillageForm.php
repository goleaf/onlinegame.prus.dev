<?php

namespace App\Forms;

use App\Models\Game\Player;
use App\Models\Game\World;
use Metalogico\Formello\Formello;

class VillageForm extends Formello
{
    protected function create(): array
    {
        return [
            'method' => 'POST',
            'action' => route('game.api.villages.store'),
        ];
    }

    protected function edit(): array
    {
        return [
            'method' => 'PATCH',
            'action' => route('game.api.villages.update', $this->model->id),
        ];
    }

    protected function fields(): array
    {
        return [
            'name' => [
                'label' => __('Village Name'),
                'required' => true,
                'maxlength' => 50,
            ],
            'player_id' => [
                'label' => __('Player'),
                'widget' => 'select',
                'choices' => Player::pluck('name', 'id')->toArray(),
                'required' => true,
            ],
            'world_id' => [
                'label' => __('World'),
                'widget' => 'select',
                'choices' => World::pluck('name', 'id')->toArray(),
                'required' => true,
            ],
            'x_coordinate' => [
                'label' => __('X Coordinate'),
                'type' => 'number',
                'min' => 0,
                'max' => 1000,
                'required' => true,
            ],
            'y_coordinate' => [
                'label' => __('Y Coordinate'),
                'type' => 'number',
                'min' => 0,
                'max' => 1000,
                'required' => true,
            ],
            'population' => [
                'label' => __('Population'),
                'type' => 'number',
                'min' => 0,
                'required' => true,
            ],
            'is_capital' => [
                'label' => __('Capital Village'),
                'widget' => 'checkbox',
            ],
            'is_active' => [
                'label' => __('Active Village'),
                'widget' => 'checkbox',
            ],
        ];
    }
}

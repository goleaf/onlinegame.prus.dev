<?php

namespace App\Forms;

use App\Models\Game\Alliance;
use App\Models\Game\World;
use App\Models\User;
use Metalogico\Formello\Formello;

class PlayerForm extends Formello
{
    protected function create(): array
    {
        return [
            'method' => 'POST',
            'action' => route('players.store'),
        ];
    }

    protected function edit(): array
    {
        return [
            'method' => 'PATCH',
            'action' => route('players.update', $this->model->id),
        ];
    }

    protected function fields(): array
    {
        return [
            'name' => [
                'label' => __('Player Name'),
                'required' => true,
                'maxlength' => 50,
            ],
            'tribe' => [
                'label' => __('Tribe'),
                'widget' => 'select',
                'choices' => [
                    'romans' => __('Romans'),
                    'teutons' => __('Teutons'),
                    'gauls' => __('Gauls'),
                ],
                'required' => true,
            ],
            'world_id' => [
                'label' => __('World'),
                'widget' => 'select',
                'choices' => World::pluck('name', 'id')->toArray(),
                'required' => true,
            ],
            'alliance_id' => [
                'label' => __('Alliance'),
                'widget' => 'select',
                'choices' => ['' => __('No Alliance')] + Alliance::pluck('name', 'id')->toArray(),
            ],
            'is_active' => [
                'label' => __('Active Player'),
                'widget' => 'checkbox',
            ],
        ];
    }
}

<?php

namespace App\Forms;

use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use Metalogico\Formello\Formello;
use Intervention\Validation\Rules\Username;

class BuildingForm extends Formello
{
    protected function create(): array
    {
        return [
            'method' => 'POST',
            'action' => route('game.api.buildings.store'),
        ];
    }

    protected function edit(): array
    {
        return [
            'method' => 'PATCH',
            'action' => route('game.api.buildings.update', $this->model->id),
        ];
    }

    protected function fields(): array
    {
        return [
            'village_id' => [
                'label' => __('Village'),
                'widget' => 'select',
                'choices' => Village::pluck('name', 'id')->toArray(),
                'required' => true,
            ],
            'building_type_id' => [
                'label' => __('Building Type'),
                'widget' => 'select',
                'choices' => BuildingType::pluck('name', 'id')->toArray(),
                'required' => true,
            ],
            'name' => [
                'label' => __('Building Name'),
                'maxlength' => 100,
                'rules' => [new Username()],
            ],
            'level' => [
                'label' => __('Level'),
                'type' => 'number',
                'min' => 0,
                'max' => 20,
                'required' => true,
            ],
            'x' => [
                'label' => __('X Position'),
                'type' => 'number',
                'min' => 0,
                'max' => 18,
            ],
            'y' => [
                'label' => __('Y Position'),
                'type' => 'number',
                'min' => 0,
                'max' => 18,
            ],
            'is_active' => [
                'label' => __('Active Building'),
                'widget' => 'checkbox',
            ],
        ];
    }
}

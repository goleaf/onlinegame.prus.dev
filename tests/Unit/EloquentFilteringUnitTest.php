<?php

namespace Tests\Unit;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use App\Models\User;
use Tests\TestCase;

class EloquentFilteringUnitTest extends TestCase
{
    public function test_player_model_implements_filterable_interface()
    {
        $player = new Player();
        
        $this->assertTrue(method_exists($player, 'allowedFilters'));
        $this->assertTrue(method_exists($player, 'filter'));
        
        // Test that the model has the Filterable trait
        $traits = class_uses_recursive(Player::class);
        $this->assertContains('IndexZer0\EloquentFiltering\Filter\Traits\Filterable', $traits);
    }

    public function test_village_model_implements_filterable_interface()
    {
        $village = new Village();
        
        $this->assertTrue(method_exists($village, 'allowedFilters'));
        $this->assertTrue(method_exists($village, 'filter'));
        
        // Test that the model has the Filterable trait
        $traits = class_uses_recursive(Village::class);
        $this->assertContains('IndexZer0\EloquentFiltering\Filter\Traits\Filterable', $traits);
    }

    public function test_alliance_model_implements_filterable_interface()
    {
        $alliance = new Alliance();
        
        $this->assertTrue(method_exists($alliance, 'allowedFilters'));
        $this->assertTrue(method_exists($alliance, 'filter'));
        
        // Test that the model has the Filterable trait
        $traits = class_uses_recursive(Alliance::class);
        $this->assertContains('IndexZer0\EloquentFiltering\Filter\Traits\Filterable', $traits);
    }

    public function test_user_model_implements_filterable_interface()
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'allowedFilters'));
        $this->assertTrue(method_exists($user, 'filter'));
        
        // Test that the model has the Filterable trait
        $traits = class_uses_recursive(User::class);
        $this->assertContains('IndexZer0\EloquentFiltering\Filter\Traits\Filterable', $traits);
    }

    public function test_player_allowed_filters_returns_correct_structure()
    {
        $player = new Player();
        $allowedFilters = $player->allowedFilters();
        
        $this->assertInstanceOf('IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList', $allowedFilters);
    }

    public function test_village_allowed_filters_returns_correct_structure()
    {
        $village = new Village();
        $allowedFilters = $village->allowedFilters();
        
        $this->assertInstanceOf('IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList', $allowedFilters);
    }

    public function test_alliance_allowed_filters_returns_correct_structure()
    {
        $alliance = new Alliance();
        $allowedFilters = $alliance->allowedFilters();
        
        $this->assertInstanceOf('IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList', $allowedFilters);
    }

    public function test_user_allowed_filters_returns_correct_structure()
    {
        $user = new User();
        $allowedFilters = $user->allowedFilters();
        
        $this->assertInstanceOf('IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList', $allowedFilters);
    }

    public function test_eloquent_filtering_package_is_installed()
    {
        $this->assertTrue(class_exists('IndexZer0\EloquentFiltering\Filter\Traits\Filterable'));
        $this->assertTrue(interface_exists('IndexZer0\EloquentFiltering\Contracts\IsFilterable'));
        $this->assertTrue(interface_exists('IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList'));
        $this->assertTrue(class_exists('IndexZer0\EloquentFiltering\Filter\Filterable\Filter'));
        $this->assertTrue(enum_exists('IndexZer0\EloquentFiltering\Filter\FilterType'));
    }
}


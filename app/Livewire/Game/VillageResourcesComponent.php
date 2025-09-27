<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Services\ValueObjectService;
use App\ValueObjects\VillageResources;
use Livewire\Component;

class VillageResourcesComponent extends Component
{
    public Village $village;
    public VillageResources $resources;

    public function mount(Village $village)
    {
        $this->village = $village;
        $this->loadResources();
    }

    public function loadResources()
    {
        $valueObjectService = app(ValueObjectService::class);
        $this->resources = $valueObjectService->createVillageResources($this->village);
    }

    public function render()
    {
        return view('livewire.game.village-resources', [
            'resources' => $this->resources,
            'village' => $this->village,
        ]);
    }

    public function getResourceBalanceProperty()
    {
        return $this->resources->getResourceBalance();
    }

    public function getIsStorageNearlyFullProperty()
    {
        return $this->resources->isStorageNearlyFull();
    }

    public function getTimeToFillStorageProperty()
    {
        return $this->resources->getTimeToFillStorage();
    }

    public function getMostAbundantResourceProperty()
    {
        return $this->resources->getMostAbundantResource();
    }

    public function getLeastAbundantResourceProperty()
    {
        return $this->resources->getLeastAbundantResource();
    }

    public function getResourceEfficiencyProperty()
    {
        return $this->resources->getResourceEfficiency();
    }

    public function getStorageUtilizationProperty()
    {
        return $this->resources->getStorageUtilization();
    }

    public function getProductionRatesProperty()
    {
        return $this->resources->getProductionRates();
    }

    public function getResourceRecommendationsProperty()
    {
        return $this->resources->getResourceRecommendations();
    }

    public function getVillageCoordinatesProperty()
    {
        return $this->village->coordinates;
    }

    public function getPlayerStatsProperty()
    {
        return $this->village->player->stats;
    }
}

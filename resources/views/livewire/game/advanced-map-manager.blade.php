<div>
    <!-- This component provides data to the JavaScript map manager -->
    <div id="map-data" 
         data-villages="{{ json_encode($this->getVillageData()) }}"
         data-center-x="{{ $centerX }}"
         data-center-y="{{ $centerY }}"
         data-radius="{{ $radius }}"
         data-map-mode="{{ $mapMode }}"
         data-total-villages="{{ $statistics['total_villages'] ?? 0 }}"
         data-my-villages="{{ $statistics['my_villages'] ?? 0 }}"
         data-alliance-villages="{{ $statistics['alliance_villages'] ?? 0 }}"
         data-enemy-villages="{{ $statistics['enemy_villages'] ?? 0 }}"
         style="display: none;">
    </div>
</div>
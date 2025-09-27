<div>
    <!-- This component provides data to the JavaScript map manager -->
    <div id="map-data" 
         data-villages="{{ json_encode($this->getVillageData()) }}"
         data-center-x="{{ $centerX }}"
         data-center-y="{{ $centerY }}"
         data-radius="{{ $radius }}"
         data-map-mode="{{ $mapMode }}"
         data-total-villages="{{ $totalVillages }}"
         data-my-villages="{{ $myVillages }}"
         data-alliance-villages="{{ $allianceVillages }}"
         data-enemy-villages="{{ $enemyVillages }}"
         style="display: none;">
    </div>
</div>
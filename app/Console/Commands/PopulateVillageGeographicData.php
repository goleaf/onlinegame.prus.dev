<?php

namespace App\Console\Commands;

use App\Models\Game\Village;
use App\Services\GeographicService;
use Illuminate\Console\Command;

class PopulateVillageGeographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'villages:populate-geographic-data {--chunk=100 : Number of villages to process at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate geographic data (latitude, longitude, geohash) for all villages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate geographic data for villages...');

        $geoService = app(GeographicService::class);
        $chunkSize = (int) $this->option('chunk');
        $processed = 0;

        Village::whereNull('latitude')
            ->orWhereNull('longitude')
            ->orWhereNull('geohash')
            ->chunk($chunkSize, function ($villages) use ($geoService, &$processed) {
                foreach ($villages as $village) {
                    try {
                        // Calculate real-world coordinates from game coordinates
                        $coords = $geoService->gameToRealWorld(
                            $village->x_coordinate,
                            $village->y_coordinate
                        );

                        // Generate geohash
                        $geohash = $geoService->generateGeohash($coords['lat'], $coords['lon']);

                        // Update village with geographic data
                        $village->update([
                            'latitude' => $coords['lat'],
                            'longitude' => $coords['lon'],
                            'geohash' => $geohash,
                        ]);

                        $processed++;

                        if ($processed % 50 === 0) {
                            $this->info("Processed {$processed} villages...");
                        }
                    } catch (\Exception $e) {
                        $this->error("Error processing village {$village->id}: " . $e->getMessage());
                    }
                }
            });

        $this->info("Completed! Processed {$processed} villages with geographic data.");

        return Command::SUCCESS;
    }
}

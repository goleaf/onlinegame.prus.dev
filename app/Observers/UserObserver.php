<?php

namespace App\Observers;

use App\Models\User;
use App\Services\GeographicService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $startTime = microtime(true);

        ds('UserObserver: User created event triggered', [
            'observer' => 'UserObserver',
            'event' => 'created',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'has_phone' => ! empty($user->phone),
            'event_time' => now(),
        ]);

        $this->formatPhoneNumber($user);

        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('UserObserver: User created event completed', [
            'user_id' => $user->id,
            'processing_time_ms' => $processingTime,
            'phone_formatted' => ! empty($user->phone),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $startTime = microtime(true);

        ds('UserObserver: User updated event triggered', [
            'observer' => 'UserObserver',
            'event' => 'updated',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'changed_attributes' => $user->getDirty(),
            'event_time' => now(),
        ]);

        $this->formatPhoneNumber($user);

        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('UserObserver: User updated event completed', [
            'user_id' => $user->id,
            'processing_time_ms' => $processingTime,
            'attributes_changed' => count($user->getDirty()),
        ]);
    }

    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        if ($user->isDirty(['phone', 'phone_country']) && $user->phone) {
            $user->phone_normalized = preg_replace('/[^0-9]/', '', $user->phone);
            $user->phone_national = preg_replace('/[^0-9]/', '', phone($user->phone, $user->phone_country)->formatNational());
            $user->phone_e164 = phone($user->phone, $user->phone_country)->formatE164();
        }
    }

    /**
     * Format phone number and update related fields
     */
    private function formatPhoneNumber(User $user): void
    {
        if ($user->phone && $user->phone_country) {
            try {
                $phoneNumber = phone($user->phone, $user->phone_country);
                $user->phone_normalized = preg_replace('/[^0-9]/', '', $user->phone);
                $user->phone_national = preg_replace('/[^0-9]/', '', $phoneNumber->formatNational());
                $user->phone_e164 = $phoneNumber->formatE164();
                $user->saveQuietly();
            } catch (\Exception $e) {
                // Handle phone number parsing errors gracefully
                \Log::warning('Failed to format phone number for user '.$user->id.': '.$e->getMessage());
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }

    /**
     * Handle geographic events for user's villages
     */
    public function handleGeographicEvent(User $user, string $eventType, array $data = []): void
    {
        try {
            $geoService = app(GeographicService::class);

            // Get user's villages with geographic data
            $villages = $user->player?->villages()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            if ($villages->isEmpty()) {
                return;
            }

            // Process geographic event for each village
            foreach ($villages as $village) {
                $this->processVillageGeographicEvent($village, $eventType, $data, $geoService);
            }

            ds('UserObserver: Geographic event processed', [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'villages_count' => $villages->count(),
                'event_time' => now(),
            ]);
        } catch (\Exception $e) {
            ds('UserObserver: Geographic event error', [
                'user_id' => $user->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'event_time' => now(),
            ]);
        }
    }

    /**
     * Process geographic event for a specific village
     */
    private function processVillageGeographicEvent($village, string $eventType, array $data, GeographicService $geoService): void
    {
        switch ($eventType) {
            case 'village_created':
                $this->handleVillageCreated($village, $geoService);

                break;
            case 'village_updated':
                $this->handleVillageUpdated($village, $data, $geoService);

                break;
            case 'attack_launched':
                $this->handleAttackLaunched($village, $data, $geoService);

                break;
            case 'defense_activated':
                $this->handleDefenseActivated($village, $data, $geoService);

                break;
        }
    }

    /**
     * Handle village created geographic event
     */
    private function handleVillageCreated($village, GeographicService $geoService): void
    {
        // Update village with geographic data if not present
        if (! $village->latitude || ! $village->longitude) {
            $coords = $geoService->gameToRealWorld($village->x_coordinate, $village->y_coordinate);
            $village->update([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lon'],
                'geohash' => $geoService->generateGeohash($coords['lat'], $coords['lon']),
            ]);
        }

        // Send geographic update to nearby players
        \App\Services\RealTimeGameService::sendGeographicUpdate(
            $village->id,
            'village_created',
            ['village_name' => $village->name]
        );
    }

    /**
     * Handle village updated geographic event
     */
    private function handleVillageUpdated($village, array $data, GeographicService $geoService): void
    {
        // Check if coordinates changed
        if (isset($data['x_coordinate']) || isset($data['y_coordinate'])) {
            $coords = $geoService->gameToRealWorld(
                $data['x_coordinate'] ?? $village->x_coordinate,
                $data['y_coordinate'] ?? $village->y_coordinate
            );

            $village->update([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lon'],
                'geohash' => $geoService->generateGeohash($coords['lat'], $coords['lon']),
            ]);
        }

        // Send geographic update
        \App\Services\RealTimeGameService::sendGeographicUpdate(
            $village->id,
            'village_updated',
            ['village_name' => $village->name, 'changes' => $data]
        );
    }

    /**
     * Handle attack launched geographic event
     */
    private function handleAttackLaunched($village, array $data, GeographicService $geoService): void
    {
        // Send geographic alert to nearby players
        $messageService = app(\App\Services\MessageService::class);
        $messageService->sendGeographicAlert(
            $village->id,
            'attack',
            [
                'radius' => 25,
                'details' => 'Attack launched from '.$village->name,
            ]
        );

        // Send real-time geographic update
        \App\Services\RealTimeGameService::sendGeographicUpdate(
            $village->id,
            'attack_launched',
            ['village_name' => $village->name, 'target' => $data['target'] ?? 'Unknown']
        );
    }

    /**
     * Handle defense activated geographic event
     */
    private function handleDefenseActivated($village, array $data, GeographicService $geoService): void
    {
        // Send geographic update for defense activation
        \App\Services\RealTimeGameService::sendGeographicUpdate(
            $village->id,
            'defense_activated',
            ['village_name' => $village->name, 'defense_type' => $data['defense_type'] ?? 'Unknown']
        );
    }
}

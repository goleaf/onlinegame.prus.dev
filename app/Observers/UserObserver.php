<?php

namespace App\Observers;

use App\Models\User;

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
            'has_phone' => !empty($user->phone),
            'event_time' => now()
        ]);
        
        $this->formatPhoneNumber($user);
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('UserObserver: User created event completed', [
            'user_id' => $user->id,
            'processing_time_ms' => $processingTime,
            'phone_formatted' => !empty($user->phone)
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
            'event_time' => now()
        ]);
        
        $this->formatPhoneNumber($user);
        
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('UserObserver: User updated event completed', [
            'user_id' => $user->id,
            'processing_time_ms' => $processingTime,
            'attributes_changed' => count($user->getDirty())
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
        $startTime = microtime(true);
        
        ds('UserObserver: Phone number formatting started', [
            'user_id' => $user->id,
            'phone_provided' => !empty($user->phone),
            'country_provided' => !empty($user->phone_country),
            'formatting_time' => now()
        ]);
        
        if ($user->phone && $user->phone_country) {
            try {
                $phoneNumber = phone($user->phone, $user->phone_country);
                $user->phone_normalized = preg_replace('/[^0-9]/', '', $user->phone);
                $user->phone_national = preg_replace('/[^0-9]/', '', $phoneNumber->formatNational());
                $user->phone_e164 = $phoneNumber->formatE164();
                $user->saveQuietly();
                
                $formattingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                ds('UserObserver: Phone number formatting completed successfully', [
                    'user_id' => $user->id,
                    'phone_normalized' => $user->phone_normalized,
                    'phone_e164' => $user->phone_e164,
                    'formatting_time_ms' => $formattingTime
                ]);
            } catch (\Exception $e) {
                $formattingTime = round((microtime(true) - $startTime) * 1000, 2);
                
                ds('UserObserver: Phone number formatting failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'formatting_time_ms' => $formattingTime
                ]);
                
                // Handle phone number parsing errors gracefully
                \Log::warning('Failed to format phone number for user ' . $user->id . ': ' . $e->getMessage());
            }
        } else {
            ds('UserObserver: Phone number formatting skipped', [
                'user_id' => $user->id,
                'reason' => 'Phone or country not provided'
            ]);
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
}

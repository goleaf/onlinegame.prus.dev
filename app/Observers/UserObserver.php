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
        $this->formatPhoneNumber($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->formatPhoneNumber($user);
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
                \Log::warning('Failed to format phone number for user ' . $user->id . ': ' . $e->getMessage());
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
}

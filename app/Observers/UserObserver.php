<?php

namespace App\Observers;

use App\Models\User;
use App\Includes\EventBusInterface;
use Illuminate\Support\Facades\App;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Reload the user to make sure all attributes are fresh
        $freshUser = $user->fresh();
        
        // Dispatch user_created event to trigger referral code generation
        $eventBus = App::make(EventBusInterface::class);
        $eventBus->dispatch('user_created', [
            'user_id' => $freshUser->id,
            'firstName' => $freshUser->name ?: 'User',
            'referral_code' => null // No referral code used when admin creates user
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Reload the user to make sure all attributes and meta are fresh
        $freshUser = $user->fresh();
        
        // Check if the user doesn't have a referral code, generate one if needed
        if (empty($freshUser->meta['_canna_referral_code'] ?? null)) {
            $eventBus = App::make(EventBusInterface::class);
            $eventBus->dispatch('user_created', [
                'user_id' => $freshUser->id,
                'firstName' => $freshUser->name ?: 'User',
                'referral_code' => null
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
     * Handle the User "forceDeleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\ReferralService;

class UserCreatedListener
{
    private ReferralService $referralService;

    /**
     * Create the event listener.
     */
    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        // Call the referral service's onUserCreated method to generate referral code
        // and process any referral code that was used during registration
        $this->referralService->onUserCreated($event->payload);
    }
}

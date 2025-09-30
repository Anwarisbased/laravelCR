<?php

namespace App\Listeners;

use App\Events\FirstProductScanned;
use App\Services\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;

class ReferralConversionListener
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
    public function handle(FirstProductScanned $event): void
    {
        // Extract user ID from the event context
        $userId = $event->context['user_snapshot']['identity']['user_id'] ?? null;
        
        if ($userId) {
            // Get the user model
            $user = User::find($userId);
            
            if ($user) {
                // Use the referral service to handle conversion
                $this->referralService->handle_referral_conversion($user);
            }
        }
    }
}

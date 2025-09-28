<?php

namespace App\Services;

use App\Events\ReferralBonusAwarded;
use App\Jobs\AwardReferralBonus;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralBonusAwardedNotification;

class ReferralBonusService
{
    public function awardBonus(User $referrer, User $invitee): void
    {
        // Award points to referrer
        $points = config('cannarewards.referral_bonus_points', 500);
        
        // Dispatch job to award points
        AwardReferralBonus::dispatch($referrer->id, $points, "Referral bonus for {$invitee->email}");
        
        // Update referral record
        Referral::where('referrer_user_id', $referrer->id)
            ->where('invitee_user_id', $invitee->id)
            ->update(['bonus_points_awarded' => $points]);
            
        // Send notification
        $referrer->notify(new ReferralBonusAwardedNotification($points, $invitee));
        
        // Fire event
        event(new ReferralBonusAwarded($referrer, $invitee, $points));
    }
}
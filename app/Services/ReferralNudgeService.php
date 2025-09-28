<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;

class ReferralNudgeService
{
    public function getNudgeOptions(User $user, string $refereeEmail): array
    {
        // Validate email
        if (!filter_var($refereeEmail, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address'];
        }
        
        // Check if already referred
        $existingReferral = Referral::whereHas('invitee', function ($query) use ($refereeEmail) {
            $query->where('email', $refereeEmail);
        })->first();
        
        if ($existingReferral) {
            return ['error' => 'This person has already been referred'];
        }
        
        // Check if the user is trying to refer themselves
        if ($user->email === $refereeEmail) {
            return ['error' => 'You cannot refer yourself'];
        }
        
        return [
            'can_nudge' => true,
            'message' => "Invite {$refereeEmail} to earn bonus points!",
            'referral_code' => $user->referral_code,
        ];
    }
}
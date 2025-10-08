<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use App\Domain\ValueObjects\EmailAddress;
use InvalidArgumentException;

class ReferralNudgeService
{
    public function getNudgeOptions(User $user, $refereeEmail): array
    {
        // Validate email format first if it's a string
        if (is_string($refereeEmail)) {
            try {
                $emailVO = EmailAddress::fromString($refereeEmail);
            } catch (InvalidArgumentException $e) {
                return ['error' => 'Invalid email address'];
            }
        } else {
            $emailVO = $refereeEmail;
        }
        
        // Check if already referred
        $existingReferral = Referral::whereHas('invitee', function ($query) use ($emailVO) {
            $query->where('email', $emailVO->value);
        })->first();
        
        if ($existingReferral) {
            return ['error' => 'This person has already been referred'];
        }
        
        // Check if the user is trying to refer themselves
        if ($user->email === $emailVO->value) {
            return ['error' => 'You cannot refer yourself'];
        }
        
        return [
            'can_nudge' => true,
            'message' => "Invite {$emailVO->value} to earn bonus points!",
            'referral_code' => $user->referral_code,
        ];
    }
}
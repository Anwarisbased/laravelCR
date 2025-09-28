<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ReferralCodeService
{
    public function isValid(string $referralCode): bool
    {
        if (empty($referralCode)) {
            return false;
        }

        // Try to get user from cache first
        $user = Cache::remember("referral_code_{$referralCode}", 3600, function () use ($referralCode) {
            return User::where('referral_code', $referralCode)->first();
        });

        return $user !== null;
    }
    
    public function getUserByReferralCode(string $referralCode): ?User
    {
        if (empty($referralCode)) {
            return null;
        }
        
        // Get user from cache or database
        return Cache::remember("referral_code_{$referralCode}", 3600, function () use ($referralCode) {
            return User::where('referral_code', $referralCode)->first();
        });
    }
    
    public function invalidateReferralCodeCache(string $referralCode): void
    {
        Cache::forget("referral_code_{$referralCode}");
    }
}
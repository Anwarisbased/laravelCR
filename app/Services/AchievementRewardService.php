<?php

namespace App\Services;

use App\Models\User;
use App\Domain\MetaKeys;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Points;

class AchievementRewardService
{
    public function __construct()
    {
        // No dependencies needed in simplified version
    }
    
    public function grantReward(UserId $userId, Points $pointsReward, string $reason): void
    {
        if ($pointsReward->toInt() <= 0) {
            return; // No reward to grant
        }
        
        // Simplified approach - directly update user points
        $user = User::find($userId->toInt());
        if (!$user) {
            return; // User not found
        }
        
        // Update user's points balance using meta keys
        $currentPoints = $user->meta[MetaKeys::POINTS_BALANCE] ?? 0;
        $lifetimePoints = $user->meta[MetaKeys::LIFETIME_POINTS] ?? 0;
        
        $newPoints = $currentPoints + $pointsReward->toInt();
        $newLifetimePoints = $lifetimePoints + $pointsReward->toInt();
        
        $user->update([
            'meta' => array_merge($user->meta ?? [], [
                MetaKeys::POINTS_BALANCE => $newPoints,
                MetaKeys::LIFETIME_POINTS => $newLifetimePoints
            ])
        ]);
    }
}
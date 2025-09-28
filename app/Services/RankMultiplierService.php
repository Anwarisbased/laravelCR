<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rank;

class RankMultiplierService
{
    /**
     * Apply rank-based multiplier to base points
     */
    public function applyMultiplier(int $basePoints, User $user): int
    {
        $rank = app(RankService::class)->getUserRankFromModel($user);
        
        if ($rank) {
            return (int) ($basePoints * $rank->point_multiplier);
        }
        
        return $basePoints; // Default to no multiplier if no rank found
    }
}
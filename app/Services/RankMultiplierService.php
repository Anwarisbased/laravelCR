<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rank;

class RankMultiplierService
{
    private RankService $rankService;
    
    public function __construct(RankService $rankService)
    {
        $this->rankService = $rankService;
    }
    
    /**
     * Apply rank-based multiplier to base points
     */
    public function applyMultiplier(int $basePoints, User $user): int
    {
        $rank = $this->rankService->getUserRankFromModel($user);
        
        if ($rank) {
            return (int) ($basePoints * $rank->point_multiplier);
        }
        
        return $basePoints; // Default to no multiplier if no rank found
    }
}
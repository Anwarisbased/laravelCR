<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Rank;
use Illuminate\Auth\Access\Response;

class UserMeetsRankRequirementPolicy
{
    /**
     * Determine if the user meets a specific rank requirement.
     */
    public function meetsRankRequirement(User $user, Rank $requiredRank): Response
    {
        $userRank = $user->getCurrentRankAttribute();
        
        if (!$userRank) {
            return Response::deny('User does not have a rank.');
        }
        
        // Check if user's current rank meets or exceeds the required rank
        if ($userRank->points_required >= $requiredRank->points_required) {
            return Response::allow('User meets the rank requirement.');
        }
        
        return Response::deny('User does not meet the required rank.');
    }
    
    /**
     * Determine if the user has a specific rank or higher
     */
    public function hasRankOrHigher(User $user, string $requiredRankKey): Response
    {
        $allRanks = Rank::active()->ordered()->get();
        $requiredRank = $allRanks->firstWhere('key', $requiredRankKey);
        
        if (!$requiredRank) {
            return Response::deny('Required rank does not exist.');
        }
        
        $userRank = $user->getCurrentRankAttribute();
        
        if (!$userRank) {
            return Response::deny('User does not have a rank.');
        }
        
        // Check if user's current rank meets or exceeds the required rank
        if ($userRank->points_required >= $requiredRank->points_required) {
            return Response::allow('User has the required rank or higher.');
        }
        
        return Response::deny('User does not have the required rank or higher.');
    }
}
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class UserPolicyService
{
    public function meetsRankRequirement(User $user, Product $product): bool
    {
        if (empty($product->required_rank_key)) {
            return true; // If no rank required, allow redemption
        }
        
        $userRank = $user->current_rank_key;
        $productRequiredRank = $product->required_rank_key;
        
        // Get all ranks ordered by points required
        $allRanks = \App\Models\Rank::orderBy('points_required')->get();
        
        // Find the position of user and required ranks
        $userRankIndex = $allRanks->search(function ($rank) use ($userRank) {
            return $rank->key === $userRank;
        });
        
        $requiredRankIndex = $allRanks->search(function ($rank) use ($productRequiredRank) {
            return $rank->key === $productRequiredRank;
        });
        
        // If either rank is not found, deny access
        if ($userRankIndex === false || $requiredRankIndex === false) {
            return false;
        }
        
        // User meets rank requirement if their rank is at or above the required rank
        return $userRankIndex >= $requiredRankIndex;
    }
    
    public function canAffordRedemption(User $user, Product $product): bool
    {
        return $user->points_balance >= $product->points_cost;
    }
}
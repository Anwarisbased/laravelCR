<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RankController extends Controller
{
    /**
     * Get all available ranks
     */
    public function getRanks()
    {
        $rankModels = Cache::remember('all_ranks', 3600, function () {
            return Rank::where('points_required', '>=', 0)->orderBy('points_required')->get();
        });

        $ranksData = $rankModels->map(function ($rankModel) {
            return \App\Data\RankData::fromModel($rankModel);
        });

        return response()->json([
            'ranks' => $ranksData
        ]);
    }

    /**
     * Get specific user's current rank
     */
    public function getUserRank($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $rankData = $this->calculateUserRankData($user);

        return response()->json($rankData);
    }

    /**
     * Get authenticated user's current rank
     */
    public function getMyRank(Request $request)
    {
        \Log::info('getMyRank called', [
            'user_from_request' => $request->user() ? $request->user()->id : null,
            'has_user' => $request->user() !== null,
        ]);
        
        $user = $request->user();
        
        if (!$user) {
            \Log::warning('User not authenticated in getMyRank');
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }
        
        try {
            $rankData = $this->calculateUserRankData($user);

            return response()->json($rankData);
        } catch (\Exception $e) {
            \Log::error('Error in getMyRank', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while calculating your rank data'
            ], 500);
        }
    }

    /**
     * Calculate user rank data including progress
     */
    private function calculateUserRankData(User $user)
    {
        \Log::info('calculateUserRankData called', [
            'user_id' => $user->id ?? null,
            'user_exists' => $user->exists ?? false,
        ]);
        
        if (!$user || !$user->exists) {
            \Log::error('Invalid user passed to calculateUserRankData', [
                'user' => $user
            ]);
            throw new \Exception('Invalid user provided to rank calculation');
        }
        
        $allRanks = Rank::where('points_required', '>=', 0)->orderBy('points_required')->get();
        $lifetimePoints = $user->lifetime_points ?? 0;

        // Find current rank
        $currentRank = null;
        $nextRank = null;
        
        foreach ($allRanks as $rank) {
            if ($rank->points_required <= $lifetimePoints) {
                $currentRank = $rank;
            } elseif ($currentRank && !$nextRank) {
                $nextRank = $rank;
                break;
            }
        }

        // If no current rank found, use the lowest rank
        if (!$currentRank && $allRanks->isNotEmpty()) {
            $currentRank = $allRanks->first();
        }

        // Calculate progress
        $progressPercent = 0;
        $pointsToNext = 0;
        
        if ($currentRank && $nextRank) {
            $pointsNeeded = $nextRank->points_required - $lifetimePoints;
            $pointsRange = $nextRank->points_required - $currentRank->points_required;
            $progressPercent = $pointsRange > 0 ? (($pointsRange - $pointsNeeded) / $pointsRange) * 100 : 100;
            $pointsToNext = max(0, $pointsNeeded);
        } elseif ($currentRank && !$nextRank) {
            // Max rank achieved
            $progressPercent = 100;
        }

        return [
            'current_rank' => $currentRank ? \App\Data\RankData::fromModel($currentRank) : null,
            'next_rank' => $nextRank ? \App\Data\RankData::fromModel($nextRank) : null,
            'lifetime_points' => $lifetimePoints,
            'progress_percent' => round($progressPercent, 2),
            'points_to_next' => $pointsToNext,
        ];
    }
}
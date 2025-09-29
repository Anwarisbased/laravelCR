<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AchievementController extends Controller
{
    protected $achievementProgressService;

    public function __construct(AchievementProgressService $achievementProgressService)
    {
        $this->achievementProgressService = $achievementProgressService;
    }

    public function index(Request $request)
    {
        $achievements = Achievement::active()->orderBy('sort_order')->get();
        return response()->json($achievements);
    }

    public function userAchievements(Request $request)
    {
        $user = $request->user();
        $userAchievements = $user->unlockedAchievements;
        
        return response()->json($userAchievements);
    }

    public function userLockedAchievements(Request $request)
    {
        $user = $request->user();
        
        // Get unlocked achievement keys
        $unlockedAchievementIds = $user->unlockedAchievements()->pluck('achievements.achievement_key')->toArray();
        
        // Get all active achievements that are not unlocked
        $lockedAchievements = Achievement::active()
            ->whereNotIn('achievement_key', $unlockedAchievementIds)
            ->orderBy('sort_order')
            ->get();
        
        return response()->json($lockedAchievements);
    }

    public function userProgress(Request $request)
    {
        $user = $request->user();
        
        $progress = $this->achievementProgressService->getUserOverallProgress($user);
        
        return response()->json($progress);
    }
}
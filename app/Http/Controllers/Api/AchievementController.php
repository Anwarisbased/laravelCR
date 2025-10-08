<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Data\AchievementData;
use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementProgressService;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    protected $achievementProgressService;

    public function __construct(AchievementProgressService $achievementProgressService)
    {
        $this->achievementProgressService = $achievementProgressService;
    }

    public function index(Request $request)
    {
        $achievementModels = Achievement::active()->orderBy('sort_order')->get();
        
        $achievementsData = $achievementModels->map(function ($achievementModel) {
            return AchievementData::fromModel($achievementModel);
        });
        
        return response()->json([
            'achievements' => $achievementsData
        ]);
    }

    public function userAchievements(Request $request)
    {
        $user = $request->user();
        $userAchievementModels = $user->unlockedAchievements;
        
        $userAchievementsData = $userAchievementModels->map(function ($achievementModel) {
            return AchievementData::fromModel($achievementModel);
        });
        
        return response()->json([
            'achievements' => $userAchievementsData
        ]);
    }

    public function userLockedAchievements(Request $request)
    {
        $user = $request->user();
        
        // Get unlocked achievement keys
        $unlockedAchievementIds = $user->unlockedAchievements()->pluck('achievements.achievement_key')->toArray();
        
        // Get all active achievements that are not unlocked
        $lockedAchievementModels = Achievement::active()
            ->whereNotIn('achievement_key', $unlockedAchievementIds)
            ->orderBy('sort_order')
            ->get();
            
        $lockedAchievementsData = $lockedAchievementModels->map(function ($achievementModel) {
            return AchievementData::fromModel($achievementModel);
        });
        
        return response()->json([
            'achievements' => $lockedAchievementsData
        ]);
    }

    public function userProgress(Request $request)
    {
        $user = $request->user();
        
        $progress = $this->achievementProgressService->getUserOverallProgress($user);
        
        return response()->json($progress);
    }
}
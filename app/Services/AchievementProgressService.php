<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;

class AchievementProgressService
{
    public function getUserAchievementProgress(User $user, Achievement $achievement): array
    {
        $currentCount = $this->getCurrentTriggerCount($user, $achievement);
        $requiredCount = $achievement->trigger_count;
        
        return [
            'achievement' => $achievement,
            'current_count' => $currentCount,
            'required_count' => $requiredCount,
            'progress_percent' => min(100, ($currentCount / $requiredCount) * 100),
            'is_unlocked' => $user->unlockedAchievements->contains('achievement_key', $achievement->achievement_key),
        ];
    }
    
    public function getCurrentTriggerCount(User $user, Achievement $achievement): int
    {
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', $achievement->achievement_key)
            ->first();
            
        return $userAchievement ? $userAchievement->trigger_count : 0;
    }
    
    public function getUserOverallProgress(User $user): array
    {
        $totalAchievements = Achievement::active()->count();
        $unlockedAchievements = $user->unlockedAchievements()->count();
        
        return [
            'total_achievements' => $totalAchievements,
            'unlocked_achievements' => $unlockedAchievements,
            'completion_percentage' => $totalAchievements > 0 ? 
                ($unlockedAchievements / $totalAchievements) * 100 : 0,
        ];
    }
}
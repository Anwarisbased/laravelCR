<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Notifications\AchievementUnlockedNotification;
use App\Jobs\GrantAchievementReward;
use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;

class AchievementUnlockService
{
    protected $achievementService;
    
    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }
    
    public function unlockAchievement(User $user, Achievement $achievement): void
    {
        // Check if already unlocked
        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', $achievement->achievement_key)
            ->first();
            
        if ($existing && $existing->unlocked_at) {
            return; // Already unlocked, prevent duplicates
        }
        
        if ($existing) {
            // Update existing record to mark as unlocked
            $existing->update([
                'unlocked_at' => now(),
                'trigger_count' => $achievement->trigger_count,
            ]);
        } else {
            // Create user achievement record
            UserAchievement::create([
                'user_id' => $user->id,
                'achievement_key' => $achievement->achievement_key,
                'unlocked_at' => now(),
                'trigger_count' => $achievement->trigger_count,
            ]);
        }
        
        // Grant points reward if applicable
        if ($achievement->points_reward > 0) {
            GrantAchievementReward::dispatch(
                $user->id,
                $achievement->points_reward,
                "Achievement unlocked: {$achievement->title}"
            );
        }
        
        // Send notification to user
        $user->notify(new AchievementUnlockedNotification($achievement));
        
        // Fire event for other systems to react
        event(new AchievementUnlocked($user, $achievement));
    }
}
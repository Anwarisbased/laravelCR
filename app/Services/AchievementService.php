<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Notifications\AchievementProgressNotification;
use App\Jobs\UnlockAchievement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AchievementService
{
    protected $rulesEngineService;
    protected $achievementCache;
    
    public function __construct(RulesEngineService $rulesEngineService)
    {
        $this->rulesEngineService = $rulesEngineService;
        $this->achievementCache = [];
    }
    
    public function getAchievementsByTriggerEvent(string $event): Collection
    {
        if (!isset($this->achievementCache[$event])) {
            $this->achievementCache[$event] = Cache::remember(
                "achievements_trigger_{$event}", 
                3600, 
                function () use ($event) {
                    return Achievement::active()
                        ->where('trigger_event', $event)
                        ->get();
                }
            );
        }
        
        return $this->achievementCache[$event];
    }
    
    public function evaluateAchievements(User $user, string $event, array $context = []): void
    {
        $achievements = $this->getAchievementsByTriggerEvent($event);
        $unlockedKeys = $user->unlockedAchievements->pluck('achievement_key')->toArray();
        
        foreach ($achievements as $achievement) {
            // Skip already unlocked achievements
            if (in_array($achievement->achievement_key, $unlockedKeys)) {
                continue;
            }
            
            // Check if conditions are met
            if ($achievement->meetsConditions($context)) {
                // Check trigger count
                $triggerCount = $this->getTriggerCount($user, $achievement, $event);
                
                $newTriggerCount = $triggerCount + 1;
                if ($newTriggerCount >= $achievement->trigger_count) {
                    // Dispatch job to unlock achievement
                    UnlockAchievement::dispatch($user, $achievement);
                } else {
                    // Update trigger count with the incremented value
                    $this->updateTriggerCount($user, $achievement, $newTriggerCount);
                    
                    // Send progress notification
                    if ($newTriggerCount === $achievement->trigger_count - 1) {
                        // One more to go
                        $user->notify(new AchievementProgressNotification($achievement));
                    }
                }
            }
        }
    }
    
    protected function getTriggerCount(User $user, Achievement $achievement, string $event): int
    {
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', $achievement->achievement_key)
            ->first();
            
        return $userAchievement ? $userAchievement->trigger_count : 0;
    }
    
    protected function updateTriggerCount(User $user, Achievement $achievement, int $count): void
    {
        DB::transaction(function () use ($user, $achievement, $count) {
            UserAchievement::where('user_id', $user->id)
                ->where('achievement_key', $achievement->achievement_key)
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'achievement_key' => $achievement->achievement_key,
                    ],
                    [
                        'trigger_count' => $count,
                        'unlocked_at' => null
                    ]
                )
                ->update(['trigger_count' => $count]);
        });
    }
}
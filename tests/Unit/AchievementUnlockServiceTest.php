<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\AchievementUnlockService;
use App\Services\AchievementService;
use App\Services\RulesEngineService;
use App\Notifications\AchievementUnlockedNotification;
use App\Jobs\GrantAchievementReward;
use App\Events\AchievementUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class AchievementUnlockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementUnlockService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real dependencies instead of mocking
        $rulesEngineService = new RulesEngineService();
        $achievementService = new AchievementService($rulesEngineService);
        $this->achievementUnlockService = new AchievementUnlockService($achievementService);
    }

    public function test_unlock_achievement_creates_user_achievement_record()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_unlock',
            'title' => 'Test Unlock',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
        ]);

        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'test_unlock',
        ]);
    }

    public function test_unlock_achievement_with_points_reward_dispatches_reward_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_reward',
            'title' => 'Test Reward',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 150,
            'is_active' => true,
        ]);

        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        Queue::assertPushed(GrantAchievementReward::class, function ($job) use ($user, $achievement) {
            return $job->userId === $user->id 
                && $job->pointsReward === $achievement->points_reward
                && $job->reason === "Achievement unlocked: {$achievement->title}";
        });
    }

    public function test_unlock_achievement_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_notification',
            'title' => 'Test Notification',
            'description' => 'Test notification achievement',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0, // No points for this test
            'is_active' => true,
        ]);

        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        Notification::assertSentTo($user, AchievementUnlockedNotification::class, function ($notification) use ($user, $achievement) {
            return $notification->toArray($user)['achievement_key'] === $achievement->achievement_key;
        });
    }

    public function test_unlock_achievement_fires_event()
    {
        Event::fake();

        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_event',
            'title' => 'Test Event',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0,
            'is_active' => true,
        ]);

        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user, $achievement) {
            return $event->user->id === $user->id && $event->achievement->achievement_key === $achievement->achievement_key;
        });
    }

    public function test_unlock_achievement_prevents_duplicates()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_duplicate',
            'title' => 'Test Duplicate',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0,
            'is_active' => true,
        ]);

        // Unlock the achievement for the first time
        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        // Try to unlock the same achievement again
        $this->achievementUnlockService->unlockAchievement($user, $achievement);

        // Should still only have one record
        $this->assertCount(1, UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'test_duplicate')
            ->get());
    }
}
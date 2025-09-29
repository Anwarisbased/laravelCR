<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\AchievementProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AchievementProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementProgressService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->achievementProgressService = new AchievementProgressService();
    }

    public function test_get_user_achievement_progress()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_progress',
            'title' => 'Test Progress',
            'trigger_event' => 'test_event',
            'trigger_count' => 10,
            'is_active' => true,
        ]);

        // Create a user achievement with partial progress
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'test_progress',
            'trigger_count' => 5,
            'unlocked_at' => null, // Not unlocked yet
        ]);

        $progress = $this->achievementProgressService->getUserAchievementProgress($user, $achievement);

        $this->assertEquals($achievement, $progress['achievement']);
        $this->assertEquals(5, $progress['current_count']);
        $this->assertEquals(10, $progress['required_count']);
        $this->assertEquals(50.0, $progress['progress_percent']); // 5/10 = 50%
        $this->assertFalse($progress['is_unlocked']);
    }

    public function test_get_user_achievement_progress_unlocked()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_unlocked',
            'title' => 'Test Unlocked',
            'trigger_event' => 'test_event',
            'trigger_count' => 10,
            'is_active' => true,
        ]);

        // Create a user achievement that's unlocked
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'test_unlocked',
            'unlocked_at' => now(),
            'trigger_count' => 10,
        ]);

        $progress = $this->achievementProgressService->getUserAchievementProgress($user, $achievement);

        $this->assertTrue($progress['is_unlocked']);
        $this->assertEquals(100.0, $progress['progress_percent']);
    }

    public function test_get_current_trigger_count()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_count',
            'title' => 'Test Count',
            'trigger_event' => 'test_event',
            'trigger_count' => 5,
            'is_active' => true,
        ]);

        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'test_count',
            'trigger_count' => 3,
        ]);

        $count = $this->achievementProgressService->getCurrentTriggerCount($user, $achievement);

        $this->assertEquals(3, $count);
    }

    public function test_get_current_trigger_count_not_found()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_count',
            'title' => 'Test Count',
            'trigger_event' => 'test_event',
            'trigger_count' => 5,
            'is_active' => true,
        ]);

        $count = $this->achievementProgressService->getCurrentTriggerCount($user, $achievement);

        $this->assertEquals(0, $count);
    }

    public function test_get_user_overall_progress()
    {
        $user = User::factory()->create();
        
        // Create 3 active achievements
        Achievement::create([
            'achievement_key' => 'ach_1',
            'title' => 'Achievement 1',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);
        
        Achievement::create([
            'achievement_key' => 'ach_2',
            'title' => 'Achievement 2',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);
        
        Achievement::create([
            'achievement_key' => 'ach_3',
            'title' => 'Achievement 3',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        // User has unlocked 2 of 3
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'ach_1',
            'unlocked_at' => now(),
        ]);

        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'ach_2',
            'unlocked_at' => now(),
        ]);

        $progress = $this->achievementProgressService->getUserOverallProgress($user);

        $this->assertEquals(3, $progress['total_achievements']);
        $this->assertEquals(2, $progress['unlocked_achievements']);
        $this->assertEquals(66.66666666666666, $progress['completion_percentage']);
    }

    public function test_get_user_overall_progress_no_achievements()
    {
        $user = User::factory()->create();

        $progress = $this->achievementProgressService->getUserOverallProgress($user);

        $this->assertEquals(0, $progress['total_achievements']);
        $this->assertEquals(0, $progress['unlocked_achievements']);
        $this->assertEquals(0, $progress['completion_percentage']);
    }
}
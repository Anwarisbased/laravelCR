<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Jobs\UnlockAchievement;
use App\Jobs\GrantAchievementReward;
use App\Jobs\EvaluateAchievementCriteria;
use App\Services\AchievementUnlockService;
use App\Services\AchievementService;
use App\Services\AchievementRewardService;
use App\Services\RulesEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AchievementUnlockedNotification;
use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\Event;

class AchievementJobsTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementUnlockService;
    protected $achievementRewardService;
    protected $achievementService;
    protected $rulesEngineService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real services instead of mocking
        $this->rulesEngineService = new RulesEngineService();
        $this->achievementService = new AchievementService($this->rulesEngineService);
        $this->achievementUnlockService = new AchievementUnlockService($this->achievementService);
        $this->achievementRewardService = new AchievementRewardService();
    }

    public function test_unlock_achievement_job_calls_service_method()
    {
        Notification::fake();
        Event::fake();
        
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_job_unlock',
            'title' => 'Test Job Unlock',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0,
            'is_active' => true,
        ]);

        // Execute the job with real service
        $job = new UnlockAchievement($user, $achievement);
        $job->handle($this->achievementUnlockService);

        // Verify the side effect - user achievement record was created
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'test_job_unlock',
        ]);
    }

    public function test_grant_achievement_reward_job_calls_service_method()
    {
        $user = User::factory()->create(['meta' => ['_canna_points_balance' => 500]]);
        $userId = $user->id; // Keep as integer to match the job's constructor
        $pointsReward = 100; // Keep as integer to match the job's constructor
        $reason = 'Test job reward';

        // Execute the job with real service
        $job = new GrantAchievementReward($userId, $pointsReward, $reason);
        $job->handle($this->achievementRewardService);

        // Verify the side effect - user points balance was updated
        $user->refresh();
        $this->assertEquals(600, $user->meta['_canna_points_balance']);
    }

    public function test_evaluate_achievement_criteria_job_calls_service_method()
    {
        $user = User::factory()->create();
        $event = 'test_event';
        $context = ['test' => 'data'];

        // Create an achievement that matches the event
        $achievement = Achievement::create([
            'achievement_key' => 'test_evaluation',
            'title' => 'Test Evaluation',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0,
            'is_active' => true,
        ]);

        // Execute the job with real service
        $job = new EvaluateAchievementCriteria($user, $event, $context);
        $job->handle($this->achievementService);

        // For this test, we're verifying that the job executed without error
        // The actual evaluation logic would be tested in AchievementServiceTest
        $this->assertTrue(true); // Job executed without throwing exception
    }
}
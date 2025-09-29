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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class AchievementJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlock_achievement_job_calls_service_method()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_job_unlock',
            'title' => 'Test Job Unlock',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 0,
            'is_active' => true,
        ]);

        $serviceMock = $this->mock(AchievementUnlockService::class);
        $serviceMock->shouldReceive('unlockAchievement')
            ->with($user, $achievement)
            ->once();

        $job = new UnlockAchievement($user, $achievement);
        $job->handle($serviceMock);
    }

    public function test_grant_achievement_reward_job_calls_service_method()
    {
        $userId = 1;
        $pointsReward = 100;
        $reason = 'Test job reward';

        $serviceMock = $this->mock(AchievementRewardService::class);
        $serviceMock->shouldReceive('grantReward')
            ->with($userId, $pointsReward, $reason)
            ->once();

        $job = new GrantAchievementReward($userId, $pointsReward, $reason);
        $job->handle($serviceMock);
    }

    public function test_evaluate_achievement_criteria_job_calls_service_method()
    {
        $user = User::factory()->create();
        $event = 'test_event';
        $context = ['test' => 'data'];

        $serviceMock = $this->mock(AchievementService::class);
        $serviceMock->shouldReceive('evaluateAchievements')
            ->with($user, $event, $context)
            ->once();

        $job = new EvaluateAchievementCriteria($user, $event, $context);
        $job->handle($serviceMock);
    }
}
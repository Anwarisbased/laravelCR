<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use App\Services\RulesEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementService;
    protected $rulesEngineService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rulesEngineService = Mockery::mock(RulesEngineService::class)->makePartial();
        $this->achievementService = new AchievementService($this->rulesEngineService);
    }

    public function test_get_achievements_by_trigger_event()
    {
        Achievement::create([
            'achievement_key' => 'achievement_a',
            'title' => 'Achievement A',
            'trigger_event' => 'event_a',
            'is_active' => true,
        ]);

        Achievement::create([
            'achievement_key' => 'achievement_b',
            'title' => 'Achievement B',
            'trigger_event' => 'event_a',
            'is_active' => true,
        ]);

        Achievement::create([
            'achievement_key' => 'achievement_c',
            'title' => 'Achievement C',
            'trigger_event' => 'event_b',
            'is_active' => true,
        ]);

        $achievements = $this->achievementService->getAchievementsByTriggerEvent('event_a');

        $this->assertCount(2, $achievements);
        $this->assertEquals(['achievement_a', 'achievement_b'], $achievements->pluck('achievement_key')->toArray());
    }

    public function test_evaluate_achievements_with_met_conditions()
    {
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
        ]);

        // Mock that conditions are met
        $this->rulesEngineService
            ->shouldReceive('evaluate')
            ->with($achievement->conditions, ['test' => 'data'])
            ->andReturn(true);

        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'test_achievement',
        ]);

        // Check that a job was dispatched to unlock the achievement
        // Note: In the real implementation, we would check for job dispatches
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'test_achievement')
            ->first();
        
        $this->assertNotNull($userAchievement);
    }

    public function test_evaluate_achievements_with_unmet_conditions()
    {
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
        ]);

        // Mock that conditions are NOT met
        $this->rulesEngineService
            ->shouldReceive('evaluate')
            ->with($achievement->conditions, ['test' => 'data'])
            ->andReturn(false);

        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'test_achievement',
        ]);
    }

    public function test_evaluate_achievements_skips_already_unlocked()
    {
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'already_unlocked',
            'title' => 'Already Unlocked',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
        ]);

        // Create the user achievement to simulate already unlocked
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'already_unlocked',
            'unlocked_at' => now(),
        ]);

        // The rules engine should NOT be called since the achievement is already unlocked
        $this->rulesEngineService
            ->shouldNotReceive('evaluate');

        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        // Verify there's still only one record and no additional ones were created
        $this->assertCount(1, UserAchievement::where('user_id', $user->id)->get());
    }
}
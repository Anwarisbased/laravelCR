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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the real RulesEngineService instead of mocking it
        $this->achievementService = new AchievementService(new RulesEngineService());
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
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
            'conditions' => [], // Empty conditions should pass
        ]);

        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        // Verify that the UnlockAchievement job was dispatched
        Queue::assertPushed(\App\Jobs\UnlockAchievement::class, function ($job) use ($user, $achievement) {
            return $job->user->id === $user->id && $job->achievement->achievement_key === $achievement->achievement_key;
        });
    }

    public function test_evaluate_achievements_with_unmet_conditions()
    {
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
            'conditions' => [
                ['field' => 'user.level', 'operator' => '>', 'value' => 10],
            ],
        ]);

        // This should not match since user level is not in the context
        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'test_achievement',
        ]);
    }

    public function test_evaluate_achievements_skips_already_unlocked()
    {
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create();

        $achievement = Achievement::create([
            'achievement_key' => 'already_unlocked',
            'title' => 'Already Unlocked',
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'points_reward' => 100,
            'is_active' => true,
            'conditions' => [], // Empty conditions should pass
        ]);

        // Create the user achievement to simulate already unlocked
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => 'already_unlocked',
            'unlocked_at' => now(),
        ]);

        $this->achievementService->evaluateAchievements($user, 'test_event', ['test' => 'data']);

        // Verify there's still only one record and no additional ones were created
        $this->assertCount(1, UserAchievement::where('user_id', $user->id)->get());
    }
}
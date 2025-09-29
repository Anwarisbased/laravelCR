<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use App\Jobs\EvaluateAchievementCriteria;
use App\Jobs\UnlockAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

class GamificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_complete_achievement_workflow()
    {
        // Create a test user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create an achievement that requires 3 scans
        $achievement = Achievement::create([
            'achievement_key' => 'three_scans',
            'title' => 'Three Scans',
            'description' => 'Scan 3 products',
            'points_reward' => 50,
            'trigger_event' => 'product_scanned',
            'trigger_count' => 3,
            'is_active' => true,
        ]);

        // Evaluate achievement criteria with context that should eventually unlock the achievement
        $service = app(AchievementService::class);
        
        // Simulate first scan
        $service->evaluateAchievements($user, 'product_scanned', [
            'product' => ['id' => 1],
            'user' => ['id' => $user->id]
        ]);
        
        // Check that the achievement is not unlocked yet (unlocked_at is null)
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'three_scans',
        ]);
        
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'three_scans')
            ->first();
        
        $this->assertNull($userAchievement->unlocked_at); // Achievement not unlocked yet
        $this->assertEquals(1, $userAchievement->trigger_count); // But progress is tracked

        // Simulate second scan
        $service->evaluateAchievements($user, 'product_scanned', [
            'product' => ['id' => 2],
            'user' => ['id' => $user->id]
        ]);

        // Still not unlocked but progress is tracked
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'three_scans',
        ]);
        
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'three_scans')
            ->first();
        
        $this->assertNull($userAchievement->unlocked_at); // Achievement still not unlocked
        $this->assertEquals(2, $userAchievement->trigger_count); // But progress is tracked

        // Simulate third scan - this should unlock the achievement
        $service->evaluateAchievements($user, 'product_scanned', [
            'product' => ['id' => 3],
            'user' => ['id' => $user->id]
        ]);

        // Now the achievement should be unlocked
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'three_scans',
        ]);
        
        // Check that the achievement was actually unlocked (unlocked_at is set)
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'three_scans')
            ->first();
        
        $this->assertNotNull($userAchievement->unlocked_at); // Achievement should now be unlocked
    }

    public function test_achievement_with_conditions_evaluation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create an achievement with conditions (e.g., user must be VIP)
        $achievement = Achievement::create([
            'achievement_key' => 'vip_welcome',
            'title' => 'VIP Welcome',
            'description' => 'Welcome VIP users',
            'points_reward' => 100,
            'trigger_event' => 'user_registered',
            'trigger_count' => 1,
            'conditions' => [
                ['field' => 'user.is_vip', 'operator' => 'is', 'value' => true]
            ],
            'is_active' => true,
        ]);

        $service = app(AchievementService::class);
        
        // Evaluate with context that meets conditions
        $service->evaluateAchievements($user, 'user_registered', [
            'user' => [
                'id' => $user->id,
                'is_vip' => true
            ]
        ]);

        // The achievement should be unlocked
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'vip_welcome',
        ]);
    }

    public function test_achievement_evaluation_with_unmet_conditions()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create an achievement with conditions (e.g., user must be VIP)
        $achievement = Achievement::create([
            'achievement_key' => 'vip_only',
            'title' => 'VIP Only',
            'description' => 'For VIP users only',
            'points_reward' => 100,
            'trigger_event' => 'user_registered',
            'trigger_count' => 1,
            'conditions' => [
                ['field' => 'user.is_vip', 'operator' => 'is', 'value' => true]
            ],
            'is_active' => true,
        ]);

        $service = app(AchievementService::class);
        
        // Evaluate with context that does NOT meet conditions
        $service->evaluateAchievements($user, 'user_registered', [
            'user' => [
                'id' => $user->id,
                'is_vip' => false  // This should prevent unlocking
            ]
        ]);

        // The achievement should NOT be unlocked
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'vip_only',
        ]);
    }

    public function test_achievement_prevents_duplicates()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $achievement = Achievement::create([
            'achievement_key' => 'no_duplicates',
            'title' => 'No Duplicates',
            'description' => 'Achievement that should not duplicate',
            'points_reward' => 50,
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'is_active' => true,
        ]);

        $service = app(AchievementService::class);
        
        // Evaluate the same achievement multiple times
        for ($i = 0; $i < 3; $i++) {
            $service->evaluateAchievements($user, 'test_event', [
                'user' => ['id' => $user->id]
            ]);
        }

        // Should only have one record even after multiple evaluations
        $userAchievements = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', 'no_duplicates')
            ->get();
            
        $this->assertCount(1, $userAchievements);
    }
}
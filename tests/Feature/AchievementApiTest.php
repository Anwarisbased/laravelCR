<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AchievementApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    public function test_get_user_achievements()
    {
        // Create an achievement and unlock it for the user
        $achievement = Achievement::create([
            'achievement_key' => 'test_user_ach',
            'title' => 'Test User Achievement',
            'description' => 'Description',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        UserAchievement::create([
            'user_id' => $this->user->id,
            'achievement_key' => 'test_user_ach',
            'unlocked_at' => now(),
        ]);

        $response = $this->getJson('/api/rewards/v2/users/me/achievements');

        $response->assertStatus(200)
                 ->assertJson([
                     'achievements' => [
                         [
                             'achievement_key' => 'test_user_ach',
                             'title' => 'Test User Achievement',
                         ]
                     ]
                 ]);
        
        // Verify there is 1 achievement in the response
        $this->assertCount(1, $response->json('achievements'));
    }

    public function test_get_user_locked_achievements()
    {
        // Create an unlocked achievement
        $unlockedAchievement = Achievement::create([
            'achievement_key' => 'unlocked_ach',
            'title' => 'Unlocked Achievement',
            'description' => 'Description',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        UserAchievement::create([
            'user_id' => $this->user->id,
            'achievement_key' => 'unlocked_ach',
            'unlocked_at' => now(),
        ]);

        // Create a locked achievement
        $lockedAchievement = Achievement::create([
            'achievement_key' => 'locked_ach',
            'title' => 'Locked Achievement',
            'description' => 'Description',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/rewards/v2/users/me/achievements/locked');

        $response->assertStatus(200);
        
        // Verify the locked achievement is in the response
        $response->assertJsonFragment([
            'achievement_key' => 'locked_ach',
            'title' => 'Locked Achievement',
        ]);
    }

    public function test_get_user_progress()
    {
        // Create exactly 2 achievements
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

        // User has unlocked one achievement
        UserAchievement::create([
            'user_id' => $this->user->id,
            'achievement_key' => 'ach_1',
            'unlocked_at' => now(),
        ]);

        $response = $this->getJson('/api/rewards/v2/users/me/achievements/progress');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        // Calculate expected values based on only the achievements we created
        $totalAchievements = Achievement::where('is_active', true)->count();
        $unlockedAchievements = UserAchievement::where('user_id', $this->user->id)->count();
        $expectedPercentage = $totalAchievements > 0 ? ($unlockedAchievements / $totalAchievements) * 100 : 0;
        
        $response->assertJson([
            'total_achievements' => $totalAchievements,
            'unlocked_achievements' => $unlockedAchievements,
            'completion_percentage' => $expectedPercentage,
        ]);
    }

    public function test_get_all_achievements()
    {
        Achievement::create([
            'achievement_key' => 'ach_1',
            'title' => 'Achievement 1',
            'description' => 'Description 1',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        Achievement::create([
            'achievement_key' => 'ach_2',
            'title' => 'Achievement 2',
            'description' => 'Description 2',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        // Create an inactive achievement that should not appear
        Achievement::create([
            'achievement_key' => 'ach_inactive',
            'title' => 'Inactive Achievement',
            'description' => 'Inactive Description',
            'trigger_event' => 'test_event',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/rewards/v2/achievements');

        $response->assertStatus(200);
        
        // Verify the response contains only active achievements
        $responseData = $response->json();
        $activeAchievementCount = Achievement::where('is_active', true)->count();
        
        $this->assertCount($activeAchievementCount, $responseData['achievements']);
        
        // Verify that the expected achievements are present
        $response->assertJsonFragment([
            'achievement_key' => 'ach_1',
            'title' => 'Achievement 1',
        ]);
        
        $response->assertJsonFragment([
            'achievement_key' => 'ach_2',
            'title' => 'Achievement 2',
        ]);
        
        // Verify inactive achievement is not in the response
        $this->assertNotContains('ach_inactive', array_column($responseData['achievements'], 'achievement_key'));
    }

    
}
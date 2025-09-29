<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\UserAchievement;
use App\Models\User;
use App\Models\Achievement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_achievement_model_fillable_attributes()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'trigger_event' => 'test_event',
        ]);

        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => $achievement->achievement_key,
            'unlocked_at' => now(),
            'trigger_count' => 10,
        ]);

        $this->assertEquals($user->id, $userAchievement->user_id);
        $this->assertEquals($achievement->achievement_key, $userAchievement->achievement_key);
        $this->assertNotNull($userAchievement->unlocked_at);
        $this->assertEquals(10, $userAchievement->trigger_count);
    }

    public function test_user_achievement_model_casts()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_casts',
            'title' => 'Test Casts',
            'trigger_event' => 'test_event',
        ]);

        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => $achievement->achievement_key,
            'unlocked_at' => '2023-01-01 10:00:00',
            'trigger_count' => '15',
        ]);

        $this->assertInstanceOf(\DateTime::class, $userAchievement->unlocked_at);
        $this->assertIsInt($userAchievement->trigger_count);
    }

    public function test_user_achievement_belongs_to_user_relationship()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_relationship',
            'title' => 'Test Relationship',
            'trigger_event' => 'test_event',
        ]);

        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => $achievement->achievement_key,
            'unlocked_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $userAchievement->user);
        $this->assertEquals($user->id, $userAchievement->user->id);
    }

    public function test_user_achievement_belongs_to_achievement_relationship()
    {
        $user = User::factory()->create();
        $achievement = Achievement::create([
            'achievement_key' => 'test_relationship',
            'title' => 'Test Relationship',
            'trigger_event' => 'test_event',
        ]);

        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => $achievement->achievement_key,
            'unlocked_at' => now(),
        ]);

        $this->assertInstanceOf(Achievement::class, $userAchievement->achievement);
        $this->assertEquals($achievement->achievement_key, $userAchievement->achievement->achievement_key);
    }
}
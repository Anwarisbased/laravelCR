<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\AchievementRewardService;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class AchievementRewardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $achievementRewardService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->achievementRewardService = new AchievementRewardService();
    }

    public function test_grant_reward_creates_and_handles_command()
    {
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create(['meta' => ['_canna_points_balance' => 500]]);
        $userId = UserId::fromInt($user->id);
        $pointsReward = Points::fromInt(100);
        $reason = 'Test reward';

        // Use the real services
        $this->achievementRewardService->grantReward($userId, $pointsReward, $reason);
        
        // Verify the user's points balance was updated
        $user->refresh();
        $this->assertEquals(600, $user->meta['_canna_points_balance']);
    }

    public function test_grant_reward_with_zero_points_does_not_create_command()
    {
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create(['meta' => ['_canna_points_balance' => 500]]);
        $initialPoints = $user->meta['_canna_points_balance'];
        $userId = UserId::fromInt($user->id);
        $pointsReward = Points::fromInt(0);

        // Use the real services
        $this->achievementRewardService->grantReward($userId, $pointsReward, 'Test zero reward');
        
        // Check that the points were not changed
        $user->refresh();
        $this->assertEquals($initialPoints, $user->meta['_canna_points_balance']);
    }

    public function test_grant_reward_with_negative_points_does_not_create_command()
    {
        Event::fake();
        Queue::fake();
        
        $user = User::factory()->create(['meta' => ['_canna_points_balance' => 500]]);
        $initialPoints = $user->meta['_canna_points_balance'];
        $userId = UserId::fromInt($user->id);
        
        // This should throw an exception because Points value object validates against negative values
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Points cannot be negative. Received: -50');
        
        $pointsReward = Points::fromInt(-50);

        // Use the real services
        $this->achievementRewardService->grantReward($userId, $pointsReward, 'Test negative reward');
    }
}
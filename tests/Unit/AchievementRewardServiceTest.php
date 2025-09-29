<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\AchievementRewardService;
use App\Services\EconomyService;
use App\Commands\GrantPointsCommand;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AchievementRewardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $economyService;
    protected $achievementRewardService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock instance of EconomyService
        $mockEconomyService = Mockery::mock(EconomyService::class)->makePartial();
        
        $this->app->instance(EconomyService::class, $mockEconomyService);
        
        $this->economyService = $mockEconomyService;
        $this->achievementRewardService = new AchievementRewardService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_grant_reward_creates_and_handles_command()
    {
        $userId = 1;
        $pointsReward = 100;
        $reason = 'Test reward';

        $command = new GrantPointsCommand(
            UserId::fromInt($userId),
            Points::fromInt($pointsReward),
            $reason
        );

        $this->economyService
            ->shouldReceive('handle')
            ->with($command)
            ->once();

        $this->achievementRewardService->grantReward($userId, $pointsReward, $reason);
    }

    public function test_grant_reward_with_zero_points_does_not_create_command()
    {
        $this->economyService
            ->shouldNotReceive('handle');

        $this->achievementRewardService->grantReward(1, 0, 'Test zero reward');
    }

    public function test_grant_reward_with_negative_points_does_not_create_command()
    {
        $this->economyService
            ->shouldNotReceive('handle');

        $this->achievementRewardService->grantReward(1, -50, 'Test negative reward');
    }
}
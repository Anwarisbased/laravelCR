<?php

namespace App\Services;

use App\Models\User;
use App\Commands\GrantPointsCommand;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\Points;

class AchievementRewardService
{
    protected $economyService;
    
    public function __construct()
    {
        $this->economyService = app(\App\Services\EconomyService::class);
    }
    
    public function grantReward(int $userId, int $pointsReward, string $reason): void
    {
        if ($pointsReward <= 0) {
            return; // No reward to grant
        }
        
        $command = new GrantPointsCommand(
            UserId::fromInt($userId),
            Points::fromInt($pointsReward),
            $reason
        );
        
        $this->economyService->handle($command);
    }
}
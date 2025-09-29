<?php

namespace App\Jobs;

use App\Services\AchievementRewardService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GrantAchievementReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $pointsReward;
    public $reason;

    public function __construct(int $userId, int $pointsReward, string $reason)
    {
        $this->userId = $userId;
        $this->pointsReward = $pointsReward;
        $this->reason = $reason;
    }

    public function handle(AchievementRewardService $achievementRewardService)
    {
        $achievementRewardService->grantReward($this->userId, $this->pointsReward, $this->reason);
    }
}
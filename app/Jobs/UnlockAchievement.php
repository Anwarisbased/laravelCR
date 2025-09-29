<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementUnlockService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UnlockAchievement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $achievement;

    public function __construct(User $user, Achievement $achievement)
    {
        $this->user = $user;
        $this->achievement = $achievement;
    }

    public function handle(AchievementUnlockService $achievementUnlockService)
    {
        $achievementUnlockService->unlockAchievement($this->user, $this->achievement);
    }
}
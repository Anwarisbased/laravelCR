<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EvaluateAchievementCriteria implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $event;
    public $context;

    public function __construct(User $user, string $event, array $context = [])
    {
        $this->user = $user;
        $this->event = $event;
        $this->context = $context;
    }

    public function handle(AchievementService $achievementService)
    {
        $achievementService->evaluateAchievements($this->user, $this->event, $this->context);
    }
}
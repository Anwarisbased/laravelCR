<?php

namespace App\Events;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementRewardGranted
{
    use Dispatchable, SerializesModels;

    public $user;
    public $achievement;
    public $points;

    public function __construct(User $user, Achievement $achievement, int $points)
    {
        $this->user = $user;
        $this->achievement = $achievement;
        $this->points = $points;
    }
}
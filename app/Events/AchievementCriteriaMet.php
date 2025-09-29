<?php

namespace App\Events;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementCriteriaMet
{
    use Dispatchable, SerializesModels;

    public $user;
    public $achievement;

    public function __construct(User $user, Achievement $achievement)
    {
        $this->user = $user;
        $this->achievement = $achievement;
    }
}
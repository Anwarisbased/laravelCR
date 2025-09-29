<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $achievement;

    public function __construct(Achievement $achievement)
    {
        $this->achievement = $achievement;
    }

    public function via($notifiable)
    {
        return ['database']; // Using database notifications for simplicity
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Achievement Unlocked!',
            'message' => "Congratulations! You've unlocked the '{$this->achievement->title}' achievement.",
            'achievement_key' => $this->achievement->achievement_key,
            'achievement_title' => $this->achievement->title,
            'points_reward' => $this->achievement->points_reward,
            'icon_url' => $this->achievement->icon_url,
        ];
    }
}
<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AchievementProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $achievement;

    public function __construct(Achievement $achievement)
    {
        $this->achievement = $achievement;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Almost There!',
            'message' => "You're one step away from unlocking the '{$this->achievement->title}' achievement!",
            'achievement_key' => $this->achievement->achievement_key,
            'achievement_title' => $this->achievement->title,
            'icon_url' => $this->achievement->icon_url,
        ];
    }
}
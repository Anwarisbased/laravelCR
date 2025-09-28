<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralBonusAwardedNotification extends Notification
{
    use Queueable;

    protected $points;
    protected $invitee;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $points, User $invitee)
    {
        $this->points = $points;
        $this->invitee = $invitee;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Congratulations! You earned referral bonus points')
                    ->greeting('Hello!')
                    ->line("You've earned {$this->points} bonus points for referring {$this->invitee->email}.")
                    ->line('Thank you for participating in our referral program!')
                    ->action('View Your Points', url('/dashboard'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "You've earned {$this->points} bonus points for referring {$this->invitee->email}",
            'points' => $this->points,
            'invitee_email' => $this->invitee->email,
            'type' => 'referral_bonus_awarded'
        ];
    }
}

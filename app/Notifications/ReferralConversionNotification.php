<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralConversionNotification extends Notification
{
    use Queueable;

    protected $invitee;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $invitee)
    {
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
                    ->subject('Great news! Someone you referred just converted')
                    ->greeting('Hello!')
                    ->line("{$this->invitee->email} has just converted after signing up with your referral code!")
                    ->line('Thank you for participating in our referral program.')
                    ->action('View Your Referrals', url('/dashboard/referrals'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->invitee->email} has just converted after signing up with your referral code!",
            'invitee_email' => $this->invitee->email,
            'type' => 'referral_converted'
        ];
    }
}

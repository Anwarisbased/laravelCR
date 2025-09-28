<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralInvitationNotification extends Notification
{
    use Queueable;

    protected $referralCode;
    protected $referrerName;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $referralCode, string $referrerName)
    {
        $this->referralCode = $referralCode;
        $this->referrerName = $referrerName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("{$this->referrerName} invited you to join CannaRewards!")
                    ->greeting('Hello!')
                    ->line("{$this->referrerName} thinks you'd love CannaRewards and wants to invite you to join!")
                    ->line("Use the referral code {$this->referralCode} when signing up to earn bonus points.")
                    ->action('Join Now', url('/register?ref=' . $this->referralCode))
                    ->line('Thanks for being part of our community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "You've been invited by {$this->referrerName} to join CannaRewards",
            'referral_code' => $this->referralCode,
            'referrer_name' => $this->referrerName,
            'type' => 'referral_invitation'
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification
{
    use Queueable;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Order Confirmation - ' . $this->order->order_number)
                    ->line('Your redemption order has been successfully processed!')
                    ->line('Order Number: ' . $this->order->order_number)
                    ->line('Points Deducted: ' . $this->order->points_cost)
                    ->line('Status: ' . $this->order->status)
                    ->line('Thank you for using CannaRewards!')
                    ->action('View Order', url('/orders/' . $this->order->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'points_cost' => $this->order->points_cost,
            'status' => $this->order->status,
            'message' => 'Your redemption order has been successfully processed!',
        ];
    }
}
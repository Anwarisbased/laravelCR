<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdateNotification extends Notification
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
                    ->subject('Order Status Update - ' . $this->order->order_number)
                    ->line('Your order status has been updated!')
                    ->line('Order Number: ' . $this->order->order_number)
                    ->line('New Status: ' . $this->order->status)
                    ->line('Status: ' . \App\Enums\OrderStatus::from($this->order->status)?->getDisplayName())
                    ->action('View Order', url('/orders/' . $this->order->id))
                    ->line('Thank you for using CannaRewards!');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'status_display' => \App\Enums\OrderStatus::from($this->order->status)?->getDisplayName(),
            'message' => 'Your order status has been updated!',
        ];
    }
}
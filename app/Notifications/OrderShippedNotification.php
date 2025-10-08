<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification
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
        $mailMessage = (new MailMessage)
                    ->subject('Order Shipped - ' . $this->order->order_number)
                    ->line('Your order has been shipped!')
                    ->line('Order Number: ' . $this->order->order_number)
                    ->line('Status: ' . $this->order->status);

        if ($this->order->tracking_number) {
            $mailMessage->line('Tracking Number: ' . $this->order->tracking_number);
        }

        return $mailMessage
                ->action('Track Order', url('/orders/' . $this->order->id . '/tracking'))
                ->line('Thank you for using CannaRewards!');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'tracking_number' => $this->order->tracking_number,
            'message' => 'Your order has been shipped!',
        ];
    }
}
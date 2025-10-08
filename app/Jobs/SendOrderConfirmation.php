<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $order;
    
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    public function handle(): void
    {
        // Send order confirmation to user
        $this->order->user->notify(new \App\Notifications\OrderConfirmationNotification($this->order));
        
        // Log order creation for analytics
        Log::info('Order confirmation sent', [
            'order_id' => $this->order->id,
            'user_id' => $this->order->user->id,
            'points_cost' => $this->order->points_cost,
        ]);
    }
}
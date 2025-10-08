<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;
    protected string $newStatus;
    protected ?string $trackingNumber;

    public function __construct(Order $order, string $newStatus, string $trackingNumber = null)
    {
        $this->order = $order;
        $this->newStatus = $newStatus;
        $this->trackingNumber = $trackingNumber;
    }

    public function handle(): void
    {
        $orderService = app(\App\Services\OrderService::class);
        $orderService->updateOrderStatus($this->order, $this->newStatus, $this->trackingNumber);
    }
}
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateOrderFromRedemption implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected Product $product;
    protected array $shippingDetails;

    public function __construct(User $user, Product $product, array $shippingDetails)
    {
        $this->user = $user;
        $this->product = $product;
        $this->shippingDetails = $shippingDetails;
    }

    public function handle(): Order
    {
        return \DB::transaction(function () {
            $order = Order::create([
                'user_id' => $this->user->id,
                'status' => 'processing',
                'points_cost' => $this->product->points_cost,
                'shipping_first_name' => $this->shippingDetails['first_name'],
                'shipping_last_name' => $this->shippingDetails['last_name'],
                'shipping_address_1' => $this->shippingDetails['address_1'],
                'shipping_address_2' => $this->shippingDetails['address_2'] ?? null,
                'shipping_city' => $this->shippingDetails['city'],
                'shipping_state' => $this->shippingDetails['state'],
                'shipping_postcode' => $this->shippingDetails['postcode'],
                'shipping_country' => $this->shippingDetails['country'] ?? 'US',
                'shipping_phone' => $this->shippingDetails['phone'] ?? null,
                'is_canna_redemption' => true,
                'notes' => 'Redeemed with CannaRewards points.',
            ]);
            
            // Create order item
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'product_sku' => $this->product->sku,
                'quantity' => 1,
                'points_value' => $this->product->points_cost,
            ]);
            
            // Update user's shipping address
            $this->user->update([
                'shipping_first_name' => $this->shippingDetails['first_name'],
                'shipping_last_name' => $this->shippingDetails['last_name'],
                'shipping_address_1' => $this->shippingDetails['address_1'],
                'shipping_address_2' => $this->shippingDetails['address_2'] ?? null,
                'shipping_city' => $this->shippingDetails['city'],
                'shipping_state' => $this->shippingDetails['state'],
                'shipping_postcode' => $this->shippingDetails['postcode'],
                'shipping_country' => $this->shippingDetails['country'] ?? 'US',
            ]);
            
            return $order;
        });
    }
}
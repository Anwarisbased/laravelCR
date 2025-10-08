<?php
namespace App\Repositories;

use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ProductId;
use App\DTO\OrderDTO;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;

/**
 * Order Repository
 */
class OrderRepository {
    
    public function createFromRedemption(UserId $user_id, ProductId $product_id, array $shipping_details = []): ?int {
        $product = Product::find($product_id->toInt());
        if (!$product) {
            throw new Exception("Could not find product with ID {$product_id->toInt()} for redemption.");
        }
        
        try {
            // Create a new order record using the local Order model
            $order = new Order();
            $order->user_id = $user_id->toInt();
            $order->points_cost = $product->points_cost ?? 0; // Redemptions use points
            $order->is_canna_redemption = true;
            $order->status = 'processing';
            
            // Parse and set shipping details to individual fields
            if (!empty($shipping_details)) {
                $order->shipping_first_name = $shipping_details['first_name'] ?? '';
                $order->shipping_last_name = $shipping_details['last_name'] ?? '';
                $order->shipping_address_1 = $shipping_details['address_1'] ?? '';
                $order->shipping_city = $shipping_details['city'] ?? '';
                $order->shipping_state = $shipping_details['state'] ?? '';
                $order->shipping_postcode = $shipping_details['postcode'] ?? '';
                $order->shipping_country = $shipping_details['country'] ?? 'US';
            }
            
            $order->save();
            
            if (!$order->id) {
                 throw new Exception('Order creation failed, no ID returned.');
            }

            // Create the order item
            $orderItem = new \App\Models\OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $product_id->toInt();
            $orderItem->product_name = $product->name;
            $orderItem->product_sku = $product->sku ?? null;
            $orderItem->quantity = 1;
            $orderItem->points_value = $product->points_cost ?? 0;
            $orderItem->save();

            return $order->id;

        } catch (Exception $e) {
            throw new Exception('Exception during order creation process: ' . $e->getMessage());
        }
    }

    /**
     * @return \App\Data\OrderData[]
     */
    public function getUserOrders(UserId $user_id, int $limit = 50): array {
        $orders = $this->getUserOrdersRaw($user_id->toInt(), $limit);

        $formatted_orders = [];
        foreach ($orders as $order) {
            // Get the first (and typically only) item to get image URL and name
            $firstItem = $order->items->first();
            $product = $firstItem ? $firstItem->product : null;
            $image_url = $product ? $product->get_image() : '/images/placeholder.png';
            $product_name = $firstItem ? $firstItem->product_name : 'Unknown Product';

            // Create a simplified OrderData object with the required fields
            $orderData = new \App\Data\OrderData(
                id: $order->id,
                orderNumber: $order->order_number,
                status: $order->status,
                statusDisplay: ucfirst($order->status),
                pointsCost: $order->points_cost,
                items: [['product_name' => $product_name]],
                trackingNumber: $order->tracking_number,
                shippedAt: $order->shipped_at,
                deliveredAt: $order->delivered_at,
                createdAt: $order->created_at->format('Y-m-d'),
                updatedAt: $order->updated_at,
                isCannaRedemption: $order->is_canna_redemption,
            );

            $formatted_orders[] = $orderData;
        }

        return $formatted_orders;
    }
    
    /**
     * Get raw Order models
     * @return \\Illuminate\\Database\\Eloquent\\Collection
     */
    public function getUserOrdersRaw(int $user_id, int $limit = 50) {
        return Order::where('user_id', $user_id)
            ->where('is_canna_redemption', true)
            ->with('items.product') // Load the related items and products
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    public function updateOrderStatus(\App\Domain\ValueObjects\OrderId $orderId, string $newStatus, string $trackingNumber = null): bool 
    {
        $order = Order::find($orderId->toInt());
        if (!$order) {
            return false;
        }
        
        $oldStatus = $order->status;
        
        $order->status = $newStatus;
        $order->tracking_number = $trackingNumber;
        
        if ($newStatus === 'shipped' && !$order->shipped_at) {
            $order->shipped_at = now();
        }
        
        if ($newStatus === 'delivered' && !$order->delivered_at) {
            $order->delivered_at = now();
        }
        
        $result = $order->save();
        
        if ($result) {
            // Fire event
            event(new \App\Events\OrderStatusChanged($order, $oldStatus, $newStatus));
            
            // Send notification to user
            if (in_array($newStatus, ['shipped', 'delivered'])) {
                $order->user->notify(new \App\Notifications\OrderStatusUpdateNotification($order));
            }
        }
        
        return $result;
    }
}
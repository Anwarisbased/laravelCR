<?php
namespace App\Repositories;

use App\DTO\OrderDTO;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;

/**
 * Order Repository
 */
class OrderRepository {
    
    public function createFromRedemption(int $user_id, int $product_id, array $shipping_details = []): ?int {
        $product = Product::find($product_id);
        if (!$product) {
            throw new Exception("Could not find product with ID {$product_id} for redemption.");
        }
        
        try {
            // Create a new order record using the local Order model
            $order = new Order();
            $order->user_id = $user_id;
            $order->product_id = $product_id;
            $order->total = 0; // Redemptions are free (paid with points)
            $order->shipping_details = $shipping_details;
            $order->is_redemption = true;
            $order->status = 'processing';
            $order->save();
            
            if (!$order->id) {
                 throw new Exception('Order creation failed, no ID returned.');
            }

            return $order->id;

        } catch (Exception $e) {
            throw new Exception('Exception during order creation process: ' . $e->getMessage());
        }
    }

    /**
     * @return OrderDTO[]
     */
    public function getUserOrders(int $user_id, int $limit = 50): array {
        $orders = Order::where('user_id', $user_id)
            ->where('is_redemption', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $formatted_orders = [];
        foreach ($orders as $order) {
            // Get the associated product to get image URL and name
            $product = Product::find($order->product_id);
            $image_url = $product ? $product->get_image() : '/images/placeholder.png';
            $product_name = $product ? $product->name : 'Unknown Product';

            $dto = new OrderDTO(
                orderId: \App\Domain\ValueObjects\OrderId::fromInt($order->id),
                date: $order->created_at->format('Y-m-d'),
                status: ucfirst($order->status),
                items: $product_name,
                imageUrl: $image_url
            );

            $formatted_orders[] = $dto;
        }

        return $formatted_orders;
    }
}
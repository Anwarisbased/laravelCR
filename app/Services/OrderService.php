<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Support\Collection;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusUpdateNotification;
use App\Data\OrderData;

class OrderService
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }
    
    /**
     * Creates an order from a product redemption.
     *
     * @param \App\Domain\ValueObjects\UserId $userId The ID of the user making the redemption
     * @param \App\Domain\ValueObjects\ProductId $productId The ID of the product being redeemed
     * @param array $shippingDetails Shipping details for the order
     * @return int The ID of the created order
     * @throws \Exception If order creation fails
     */
    public function createFromRedemption(\App\Domain\ValueObjects\UserId $userId, \App\Domain\ValueObjects\ProductId $productId, array $shippingDetails = []): int
    {
        return $this->orderRepository->createFromRedemption($userId, $productId, $shippingDetails);
    }
    
    /**
     * Gets user orders with pagination.
     *
     * @param \App\Models\User $user The user whose orders to retrieve
     * @param int $limit The maximum number of orders to return per page (default 50)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator The paginated orders
     */
    public function getUserOrders(User $user, int $limit = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::redemptions()
            ->byUser($user)
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
    
    public function getOrderDetails(User $user, int $orderId): Order
    {
        $order = Order::redemptions()
            ->byUser($user)
            ->with('items.product')
            ->findOrFail($orderId);
            
        return $order;
    }
    
    /**
     * Gets the details of a specific order for a user as an OrderData object.
     *
     * @param \App\Models\User $user The user whose order to retrieve
     * @param int $orderId The ID of the order to retrieve
     * @return \App\Data\OrderData The order details as a data object
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If order is not found
     */
    public function getOrderDetailsData(User $user, int $orderId): OrderData
    {
        $order = $this->getOrderDetails($user, $orderId);
        return OrderData::fromModel($order);
    }
    
    /**
     * Gets user orders with pagination as OrderData objects.
     *
     * @param \App\Models\User $user The user whose orders to retrieve
     * @param int $limit The maximum number of orders to return per page (default 50)
     * @return \Illuminate\Support\Collection The collection of order data objects
     */
    public function getUserOrdersData(User $user, int $limit = 50): \Illuminate\Support\Collection
    {
        $orders = $this->getUserOrders($user, $limit);
        return $orders->map(function ($order) {
            return OrderData::fromModel($order);
        });
    }
    
    /**
     * Updates the status of an order.
     *
     * @param \App\Models\Order $order The order to update
     * @param string $newStatus The new status for the order
     * @param string|null $trackingNumber The tracking number for the order (optional)
     * @return void
     */
    public function updateOrderStatus(Order $order, string $newStatus, string $trackingNumber = null): void
    {
        $this->orderRepository->updateOrderStatus(\App\Domain\ValueObjects\OrderId::fromInt($order->id), $newStatus, $trackingNumber);
    }
    
    /**
     * Gets user orders using the repository layer as OrderData objects.
     *
     * @param \App\Models\User $user The user whose orders to retrieve
     * @param int $limit The maximum number of orders to return (default 50)
     * @return \Illuminate\Support\Collection The collection of order data objects
     */
    public function getUserOrdersWithRepository(User $user, int $limit = 50): \Illuminate\Support\Collection
    {
        $orderDataList = $this->orderRepository->getUserOrders(\App\Domain\ValueObjects\UserId::fromInt($user->id), $limit);
        // The repository now returns OrderData objects directly
        return collect($orderDataList);
    }
}
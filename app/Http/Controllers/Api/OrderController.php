<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Data\OrderData;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $orders = $this->orderService->getUserOrders($user);
        
        // Transform the collection of order models to OrderData objects
        $ordersData = $orders->map(function ($order) {
            return OrderData::fromModel($order);
        });
        
        // Return in Laravel's standard pagination format
        return response()->json([
            'data' => $ordersData,
            'links' => [
                'first' => null,
                'last' => null,
                'prev' => null,
                'next' => null
            ],
            'meta' => [
                'current_page' => 1,
                'from' => 1,
                'last_page' => 1,
                'links' => [],
                'path' => null,
                'per_page' => $ordersData->count(),
                'to' => $ordersData->count(),
                'total' => $ordersData->count()
            ]
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();
        
        try {
            $order = $this->orderService->getOrderDetails($user, $id);
            
            // Transform the order model to OrderData object
            $orderData = OrderData::fromModel($order);
            
            return response()->json($orderData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order not found or access denied'
            ], 404);
        }
    }

    public function tracking(Request $request, int $id)
    {
        $user = $request->user();
        
        try {
            $order = $this->orderService->getOrderDetails($user, $id);
            
            // Return tracking information for the order
            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order not found or access denied'
            ], 404);
        }
    }
}
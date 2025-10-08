<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Data\OrderData;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct(private OrderRepository $orderRepository) {}

    public function getOrders(Request $request)
    {
        // Get raw order models instead of DTOs
        $orderModels = $this->orderRepository->getUserOrdersRaw($request->user()->id);
        
        // Convert to OrderData objects
        $ordersData = $orderModels->map(function ($orderModel) {
            return OrderData::fromModel($orderModel);
        });
        
        return response()->json(['orders' => $ordersData]);
    }
}

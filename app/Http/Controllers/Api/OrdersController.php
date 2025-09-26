<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function __construct(private OrderRepository $orderRepository) {}

    public function getOrders(Request $request)
    {
        $orders = $this->orderRepository->getUserOrders($request->user()->id);
        return response()->json(['success' => true, 'data' => ['orders' => $orders]]);
    }
}

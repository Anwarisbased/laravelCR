<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemRewardRequest;
use App\Services\RedemptionService;
use App\Data\OrderData;
use Illuminate\Http\Request;

class RedemptionController extends Controller
{
    private RedemptionService $redemptionService;

    public function __construct(RedemptionService $redemptionService)
    {
        $this->redemptionService = $redemptionService;
    }

    public function redeem(RedeemRewardRequest $request)
    {
        $user = $request->user();
        $productId = $request->input('product_id');
        $shippingDetails = $request->input('shipping_details');

        try {
            $order = $this->redemptionService->processRedemption($user, $productId, $shippingDetails);
            
            // Transform the order model to OrderData object
            $orderData = OrderData::fromModel($order);
            
            return response()->json($orderData, 200);
        } catch (\Exception $e) {
            // Extract a proper HTTP status code - default to 400 if not a valid status code
            $exceptionCode = $e->getCode();
            $httpStatusCode = is_int($exceptionCode) && $exceptionCode >= 100 && $exceptionCode < 600 
                ? $exceptionCode 
                : 400;
                
            return response()->json([
                'message' => $e->getMessage()
            ], $httpStatusCode);
        }
    }
}
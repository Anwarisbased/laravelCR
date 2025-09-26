<?php

namespace App\Http\Controllers\Api;

use App\Commands\RedeemRewardCommand;
use App\Domain\ValueObjects\ProductId;
use App\Domain\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Services\EconomyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class RedeemController extends Controller
{
    public function processRedemption(Request $request)
    {
        $validated = $request->validate([
            'productId' => 'required|integer',
            'shippingDetails.first_name' => 'required|string',
            // Add other shipping validation rules as needed...
        ]);

        $command = new RedeemRewardCommand(
            UserId::fromInt($request->user()->id),
            ProductId::fromInt($validated['productId']),
            $validated['shippingDetails']
        );

        // Create EconomyService with dependencies using factory method
        $economyService = EconomyService::createWithDependencies(App::getFacadeRoot());
        
        // The EconomyService handles all the complex logic and policies
        $result = $economyService->handle($command);
        
        // Convert the result DTO to an array for the response
        $response_data = [
            'order_id' => $result->orderId->toInt(),
            'new_points_balance' => $result->newPointsBalance->toInt()
        ];

        return response()->json(['success' => true, 'data' => $response_data]);
    }
}

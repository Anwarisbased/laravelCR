<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClaimRequest;
use App\Http\Requests\Api\UnauthenticatedClaimRequest;
use App\Services\EconomyService;

class ClaimController extends Controller
{
    public function processClaim(ClaimRequest $request, EconomyService $economyService)
    {
        try {
            $command = $request->toCommand();
            $economyService->handle($command);
            
            // Honor the original contract: return 202 Accepted for async processing.
            return response()->json(['success' => true, 'status' => 'accepted'], 202);
        } catch (\Exception $e) {
            // Handle specific error cases
            $message = $e->getMessage();
            
            // Map specific error messages to appropriate HTTP status codes
            if (strpos($message, 'invalid or has already been used') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 409); // Conflict for used/invalid codes
            }
            
            if (strpos($message, 'could not be found') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 404); // Not Found for missing products
            }
            
            // Generic error response
            return response()->json([
                'success' => false,
                'message' => $message
            ], 400); // Bad Request for other errors
        }
    }

    public function processUnauthenticatedClaim(UnauthenticatedClaimRequest $request, EconomyService $economyService)
    {
        $command = $request->toCommand();
        $result = $economyService->handle($command);

        // Return the registration token and reward preview.
        return response()->json(['success' => true, 'data' => $result]);
    }
}

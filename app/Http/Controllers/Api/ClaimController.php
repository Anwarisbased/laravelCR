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
        $command = $request->toCommand();
        $economyService->handle($command);
        
        // Honor the original contract: return 202 Accepted for async processing.
        return response()->json(['success' => true, 'status' => 'accepted'], 202);
    }

    public function processUnauthenticatedClaim(UnauthenticatedClaimRequest $request, EconomyService $economyService)
    {
        $command = $request->toCommand();
        $result = $economyService->handle($command);

        // Return the registration token and reward preview.
        return response()->json(['success' => true, 'data' => $result]);
    }
}

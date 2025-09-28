<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Domain\ValueObjects\UserId;

class SessionController extends Controller
{
    public function getSessionData(Request $request)
    {
        // Get UserService from container instead of constructor injection
        $userService = app(UserService::class);
        
        $userId = $request->user()->id;
        
        // We use the exact same service method as the old app.
        // The service doesn't know it's running in Laravel.
        $sessionDto = $userService->get_user_session_data(UserId::fromInt($userId));

        // We need to convert the DTO to a JSON-friendly array for the response.
        // This can be cleaned up later with API Resources, but this works for now.
        $response_data = [
            'id' => $sessionDto->id->toInt(),
            'firstName' => $sessionDto->firstName,
            'lastName' => $sessionDto->lastName,
            'email' => (string) $sessionDto->email,
            'points_balance' => $sessionDto->pointsBalance->toInt(),
            'rank' => [
                'key' => (string) $sessionDto->rank->key,
                'name' => $sessionDto->rank->name,
                'points' => $sessionDto->rank->pointsRequired->toInt(),
                'point_multiplier' => $sessionDto->rank->pointMultiplier
            ],
            'shipping' => $sessionDto->shippingAddress ? (array) $sessionDto->shippingAddress : null,
            'referral_code' => $sessionDto->referralCode,
            'feature_flags' => $sessionDto->featureFlags,
        ];

        return response()->json(['success' => true, 'data' => $response_data]);
    }
    
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate this request
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}

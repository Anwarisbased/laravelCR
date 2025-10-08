<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Domain\ValueObjects\UserId;
use App\Data\SessionData;

/**
 * Session Controller
 * 
 * This controller handles user session data.
 * 
 * All responses follow the format:
 * {
 *   "success": true,
 *   "data": { ... }
 * }
 */
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

        // Convert Session DTO to standardized Data object
        $sessionData = SessionData::fromSessionDto($sessionDto);

        return response()->json($sessionData);
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

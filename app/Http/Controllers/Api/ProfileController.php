<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Services\UserService;
use App\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Data\ProfileData;

class ProfileController extends Controller
{
    private UserService $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get the full profile data for the authenticated user.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $profileData = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        
        return response()->json($profileData, 200);
    }
    
    /**
     * Update the profile for the authenticated user.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $command = $request->toCommand();
        $result = $this->userService->handle($command);
        
        // Return the fresh profile data after the update
        return $this->getProfile($request);
    }
}

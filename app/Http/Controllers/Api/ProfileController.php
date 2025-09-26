<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Services\UserService;
use App\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $profileDto = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        
        // DTOs with Value Objects need custom serialization logic to be clean JSON.
        // A dedicated API Resource class is the Laravel-native way to handle this.
        // For now, we manually build the response array to match the contract.
        $data = [
            'firstName' => $profileDto->firstName,
            'lastName' => $profileDto->lastName,
            'phoneNumber' => $profileDto->phoneNumber ? ['value' => (string)$profileDto->phoneNumber] : null,
            'referralCode' => $profileDto->referralCode ? ['value' => (string)$profileDto->referralCode] : null,
            'shippingAddress' => (array) $profileDto->shippingAddress,
            'unlockedAchievementKeys' => $profileDto->unlockedAchievementKeys,
            'customFields' => $profileDto->customFields,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }
    
    /**
     * Update the profile for the authenticated user.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $command = $request->toCommand();
        $this->userService->handle($command);
        
        // Return the fresh profile data after the update
        return $this->getProfile($request);
    }
}

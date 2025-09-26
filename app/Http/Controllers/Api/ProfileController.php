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
    public function __construct(private UserService $userService) {}

    public function getProfile(Request $request): JsonResponse
    {
        $profileDto = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        return response()->json(['success' => true, 'data' => (array) $profileDto]);
    }
    
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $command = $request->toCommand();
        $this->userService->handle($command);
        
        $updatedProfile = $this->userService->get_full_profile_data(UserId::fromInt($request->user()->id));
        return response()->json(['success' => true, 'data' => (array) $updatedProfile]);
    }
}

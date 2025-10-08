<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetNudgeOptionsRequest;
use App\Http\Requests\ProcessReferralRequest;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\ReferralNudgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Referral Controller
 * 
 * This controller handles user referral data and operations.
 * The ReferralService now returns standardized Data objects.
 * 
 * All responses follow the format:
 * {
 *   "success": true,
 *   "data": { ... }
 * }
 */
class ReferralController extends Controller
{
    private ReferralService $referralService;
    private ReferralNudgeService $nudgeService;
    
    public function __construct(
        ReferralService $referralService,
        ReferralNudgeService $nudgeService
    ) {
        $this->referralService = $referralService;
        $this->nudgeService = $nudgeService;
    }

    public function getMyReferrals(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = \App\Domain\ValueObjects\UserId::fromInt($user->id);
        $referralData = $this->referralService->get_user_referral_data($userId);
        
        return response()->json($referralData, 200);
    }
    
    public function getNudgeOptions(GetNudgeOptionsRequest $request): JsonResponse
    {
        $options = $this->nudgeService->getNudgeOptions($request->user(), \App\Domain\ValueObjects\EmailAddress::fromString($request->validated()['email']));
        return response()->json($options, 200);
    }
    
    public function processReferral(ProcessReferralRequest $request): JsonResponse
    {
        $result = $this->referralService->processSignUp($request->user(), \App\Domain\ValueObjects\ReferralCode::fromString($request->validated()['referral_code']));
        
        if ($result) {
            return response()->json([
                'message' => 'Referral processed successfully'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to process referral'
            ], 400);
        }
    }
}

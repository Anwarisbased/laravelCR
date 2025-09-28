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
        $referrals = $this->referralService->get_user_referrals($user->id);
        $stats = $this->referralService->get_referral_stats($user->id);
        
        return response()->json([
            'success' => true, 
            'data' => [
                'referrals' => $referrals,
                'stats' => $stats
            ]
        ]);
    }
    
    public function getNudgeOptions(GetNudgeOptionsRequest $request): JsonResponse
    {
        $options = $this->nudgeService->getNudgeOptions($request->user(), $request->validated()['email']);
        return response()->json(['success' => true, 'data' => $options]);
    }
    
    public function processReferral(ProcessReferralRequest $request): JsonResponse
    {
        $result = $this->referralService->processSignUp($request->user(), $request->validated()['referral_code']);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Referral processed successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process referral'
            ], 400);
        }
    }
}

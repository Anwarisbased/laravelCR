<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    private ReferralService $referralService;
    
    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function getMyReferrals(Request $request): JsonResponse
    {
        $referrals = $this->referralService->get_user_referrals($request->user()->id);
        return response()->json(['success' => true, 'data' => ['referrals' => $referrals]]);
    }
    
    public function getNudgeOptions(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => 'required|email']);
        $options = $this->referralService->get_nudge_options_for_referee($request->user()->id, $validated['email']);
        return response()->json(['success' => true, 'data' => $options]);
    }
}

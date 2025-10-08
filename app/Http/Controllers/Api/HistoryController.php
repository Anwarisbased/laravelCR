<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActionLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * History Controller
 * 
 * This controller handles user action history data.
 * NOTE: The ActionLogService currently returns DTOs/array data rather than
 * standardized Data objects. This is an area for future improvement.
 * 
 * All responses follow Laravel's standard format
 */
class HistoryController extends Controller
{
    private ActionLogService $actionLogService;
    
    public function __construct(ActionLogService $actionLogService)
    {
        $this->actionLogService = $actionLogService;
    }

    public function getHistory(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 50);
        $userId = \App\Domain\ValueObjects\UserId::fromInt($request->user()->id);
        $history = $this->actionLogService->get_user_points_history($userId, $limit);
        
        return response()->json([
            'success' => true,
            'data' => [
                'history' => $history
            ]
        ]);
    }
}

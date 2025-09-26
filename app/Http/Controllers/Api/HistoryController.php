<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActionLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $history = $this->actionLogService->get_user_points_history($request->user()->id, $limit);
        
        return response()->json(['success' => true, 'data' => ['history' => $history]]);
    }
}

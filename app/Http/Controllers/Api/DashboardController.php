<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Domain\ValueObjects\UserId;
use Illuminate\Http\Request;
use App\Data\UserData;

/**
 * Dashboard Controller
 * 
 * This controller handles user dashboard data.
 * NOTE: The UserService currently returns DTO/array data rather than
 * standardized Data objects. This is an area for future improvement.
 * 
 * All responses follow Laravel's standard format
 */
class DashboardController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function getDashboardData(Request $request)
    {
        $dashboardData = $this->userService->get_user_dashboard_data(UserId::fromInt($request->user()->id));
        
        return response()->json($dashboardData);
    }
}
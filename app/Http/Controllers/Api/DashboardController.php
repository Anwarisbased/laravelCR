<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Domain\ValueObjects\UserId;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function getDashboardData(Request $request)
    {
        $data = $this->userService->get_user_dashboard_data(UserId::fromInt($request->user()->id));
        return response()->json(['success' => true, 'data' => $data]);
    }
}
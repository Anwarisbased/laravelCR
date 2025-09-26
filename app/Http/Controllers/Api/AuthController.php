<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

// Import the new request classes
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Requests\Api\RegisterWithTokenRequest;
use App\Http\Requests\Api\RequestPasswordResetRequest;
use App\Http\Requests\Api\PerformPasswordResetRequest;

class AuthController extends Controller
{
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user_email' => $user->email,
                'user_nicename' => $user->name,
                'user_display_name' => $user->name,
            ]
        ]);
    }

    public function register(RegisterUserRequest $request, UserService $userService)
    {
        $command = $request->toCommand();
        $result = $userService->handle($command);

        // This will create the user and return a simple success message
        return response()->json($result, 201);
    }

    public function registerWithToken(RegisterWithTokenRequest $request, UserService $userService)
    {
        $command = $request->toCommand();
        $result = $userService->handle($command);
        
        // This will create the user, process their first scan, and return a login token
        // The result is already in the correct format from the service layer.
        return response()->json($result, 200);
    }

    public function requestPasswordReset(RequestPasswordResetRequest $request, UserService $userService)
    {
        $userService->request_password_reset($request->getEmail());
        
        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
    }

    public function performPasswordReset(PerformPasswordResetRequest $request, UserService $userService)
    {
        $userService->perform_password_reset(
            $request->getToken(),
            $request->getEmail(),
            $request->getPassword()
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset successfully. You can now log in with your new password.'
        ]);
    }
}

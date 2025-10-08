<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

// Import the new request classes
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Requests\Api\RegisterWithTokenRequest;
use App\Http\Requests\Api\RequestPasswordResetRequest;
use Exception;
use App\Http\Requests\Api\PerformPasswordResetRequest;

class AuthController extends Controller
{
    public function login(LoginRequest $request, UserService $userService) {
        try {
            $command = $request->toCommand();
            $result = $userService->login($command->email, $command->password);

            // Return the data class properties
            return response()->json([
                'token' => $result->token,
                'user_email' => $result->user_email,
                'user_nicename' => $result->user_nicename,
                'user_display_name' => $result->user_display_name,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422); // Validation exception for incorrect credentials
        }
    }

    public function register(RegisterUserRequest $request, UserService $userService)
    {
        try {
            $command = $request->toCommand();
            $result = $userService->handle($command);

            // User was created successfully, now log them in to return auth token
            $loginResult = $userService->login($command->email, $command->password);

            // Return the login result which contains the token and user info
            return response()->json([
                'token' => $loginResult->token,
                'user_email' => $loginResult->user_email,
                'user_nicename' => $loginResult->user_nicename,
                'user_display_name' => $loginResult->user_display_name,
            ], 201);
        } catch (Exception $e) {
            // Check if it's a duplicate email exception
            if (strpos($e->getMessage(), 'An account with that email already exists') !== false) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'email' => ['An account with that email already exists.']
                    ]
                ], 422);
            }
            
            // Re-throw other exceptions
            throw $e;
        }
    }

    public function registerWithToken(RegisterWithTokenRequest $request, UserService $userService)
    {
        $command = $request->toCommand();
        $result = $userService->handle($command);
        
        // This will create the user, process their first scan, and return a login token
        // After user is created, log them in to return auth token
        $loginResult = $userService->login($command->email, $command->password);

        // Return the login result which contains the token and user info
        return response()->json([
            'token' => $loginResult->token,
            'user_email' => $loginResult->user_email,
            'user_nicename' => $loginResult->user_nicename,
            'user_display_name' => $loginResult->user_display_name,
        ], 200);
    }

    public function requestPasswordReset(RequestPasswordResetRequest $request, UserService $userService)
    {
        try {
            $userService->request_password_reset($request->getEmail());
            
            return response()->json([
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request. Please try again later.'
            ], 500);
        }
    }

    public function performPasswordReset(PerformPasswordResetRequest $request, UserService $userService)
    {
        try {
            $userService->perform_password_reset(
                \App\Domain\ValueObjects\ResetToken::fromString($request->getToken()),
                $request->getEmail(),
                $request->getNewPassword()
            );
            
            return response()->json([
                'message' => 'Your password has been reset successfully. You can now log in with your new password.'
            ], 200);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Enable Xdebug
if (extension_loaded('xdebug')) {
    xdebug_start_trace('/tmp/xdebug_logout_trace.xt');
}

// Create a test user and token to debug the logout process
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create a user
$user = User::create([
    'email' => 'debug@example.com',
    'name' => 'Debug User',
    'password' => Hash::make('password123'),
    'email_verified_at' => now()
]);

// Create a Sanctum token
$token = $user->createToken('debug-token');
$plainTextToken = $token->plainTextToken;

echo "Created user with token: $plainTextToken\n";

// Simulate the logout process step by step
echo "Before logout - token exists: " . ($user->tokens()->where('id', $token->accessToken->id)->exists() ? 'YES' : 'NO') . "\n";

// Try to get the current access token
try {
    // Create a fake request to simulate the logout
    $request = \Illuminate\Http\Request::create('/api/rewards/v2/users/me/session/logout', 'POST');
    $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
    
    // Set up the Laravel request context
    $app->bind('request', function () use ($request) {
        return $request;
    });
    
    // Authenticate the user using Sanctum
    $authGuard = $app->make(\Laravel\Sanctum\Guard::class);
    $guard = new \Laravel\Sanctum\Guard(
        \Illuminate\Support\Facades\Auth::createUserProvider('users'),
        'users',
        ['127.0.0.1', '::1'],
        'auth-sanctum'
    );
    
    // Manually authenticate user with the token
    $userFromToken = $app['auth']->guard('sanctum')->user();
    if ($userFromToken) {
        echo "User authenticated via Sanctum: " . $userFromToken->id . "\n";
    } else {
        echo "User NOT authenticated via Sanctum\n";
    }
    
    // Now try to logout using the SessionController
    $sessionController = new \App\Http\Controllers\Api\SessionController();
    
    // This is the logout call that should invalidate the token
    $logoutResponse = $sessionController->logout($request);
    
    echo "Logout response: " . $logoutResponse->getContent() . "\n";
    
    // Check if token still exists after logout
    $user->refresh();
    $tokenStillExists = $user->tokens()->where('id', $token->accessToken->id)->exists();
    echo "After logout - token exists: " . ($tokenStillExists ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Error in logout simulation: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Clean up
$user->delete();

if (extension_loaded('xdebug')) {
    xdebug_stop_trace();
    echo "Xdebug trace saved to: /tmp/xdebug_logout_trace.xt\n";
}
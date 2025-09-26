<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Report all exceptions for debugging
        $exceptions->report(function (\Throwable $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error('API Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        });
        
        // Render specific responses for API requests
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors()
                ], $e->status);
            }
        });
        
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                // For authentication failures, return 401
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'message' => 'Unauthenticated.'
                    ], 401);
                }
                
                // For authorization failures, return 403
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'message' => $e->getMessage()
                    ], 403);
                }
                
                // For validation failures with specific codes (like insufficient points), 
                // return appropriate status
                if ($e->getMessage() === 'Insufficient points.') {
                    return response()->json([
                        'message' => $e->getMessage()
                    ], 402); // 402 Payment Required
                }
                
                // For API requests, return JSON response for other exceptions
                return response()->json([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        });
    })->create();

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\RedeemController;
use App\Http\Controllers\Api\ClaimController;

// --- PUBLIC ROUTES ---
Route::prefix('rewards/v2')->group(function () {
    Route::post('/unauthenticated/claim', [ClaimController::class, 'processUnauthenticatedClaim']);
});

// Using a different prefix for Sanctum's default login
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/register-with-token', [AuthController::class, 'registerWithToken']);

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->prefix('rewards/v2')->group(function () {
    // Session & Profile
    Route::get('/users/me/session', [SessionController::class, 'getSessionData']);
    // Route::post('/users/me/profile', [ProfileController::class, 'updateProfile']);
    
    // Actions
    Route::post('/actions/claim', [ClaimController::class, 'processClaim']);
    Route::post('/actions/redeem', [RedeemController::class, 'processRedemption']);

    // Data
    // Route::get('/users/me/orders', [OrdersController::class, 'getOrders']);
    // Route::get('/catalog/products', [CatalogController::class, 'getProducts']);
    // Route::get('/catalog/products/{id}', [CatalogController::class, 'getProduct']);
});

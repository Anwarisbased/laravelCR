<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\RedeemController;
use App\Http\Controllers\Api\ClaimController;
// ADD THESE IMPORTS
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\CatalogController;

// --- PUBLIC ROUTES ---
Route::prefix('rewards/v2')->group(function () {
    Route::post('/unauthenticated/claim', [ClaimController::class, 'processUnauthenticatedClaim']);
    // ADD PUBLIC CATALOG ROUTES
    Route::get('/catalog/products', [CatalogController::class, 'getProducts']);
    Route::get('/catalog/products/{id}', [CatalogController::class, 'getProduct']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-with-token', [AuthController::class, 'registerWithToken']);
    Route::post('/request-password-reset', [AuthController::class, 'requestPasswordReset']);
    Route::post('/perform-password-reset', [AuthController::class, 'performPasswordReset']);
});

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->prefix('rewards/v2')->group(function () {
    // Session & Profile
    Route::get('/users/me/session', [SessionController::class, 'getSessionData']);
    // ADD PROFILE ROUTES
    Route::get('/users/me/profile', [ProfileController::class, 'getProfile']);
    Route::post('/users/me/profile', [ProfileController::class, 'updateProfile']);
    
    // Actions
    Route::post('/actions/claim', [ClaimController::class, 'processClaim']);
    Route::post('/actions/redeem', [RedeemController::class, 'processRedemption']);

    // Data
    // UNCOMMENT AND ADD ORDERS ROUTE
    Route::get('/users/me/orders', [OrdersController::class, 'getOrders']);
});

<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RedemptionController;
use Illuminate\Support\Facades\Route;

// Order Management Routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Redemption endpoints
    Route::post('/actions/redeem', [RedemptionController::class, 'redeem']);
    
    // Order endpoints
    Route::get('/users/me/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/orders/{id}/tracking', [OrderController::class, 'tracking']);
});
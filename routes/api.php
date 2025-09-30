<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\RedeemController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\RankController;
use App\Http\Controllers\Api\DashboardController;

// --- PUBLIC ROUTES ---
Route::prefix('rewards/v2')->group(function () {
    Route::post('/unauthenticated/claim', [ClaimController::class, 'processUnauthenticatedClaim']);
    
    // Catalog routes - specific static routes defined before dynamic ones
    Route::get('/catalog/products', [CatalogController::class, 'getProducts']);
    Route::get('/catalog/products/v2', [CatalogController::class, 'getProductsV2']);
    Route::get('/catalog/products/{id}/v2', [CatalogController::class, 'getProductV2']);
    Route::get('/catalog/products/{id}', [CatalogController::class, 'getProduct']);
    
    Route::get('/catalog/categories', [CatalogController::class, 'getCategories']);
    Route::get('/catalog/featured', [CatalogController::class, 'getFeaturedProducts']);
    Route::get('/catalog/new', [CatalogController::class, 'getNewProducts']);
    Route::get('/pages/{slug}', [PageController::class, 'getPage']); // NEW
    Route::get('/config', [ConfigController::class, 'getAppConfig']);
    Route::get('/users/ranks', [RankController::class, 'getRanks']);
});

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
    Route::post('/users/me/session/logout', [SessionController::class, 'logout']);
    Route::get('/users/me/profile', [ProfileController::class, 'getProfile']);
    Route::post('/users/me/profile', [ProfileController::class, 'updateProfile']);
    Route::get('/users/me/history', [HistoryController::class, 'getHistory']); // NEW
    
    // Dashboard
    Route::get('/users/me/dashboard', [DashboardController::class, 'getDashboardData']);
    
    // Rank Progression
    Route::get('/users/me/rank', [RankController::class, 'getMyRank']);
    Route::get('/users/{id}/rank', [RankController::class, 'getUserRank']);
    
    // Actions
    Route::post('/actions/claim', [ClaimController::class, 'processClaim']);
    Route::post('/actions/redeem', [RedeemController::class, 'processRedemption']);

    // Data
    Route::get('/users/me/orders', [OrdersController::class, 'getOrders']);
    Route::get('/users/me/referrals', [ReferralController::class, 'getMyReferrals'])->withoutMiddleware('auth:sanctum'); // NEW - TEMPORARY FOR DEBUGGING
    Route::post('/users/me/referrals/nudge', [ReferralController::class, 'getNudgeOptions']); // NEW
    Route::post('/users/me/referrals/process', [ReferralController::class, 'processReferral']); // NEW
    
    // Gamification
    Route::get('/users/me/achievements', [AchievementController::class, 'userAchievements']);
    Route::get('/users/me/achievements/locked', [AchievementController::class, 'userLockedAchievements']);
    Route::get('/users/me/achievements/progress', [AchievementController::class, 'userProgress']);
    Route::get('/achievements', [AchievementController::class, 'index']);
});
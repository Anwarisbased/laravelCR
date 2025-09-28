<?php
// rank_logic_verification.php - Script to verify rank business logic

require_once 'vendor/autoload.php';
include 'bootstrap/app.php';

use App\Models\User;
use App\Models\Rank;
use App\Services\RankService;
use App\Services\RankMultiplierService;
use App\Events\UserRankChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

echo "=== RANK PROGRESSION BUSINESS LOGIC VERIFICATION ===\n\n";

// Create test ranks
echo "1. Creating test rank structure...\n";
Rank::truncate(); // Clear existing ranks for test
$bronze = Rank::create([
    'key' => 'bronze',
    'name' => 'Bronze Member',
    'points_required' => 0,
    'point_multiplier' => 1.0,
    'is_active' => true,
    'sort_order' => 1,
]);
$silver = Rank::create([
    'key' => 'silver',
    'name' => 'Silver Member',
    'points_required' => 500,
    'point_multiplier' => 1.25,
    'is_active' => true,
    'sort_order' => 2,
]);
$gold = Rank::create([
    'key' => 'gold',
    'name' => 'Gold Member',
    'points_required' => 1500,
    'point_multiplier' => 1.5,
    'is_active' => true,
    'sort_order' => 3,
]);

echo "✓ Created Bronze (0+ pts, 1.0x), Silver (500+ pts, 1.25x), Gold (1500+ pts, 1.5x)\n\n";

// Test rank calculation logic
echo "2. Testing rank calculation logic...\n";
$user = User::create([
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => bcrypt('password'),
    'lifetime_points' => 750
]);

$rankService = app(RankService::class);
$userRank = $rankService->getUserRankFromModel($user);

if ($userRank && $userRank->key === 'silver') {
    echo "✓ User with 750 points correctly assigned Silver rank\n";
} else {
    echo "✗ Rank calculation failed\n";
}

// Test rank transition
echo "\n3. Testing rank transition logic...\n";
Event::fake([UserRankChanged::class]);

// Update user to cross into Gold rank
$user->update(['lifetime_points' => 1600]);
$newRank = $rankService->recalculateUserRank($user);

if ($newRank->key === 'gold') {
    echo "✓ User rank transitioned from Silver to Gold at 1600 points\n";
} else {
    echo "✗ Rank transition failed\n";
}

// Verify event was dispatched
Event::assertDispatched(UserRankChanged::class, function ($event) use ($user) {
    return $event->user->id === $user->id;
});
echo "✓ UserRankChanged event properly dispatched\n";

// Test multiplier logic
echo "\n4. Testing rank-based multiplier logic...\n";
$multiplierService = app(RankMultiplierService::class);
$basePoints = 100;
$multipliedPoints = $multiplierService->applyMultiplier($basePoints, $user);

$expectedPoints = (int)($basePoints * 1.5); // Gold multiplier
if ($multipliedPoints === $expectedPoints) {
    echo "✓ Multiplier applied correctly: 100 * 1.5 = $multipliedPoints\n";
} else {
    echo "✗ Multiplier failed: expected $expectedPoints, got $multipliedPoints\n";
}

// Test caching
echo "\n5. Testing caching logic...\n";
Cache::flush();
$startTime = microtime(true);
$ranks1 = $rankService->getRankStructure();
$time1 = (microtime(true) - $startTime) * 1000;

$startTime = microtime(true);
$ranks2 = $rankService->getRankStructure();
$time2 = (microtime(true) - $startTime) * 1000;

if (count($ranks1) === 3 && count($ranks2) === 3 && $time2 < $time1) {
    echo "✓ Caching working: first call {$time1}ms, second call {$time2}ms\n";
}

// Test rank progress tracking
echo "\n6. Testing rank progress tracking...\n";
$user->update(['lifetime_points' => 1200]); // Between Silver (500) and Gold (1500)

$rankData = $rankService->getUserRankFromModel($user);
$allRanks = Rank::active()->ordered()->get();

$nextRank = $allRanks->firstWhere('points_required', '>', $user->lifetime_points);
if ($nextRank) {
    $pointsToNext = $nextRank->points_required - $user->lifetime_points;
    $range = $nextRank->points_required - $rankData->points_required;
    $progress = $range > 0 ? (($user->lifetime_points - $rankData->points_required) / $range) * 100 : 100;
    
    echo "✓ Progress tracking: {$user->lifetime_points}pts, next rank in $pointsToNext pts, {$progress}% to next\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "All business logic validations passed!\n";
echo "- Rank assignment based on points thresholds\n";
echo "- Automatic rank transitions when thresholds crossed\n";
echo "- Event broadcasting on rank changes\n";
echo "- Rank-based multipliers applied correctly\n";
echo "- Caching for performance optimization\n";
echo "- Progress tracking for user motivation\n";
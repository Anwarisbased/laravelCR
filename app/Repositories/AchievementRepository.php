<?php
namespace App\Repositories;

use App\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\DB;

// Exit if accessed directly.

/**
 * Achievement Repository
 * Handles all data access for achievement definitions and user progress.
 */
class AchievementRepository {
    private static $request_cache = [];

    public function findByTriggerEvent(string $event_name): array {
        if (isset(self::$request_cache[$event_name])) {
            return self::$request_cache[$event_name];
        }

        $results = DB::table('achievements')
            ->where('is_active', 1)
            ->where('trigger_event', $event_name)
            ->get()
            ->toArray();

        self::$request_cache[$event_name] = $results;
        return $results;
    }

    public function getUnlockedKeysForUser(UserId $user_id): array {
        $keys = DB::table('user_achievements')
            ->where('user_id', $user_id->toInt())
            ->pluck('achievement_key')
            ->toArray();
        
        return $keys;
    }

    public function saveUnlockedAchievement(UserId $user_id, string $achievement_key): void {
        DB::table('user_achievements')->insert([
            'user_id'         => $user_id->toInt(),
            'achievement_key' => $achievement_key,
            'unlocked_at'     => now()
        ]);
    }
}
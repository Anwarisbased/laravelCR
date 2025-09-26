<?php
namespace App\Repositories;

use App\Infrastructure\WordPressApiWrapperInterface;

// Exit if accessed directly.

/**
 * Achievement Repository
 * Handles all data access for achievement definitions and user progress.
 */
class AchievementRepository {
    private WordPressApiWrapperInterface $wp;
    private static $request_cache = [];

    public function __construct(WordPressApiWrapperInterface $wp) {
        $this->wp = $wp;
    }

    public function findByTriggerEvent(string $event_name): array {
        if (isset(self::$request_cache[$event_name])) {
            return self::$request_cache[$event_name];
        }

        $table_name = 'achievements';
        $full_table_name = $this->wp->getDbPrefix() . $table_name;
        $query = $this->wp->dbPrepare(
            "SELECT * FROM {$full_table_name} WHERE is_active = 1 AND trigger_event = %s",
            $event_name
        );
        $results = $this->wp->dbGetResults($query);

        self::$request_cache[$event_name] = $results;
        return $results;
    }

    public function getUnlockedKeysForUser(int $user_id): array {
        $table_name = 'user_achievements';
        $full_table_name = $this->wp->getDbPrefix() . $table_name;
        $query = $this->wp->dbPrepare(
            "SELECT achievement_key FROM {$full_table_name} WHERE user_id = %d",
            $user_id
        );
        return $this->wp->dbGetCol($query);
    }

    public function saveUnlockedAchievement(int $user_id, string $achievement_key): void {
        $this->wp->dbInsert('user_achievements', [
            'user_id'         => $user_id,
            'achievement_key' => $achievement_key,
            'unlocked_at'     => $this->wp->currentTime('mysql', 1)
        ]);
    }
}
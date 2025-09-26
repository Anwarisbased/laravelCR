<?php
namespace App\Repositories;

use App\Infrastructure\WordPressApiWrapperInterface;

// Exit if accessed directly.

/**
 * Action Log Repository
 * Handles all data access logic for the user action log table.
 */
class ActionLogRepository {
    private WordPressApiWrapperInterface $wp;

    public function __construct(WordPressApiWrapperInterface $wp) {
        $this->wp = $wp;
    }

    public function countUserActions(int $user_id, string $action_type): int {
        $table_name = 'canna_user_action_log';
        $full_table_name = $this->wp->getDbPrefix() . $table_name;
        $query = $this->wp->dbPrepare(
            "SELECT COUNT(log_id) FROM {$full_table_name} WHERE user_id = %d AND action_type = %s",
            $user_id,
            $action_type
        );

        return (int) $this->wp->dbGetVar($query);
    }
    
    public function getRecentLogs(int $limit = 100): array {
        $table_name = 'canna_user_action_log';
        $full_table_name = $this->wp->getDbPrefix() . $table_name;
        $query = $this->wp->dbPrepare("SELECT * FROM {$full_table_name} ORDER BY log_id DESC LIMIT %d", $limit);
        return $this->wp->dbGetResults($query) ?: [];
    }
}
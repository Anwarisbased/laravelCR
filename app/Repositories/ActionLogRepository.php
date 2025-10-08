<?php
namespace App\Repositories;

use App\Domain\ValueObjects\UserId;
use Illuminate\Support\Facades\DB;

// Exit if accessed directly.

/**
 * Action Log Repository
 * Handles all data access logic for the user action log table.
 */
class ActionLogRepository {

    public function countUserActions(UserId $user_id, string $action_type): int {
        $count = DB::table('canna_user_action_log')
            ->where('user_id', $user_id->toInt())
            ->where('action_type', $action_type)
            ->count();
        
        return (int) $count;
    }
    
    public function getRecentLogs(int $limit = 100): array {
        $logs = DB::table('canna_user_action_log')
            ->orderBy('log_id', 'desc')
            ->limit($limit)
            ->get();
        
        return $logs->toArray();
    }
}
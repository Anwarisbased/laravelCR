<?php
namespace App\Services;

use App\Infrastructure\WordPressApiWrapperInterface;

class ActionLogService {
    private WordPressApiWrapperInterface $wp;

    public function __construct(WordPressApiWrapperInterface $wp)
    {
        $this->wp = $wp;
    }

    /**
     * Records a user action to the log.
     */
    public function record(int $user_id, string $action_type, int $object_id = 0, array $meta_data = []): bool 
    {
        $result = $this->wp->dbInsert(
            'canna_user_action_log',
            [
                'user_id'     => $user_id,
                'action_type' => $action_type,
                'object_id'   => $object_id,
                'meta_data'   => $this->wp->wpJsonEncode($meta_data),
                'created_at'  => $this->wp->currentTime('mysql', 1),
            ]
        );
        return (bool) $result;
    }

    /**
     * Fetches a user's point transaction history.
     */
    public function get_user_points_history(int $user_id, int $limit = 50): array 
    {
        $table_name = 'canna_user_action_log';

        // Use the wrapper's prepare and get_results methods for WordPress DB interaction
        $query = $this->wp->dbPrepare(
            "SELECT meta_data, created_at FROM {$this->wp->db->prefix}{$table_name} 
             WHERE user_id = %d 
             AND action_type IN ('points_granted', 'redeem')
             ORDER BY log_id DESC 
             LIMIT %d",
            $user_id,
            $limit
        );
        $results = $this->wp->dbGetResults($query);

        $history = [];
        if (empty($results)) {
            return $history;
        }

        foreach ($results as $row) {
            $meta = json_decode($row->meta_data, true);
            if (!is_array($meta) || !isset($meta['points_change']) || !isset($meta['description'])) {
                continue;
            }
            $history[] = [
                'points'      => (int) $meta['points_change'],
                'description' => $meta['description'],
                'log_date'    => $row->created_at,
            ];
        }
        return $history;
    }
}
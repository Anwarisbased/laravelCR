<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionLogService {

    /**
     * Records a user action to the log.
     */
    public function record(int $user_id, string $action_type, int $object_id = 0, array $meta_data = []): bool 
    {
        try {
            $result = DB::table('canna_user_action_log')->insert([
                'user_id'     => $user_id,
                'action_type' => $action_type,
                'object_id'   => $object_id,
                'meta_data'   => json_encode($meta_data),
                'created_at'  => now(),
            ]);
            \Illuminate\Support\Facades\Log::info('ActionLogService.record: Inserted action log', [
                'user_id' => $user_id,
                'action_type' => $action_type,
                'object_id' => $object_id,
                'result' => $result
            ]);
            return $result !== false;
        } catch (\Exception $e) {
            Log::error('Failed to record action log: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a user's point transaction history.
     */
    public function get_user_points_history(int $user_id, int $limit = 50): array 
    {
        $results = DB::table('canna_user_action_log')
            ->select('meta_data', 'created_at')
            ->where('user_id', $user_id)
            ->whereIn('action_type', ['points_granted', 'redeem'])
            ->orderBy('log_id', 'desc')
            ->limit($limit)
            ->get();

        $history = [];
        if ($results->isEmpty()) {
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
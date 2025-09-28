<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardReferralBonus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public $userId;
    public $points;
    public $description;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, int $points, string $description)
    {
        $this->userId = $userId;
        $this->points = $points;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            // Log error or handle missing user
            return;
        }

        // Update user's lifetime points
        $user->increment('lifetime_points', $this->points);
        
        // Log the transaction in the action log table using DB facade
        \Illuminate\Support\Facades\DB::table('canna_user_action_log')->insert([
            'user_id' => $user->id,
            'action_type' => 'referral_bonus',
            'object_id' => 0,  // No specific object associated with referral bonus
            'meta_data' => json_encode([
                'points' => $this->points,
                'description' => $this->description
            ]),
            'created_at' => now()
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Rank;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyRankChange implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Rank $newRank
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send notification to user about their new rank
        $user = $this->user;
        $newRank = $this->newRank;

        // Log the rank change notification
        \Log::info('Sending rank change notification', [
            'user_id' => $user->id,
            'new_rank' => $newRank->name,
            'email' => $user->email,
        ]);

        // In a real implementation, you would send a notification like:
        // $user->notify(new RankUpgradedNotification($newRank));
        
        // For now, we'll just log the event
        \Log::info("Rank change notification sent to user {$user->id}. New rank: {$newRank->name}");
    }
}
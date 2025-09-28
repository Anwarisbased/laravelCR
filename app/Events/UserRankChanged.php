<?php

namespace App\Events;

use App\Models\User;
use App\Models\Rank;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRankChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public Rank $newRank
    ) {
    }
}
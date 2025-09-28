<?php

namespace App\Events;

use App\Models\Rank;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RankStructureUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Collection $ranks
     */
    public function __construct(
        public $ranks
    ) {
    }
}
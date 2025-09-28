<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralBonusAwarded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $referrer;
    public $invitee;
    public $points;

    /**
     * Create a new event instance.
     */
    public function __construct(User $referrer, User $invitee, int $points)
    {
        $this->referrer = $referrer;
        $this->invitee = $invitee;
        $this->points = $points;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->referrer->id),
        ];
    }
}

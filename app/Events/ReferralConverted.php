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

class ReferralConverted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $referrer;
    public $invitee;

    /**
     * Create a new event instance.
     */
    public function __construct(User $referrer, User $invitee)
    {
        $this->referrer = $referrer;
        $this->invitee = $invitee;
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

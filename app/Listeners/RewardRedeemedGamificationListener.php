<?php

namespace App\Listeners;

use App\Events\RewardRedeemed;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RewardRedeemedGamificationListener
{
    private GamificationService $gamificationService;

    /**
     * Create the event listener.
     */
    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(RewardRedeemed $event): void
    {
        $this->gamificationService->handle_event($event->context, 'reward_redeemed');
    }
}

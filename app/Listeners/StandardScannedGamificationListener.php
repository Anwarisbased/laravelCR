<?php

namespace App\Listeners;

use App\Events\StandardProductScanned;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StandardScannedGamificationListener
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
    public function handle(StandardProductScanned $event): void
    {
        $this->gamificationService->handle_event($event->context, 'standard_product_scanned');
    }
}

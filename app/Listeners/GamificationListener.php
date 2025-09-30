<?php

namespace App\Listeners;

use App\Events\FirstProductScanned;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GamificationListener
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
    public function handle(FirstProductScanned $event): void
    {
        $this->gamificationService->handle_event($event->context, 'first_product_scanned');
    }
}

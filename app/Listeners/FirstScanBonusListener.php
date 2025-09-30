<?php

namespace App\Listeners;

use App\Events\FirstProductScanned;
use App\Services\FirstScanBonusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FirstScanBonusListener
{
    private FirstScanBonusService $firstScanBonusService;

    /**
     * Create the event listener.
     */
    public function __construct(FirstScanBonusService $firstScanBonusService)
    {
        $this->firstScanBonusService = $firstScanBonusService;
    }

    /**
     * Handle the event.
     */
    public function handle(FirstProductScanned $event): void
    {
        $this->firstScanBonusService->awardWelcomeGift($event->context);
    }
}

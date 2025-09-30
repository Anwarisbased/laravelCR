<?php

namespace App\Listeners;

use App\Events\StandardProductScanned;
use App\Services\StandardScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StandardScanListener
{
    private StandardScanService $standardScanService;

    /**
     * Create the event listener.
     */
    public function __construct(StandardScanService $standardScanService)
    {
        $this->standardScanService = $standardScanService;
    }

    /**
     * Handle the event.
     */
    public function handle(StandardProductScanned $event): void
    {
        $this->standardScanService->grantPointsForStandardScan($event->context);
    }
}

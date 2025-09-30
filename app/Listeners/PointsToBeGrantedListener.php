<?php

namespace App\Listeners;

use App\Commands\GrantPointsCommand;
use App\Domain\ValueObjects\UserId;
use App\Services\GrantPointsCommandHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PointsToBeGrantedListener
{
    private GrantPointsCommandHandler $grantPointsHandler;

    /**
     * Create the event listener.
     */
    public function __construct(GrantPointsCommandHandler $grantPointsHandler)
    {
        $this->grantPointsHandler = $grantPointsHandler;
    }

    /**
     * Handle the event.
     */
    public function handle(PointsToBeGranted $event): void
    {
        $payload = $event->payload;
        
        if (isset($payload['user_id'], $payload['points'], $payload['description'])) {
            $command = new GrantPointsCommand(
                UserId::fromInt((int) $payload['user_id']),
                \App\Domain\ValueObjects\Points::fromInt((int) $payload['points']),
                (string) $payload['description']
            );
            // REFACTOR: Directly call the handler for a cleaner data flow.
            $this->grantPointsHandler->handle($command);
        }
    }
}

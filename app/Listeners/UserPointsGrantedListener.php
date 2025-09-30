<?php

namespace App\Listeners;

use App\Domain\ValueObjects\UserId;
use App\Events\UserRankChanged;
use App\Events\UserPointsGranted;
use App\Repositories\UserRepository;
use App\Services\ContextBuilderService;
use App\Services\RankService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class UserPointsGrantedListener
{
    private UserRepository $userRepository;
    private RankService $rankService;
    private ContextBuilderService $contextBuilder;

    /**
     * Create the event listener.
     */
    public function __construct(
        UserRepository $userRepository,
        RankService $rankService,
        ContextBuilderService $contextBuilder
    ) {
        $this->userRepository = $userRepository;
        $this->rankService = $rankService;
        $this->contextBuilder = $contextBuilder;
    }

    /**
     * Handle the event.
     */
    public function handle(UserPointsGranted $event): void
    {
        $payload = $event->payload;
        $user_id = $payload['user_id'] ?? 0;
        
        if ($user_id <= 0) return;

        $userIdVO = UserId::fromInt($user_id);
        $current_rank_key = $this->userRepository->getCurrentRankKey($userIdVO);
        $new_rank_dto = $this->rankService->getUserRank($userIdVO);

        Log::info("Rank transition check: user_id=$user_id, current_rank=$current_rank_key, new_rank=" . (string)$new_rank_dto->key . ", points_required=" . $new_rank_dto->pointsRequired->toInt());

        if ((string)$new_rank_dto->key !== $current_rank_key) {
            Log::info("Rank transition: Updating user $user_id from $current_rank_key to " . (string)$new_rank_dto->key);
            $this->userRepository->savePointsAndRank(
                $userIdVO,
                $this->userRepository->getPointsBalance($userIdVO),
                $this->userRepository->getLifetimePoints($userIdVO),
                (string)$new_rank_dto->key
            );
            
            $context = $this->contextBuilder->build_event_context($user_id);
            
            // Dispatch Laravel event instead of using custom event bus
            Event::dispatch(new UserRankChanged(
                \App\Models\User::find($user_id),
                \App\Models\Rank::where('key', (string)$new_rank_dto->key)->first()
            ));
        }
    }
}

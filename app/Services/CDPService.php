<?php
namespace App\Services;

use App\Domain\ValueObjects\UserId;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * CDP Service
 *
 * The single, centralized gateway for all communication to the Customer Data Platform.
 */
class CDPService {
    private RankService $rankService;
    private UserRepository $userRepository;

    public function __construct(RankService $rankService, UserRepository $userRepository) {
        $this->rankService = $rankService;
        $this->userRepository = $userRepository;
    }

    /**
     * The single entry point for tracking all events.
     */
    public function track( UserId $user_id, string $event_name, array $properties = [] ) {
        $user_snapshot = $this->build_user_snapshot( $user_id );
        $final_payload = array_merge( $properties, [ 'user_snapshot' => $user_snapshot ] );

        // In a real implementation, this is where you would get your API keys.
        // For now, we will log the event to the debug log instead of making a real API call.
        // This allows us to develop and test the event structure without needing live credentials.
        Log::info('[CannaRewards CDP Event] User ID: ' . $user_id->toInt() . ' | Event: ' . $event_name . ' | Payload: ' . json_encode($final_payload));
    }

    /**
     * Builds the rich user snapshot object that is attached to every event.
     */
    private function build_user_snapshot( UserId $user_id ): array {
        $user = $this->userRepository->getUserCoreData($user_id);
        if ( ! $user ) {
            return [];
        }

        $userIdVO = $user_id;
        $rank_dto = $this->rankService->getUserRank($userIdVO);

        return [
            'identity' => [
                'user_id'    => $user_id,
                'email'      => $user->email,
                'first_name' => $user->meta['first_name'] ?? '',
                'created_at' => $user->created_at . 'Z',
            ],
            'economy'  => [
                'points_balance' => $this->userRepository->getPointsBalance($userIdVO),
                'lifetime_points' => $this->userRepository->getLifetimePoints($userIdVO),
            ],
            'status' => [
                'rank_key' => (string) $rank_dto->key,
                'rank_name' => $rank_dto->name,
            ]
        ];
    }
}
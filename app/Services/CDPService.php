<?php
namespace App\Services;

use App\Domain\ValueObjects\UserId;
use App\Infrastructure\WordPressApiWrapperInterface;
use Illuminate\Support\Facades\Log;

/**
 * CDP Service
 *
 * The single, centralized gateway for all communication to the Customer Data Platform.
 */
class CDPService {
    private RankService $rankService;
    private WordPressApiWrapperInterface $wp;

    public function __construct(RankService $rankService, WordPressApiWrapperInterface $wp) {
        $this->rankService = $rankService;
        $this->wp = $wp;
    }

    /**
     * The single entry point for tracking all events.
     */
    public function track( int $user_id, string $event_name, array $properties = [] ) {
        $user_snapshot = $this->build_user_snapshot( $user_id );
        $final_payload = array_merge( $properties, [ 'user_snapshot' => $user_snapshot ] );

        // In a real implementation, this is where you would get your API keys.
        // $site_id = $this->wp->getOption('customer_io_site_id');
        // $api_key = $this->wp->getOption('customer_io_api_key');
        // if (empty($site_id) || empty($api_key)) {
        //     Log::error("CannaRewards CDP Service: Customer.io API credentials are not set.");
        //     return;
        // }
        
        // For now, we will log the event to the debug log instead of making a real API call.
        // This allows us to develop and test the event structure without needing live credentials.
        Log::info('[CannaRewards CDP Event] User ID: ' . $user_id . ' | Event: ' . $event_name . ' | Payload: ' . json_encode($final_payload));
    }

    /**
     * Builds the rich user snapshot object that is attached to every event.
     */
    private function build_user_snapshot( int $user_id ): array {
        $user = $this->wp->getUserById($user_id);
        if ( ! $user ) {
            return [];
        }

        $userIdVO = UserId::fromInt($user_id);
        $rank_dto = $this->rankService->getUserRank($userIdVO);

        return [
            'identity' => [
                'user_id'    => $user_id,
                'email'      => $user->user_email,
                'first_name' => $user->first_name,
                'created_at' => $user->user_registered . 'Z',
            ],
            'economy'  => [
                'points_balance' => (int) $this->wp->getUserMeta($user_id, '_canna_points_balance', true),
                'lifetime_points' => (int) $this->wp->getUserMeta($user_id, '_canna_lifetime_points', true),
            ],
            'status' => [
                'rank_key' => (string) $rank_dto->key,
                'rank_name' => $rank_dto->name,
            ]
        ];
    }
}
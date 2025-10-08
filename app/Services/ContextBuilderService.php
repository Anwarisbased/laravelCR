<?php
namespace App\Services;


use App\Domain\ValueObjects\UserId;
use App\Repositories\ActionLogRepository; // <<<--- IMPORT THE REPOSITORY
use App\Repositories\UserRepository;

/**
 * Context Builder Service
 */
class ContextBuilderService {

    private RankService $rankService;
    private ActionLogRepository $actionLogRepo; // <<<--- ADD THE REPOSITORY PROPERTY
    private UserRepository $userRepository; // <<<--- ADD THE USER REPOSITORY PROPERTY

    public function __construct(
        RankService $rankService,
        ActionLogRepository $actionLogRepo, // <<<--- INJECT THE REPOSITORY
        UserRepository $userRepository // <<<--- INJECT THE USER REPOSITORY
    ) {
        $this->rankService = $rankService;
        $this->actionLogRepo = $actionLogRepo;
        $this->userRepository = $userRepository;
    }

    /**
     * Builds the complete, enriched context for a given event.
     */
    public function build_event_context( \App\Domain\ValueObjects\UserId $user_id, ?object $product_post = null ): array {
        return [
            'user_snapshot'    => $this->build_user_snapshot( $user_id ),
            'product_snapshot' => $product_post ? $this->build_product_snapshot( $product_post ) : null,
            'event_context'    => $this->build_event_context_snapshot(),
        ];
    }

    /**
     * Assembles the complete user_snapshot object according to the Data Taxonomy.
     */
    private function build_user_snapshot( \App\Domain\ValueObjects\UserId $user_id ): array {
        $user = $this->userRepository->getUserCoreData($user_id);
        if ( ! $user ) {
            return [];
        }

        // --- THIS IS THE FIX ---
        // Instead of a direct DB query, we use the clean, abstracted repository method.
        $total_scans = $this->actionLogRepo->countUserActions($user_id, 'scan');
        // --- END FIX ---
        
        $rank_dto = $this->rankService->getUserRank($user_id);

        return [
            'identity' => [
                'user_id'    => $user_id->toInt(),
                'email'      => $user->email,
                'first_name' => $user->meta['first_name'] ?? '',
                'created_at' => $user->created_at . 'Z',
            ],
            'economy'  => [
                'points_balance' => $this->userRepository->getPointsBalance($user_id),
                'lifetime_points' => $this->userRepository->getLifetimePoints($user_id),
            ],
            'status' => [
                'rank_key' => (string) $rank_dto->key,
                'rank_name' => $rank_dto->name,
            ],
            'engagement' => [
                'total_scans' => $total_scans
            ]
        ];
    }

    /**
     * Assembles the complete product_snapshot object from a post object.
     */
    private function build_product_snapshot( object $product_post ): array {
        // In a pure Laravel implementation, we would have a proper Product model
        // For now, return a basic structure
        return [
            'identity' => [
                'product_id'   => $product_post->ID ?? 0,
                'sku'          => $product_post->sku ?? '',
                'product_name' => $product_post->post_title ?? '',
            ],
            'economy' => [
                'points_award' => (int) ($product_post->points_award ?? 0),
                'points_cost'  => (int) ($product_post->points_cost ?? 0),
            ],
            'taxonomy' => [
                'product_form' => 'Vape', // Placeholder
                'strain_type'  => 'Sativa', // Placeholder
            ],
        ];
    }

    /**
     * Assembles the event_context snapshot from server variables.
     */
    private function build_event_context_snapshot(): array {
        return [
            'time'     => [
                'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            ],
            'location' => [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ],
            'device'   => [
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ],
        ];
    }
}
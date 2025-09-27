<?php
namespace App\Services;

use App\Includes\EventBusInterface;
use App\Repositories\UserRepository;
use App\Infrastructure\WordPressApiWrapperInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService {
    private CDPService $cdp_service;
    private UserRepository $user_repository;
    private EventBusInterface $eventBus;
    private WordPressApiWrapperInterface $wp;
    
    public function __construct(
        CDPService $cdp_service,
        UserRepository $user_repository,
        EventBusInterface $eventBus,
        WordPressApiWrapperInterface $wp
    ) {
        $this->cdp_service = $cdp_service;
        $this->user_repository = $user_repository;
        $this->eventBus = $eventBus;
        $this->wp = $wp;

        // <<<--- ADD THIS LISTENER ---
        $this->eventBus->listen('user_created', [$this, 'onUserCreated']);
    }

    /**
     * Event listener that triggers when a new user is created.
     * Responsible for generating their referral code and processing
     * any referral code they used to sign up.
     *
     * @param array $payload The event data from the EventBus.
     */
    public function onUserCreated(array $payload): void
    {
        $userId = $payload['user_id'] ?? null;
        $userFirstName = $payload['firstName'] ?? '';

        if (!$userId) {
            return;
        }

        // 1. Generate a new referral code for the user who just signed up.
        $this->generate_code_for_new_user($userId, $userFirstName);

        // 2. If they used a referral code, process it.
        $referralCodeUsed = $payload['referral_code'] ?? null;
        if ($referralCodeUsed) {
            $this->process_new_user_referral($userId, $referralCodeUsed);
        }
    }
    
    // ... (existing methods like process_new_user_referral, handle_referral_conversion, etc. remain here) ...

    public function process_new_user_referral(int $new_user_id, string $referral_code) {
        if (empty($new_user_id) || empty($referral_code)) {
            return;
        }

        $referrer_user_id = $this->user_repository->findUserIdByReferralCode($referral_code);

        if ($referrer_user_id) {
            $new_user_id_vo = \App\Domain\ValueObjects\UserId::fromInt($new_user_id);
            $referrer_user_id_vo = \App\Domain\ValueObjects\UserId::fromInt($referrer_user_id);
            $this->user_repository->setReferredBy($new_user_id_vo, $referrer_user_id_vo);
        }
    }

    public function handle_referral_conversion(array $payload) {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        if (empty($user_id)) { 
            return; 
        }
        $user_id_vo = \App\Domain\ValueObjects\UserId::fromInt($user_id);
        $referrer_user_id = $this->user_repository->getReferringUserId($user_id_vo);
        
        if ($referrer_user_id) {
            $this->execute_triggers('referral_converted', $referrer_user_id, ['invitee_id' => $user_id]);
        }
    }
    
    private function execute_triggers(string $event_key, int $user_id, array $context = []) {
        // This logic is simplified for brevity. A real implementation would be more robust.
        if ($event_key === 'referral_converted') {
            $this->eventBus->dispatch('points_to_be_granted', [
                'user_id'     => $user_id,
                'points'      => 500, // Example: 500 points for a conversion
                'description' => 'Referral Converted Bonus'
            ]);
        }
    }
    
    public function generate_code_for_new_user(int $user_id, string $first_name = ''): string {
        $base_code_name = !empty($first_name) ? $first_name : 'USER';
        $base_code      = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $base_code_name), 0, 8));
        do {
            $unique_part = strtoupper($this->wp->generatePassword(4, false, false));
            $new_code    = $base_code . $unique_part;
            $exists = $this->user_repository->findUserIdByReferralCode($new_code);
        } while (!is_null($exists));
        
        $user_id_vo = new \App\Domain\ValueObjects\UserId($user_id);
        $this->user_repository->saveReferralCode($user_id_vo, $new_code);
        
        // Also update the Eloquent model's meta array for consistency
        $user = \App\Models\User::find($user_id);
        if ($user) {
            $meta = $user->meta ?? [];
            $meta['_canna_referral_code'] = $new_code;
            $user->meta = $meta;
            $user->save();
        }
        
        return $new_code;
    }

    /**
     * Get the status of all users referred by a specific user.
     */
    public function get_user_referrals(int $user_id): array {
        $referees = \App\Models\User::where('meta->_canna_referred_by_user_id', $user_id)->get();

        if ($referees->isEmpty()) {
            return [];
        }

        $refereeIds = $referees->pluck('id')->toArray();

        // Find which of these referees have made at least one scan
        $convertedIds = \Illuminate\Support\Facades\DB::table('canna_user_action_log')
            ->where('action_type', 'scan')
            ->whereIn('user_id', $refereeIds)
            ->distinct()
            ->pluck('user_id')
            ->flip(); // Flip for O(1) lookups

        $referralData = [];
        foreach ($referees as $referee) {
            $referralData[] = [
                'email' => $referee->email,
                'status' => isset($convertedIds[$referee->id]) ? 'Converted' : 'Pending',
            ];
        }

        return $referralData;
    }

    /**
     * Get nudge options for a referee. (Stubbed as per test)
     */
    public function get_nudge_options_for_referee(int $user_id, string $email): array { 
        // This can be implemented later to provide SMS/Email options
        return []; 
    }
}
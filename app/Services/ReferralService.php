<?php
namespace App\Services;

use App\Events\ReferralInviteeSignedUp;
use App\Events\ReferralConverted;
use App\Jobs\AwardReferralBonus;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralBonusAwardedNotification;
use App\Repositories\UserRepository;
use App\Services\ReferralCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class ReferralService {
    private CDPService $cdp_service;
    private UserRepository $user_repository;
    private ?ReferralCodeService $referral_code_service;
    
    public function __construct(
        CDPService $cdp_service,
        UserRepository $user_repository,
        ReferralCodeService $referral_code_service
    ) {
        $this->cdp_service = $cdp_service;
        $this->user_repository = $user_repository;
        $this->referral_code_service = $referral_code_service;
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

        $userIdVO = \App\Domain\ValueObjects\UserId::fromInt($userId);

        // 1. Generate a new referral code for the user who just signed up.
        $this->generate_code_for_new_user($userIdVO, $userFirstName);

        // 2. If they used a referral code, process it.
        $referralCodeUsed = $payload['referral_code'] ?? null;
        if ($referralCodeUsed) {
            $this->process_new_user_referral($userIdVO, \App\Domain\ValueObjects\ReferralCode::fromString($referralCodeUsed));
        }
    }
    
    // Original method for processing new user referral
    public function process_new_user_referral(\App\Domain\ValueObjects\UserId $new_user_id, \App\Domain\ValueObjects\ReferralCode $referral_code) {
        $referrer_user_id = $this->user_repository->findUserIdByReferralCode($referral_code->value);

        if ($referrer_user_id) {
            $referrer_user_id_vo = \App\Domain\ValueObjects\UserId::fromInt($referrer_user_id);
            $this->user_repository->setReferredBy($new_user_id, $referrer_user_id_vo);
        }
    }

    /**
     * Process a new user sign up with a referral code.
     * @param string|ReferralCode $referral_code The referral code as string or value object
     */
    public function processSignUp(User $invitee, $referral_code): bool
    {
        // Convert to value object if it's a raw string
        if (is_string($referral_code)) {
            $referralCodeVO = \App\Domain\ValueObjects\ReferralCode::fromString($referral_code);
        } else {
            $referralCodeVO = $referral_code;
        }
        
        // Validate referral code
        if (!$this->referral_code_service->isValid($referralCodeVO->value)) {
            return false;
        }
        
        // Find referrer
        $referrer = $this->referral_code_service->getUserByReferralCode($referralCodeVO->value);
        if (!$referrer) {
            return false;
        }
        
        // Prevent users from referring themselves
        if ($referrer->id === $invitee->id) {
            return false;
        }
        
        // Check if user has already been referred
        $existingReferral = Referral::where('invitee_user_id', $invitee->id)->first();
        if ($existingReferral) {
            return false; // User has already been referred
        }
        
        // Create referral record
        $referral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee->id,
            'referral_code' => $referralCodeVO->value,
            'status' => 'signed_up',
        ]);
        
        // Fire event
        Event::dispatch(new ReferralInviteeSignedUp($referrer, $invitee, $referralCodeVO->value));
        
        return true;
    }
    
    /**
     * Handle referral conversion when invitee makes first scan.
     */
    public function handle_referral_conversion($invitee)
    {
        // Check if invitee is already a User model, or if it's an array payload
        if (is_array($invitee)) {
            // Extract user ID from payload and get the User model
            $userId = $invitee['user']['id'] ?? null;
            if (!$userId) {
                return; // Can't process without user ID
            }
            $invitee = User::find($userId);
            if (!$invitee) {
                return; // User not found
            }
        }
        
        // Ensure we have a valid User model at this point
        if (!$invitee instanceof User) {
            return; // Invalid invitee provided
        }
        
        $referral = Referral::where('invitee_user_id', $invitee->id)
            ->where('status', 'signed_up')
            ->first();
            
        if (!$referral) {
            return;
        }
        
        // Update referral status
        $referral->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
        
        // Award bonus to referrer
        $this->award_bonus_to_referrer($referral->referrer, $invitee);
        
        // Fire event
        Event::dispatch(new ReferralConverted($referral->referrer, $invitee));
    }
    
    /**
     * Award bonus points to referrer
     */
    private function award_bonus_to_referrer(User $referrer, User $invitee)
    {
        // Award points to referrer
        $points = config('cannarewards.referral_bonus_points', 500);
        
        // Dispatch job to award points
        AwardReferralBonus::dispatch($referrer->id, $points, "Referral bonus for {$invitee->email}");
        
        // Update referral record
        Referral::where('referrer_user_id', $referrer->id)
            ->where('invitee_user_id', $invitee->id)
            ->update(['bonus_points_awarded' => $points]);
            
        // Send notification
        $referrer->notify(new ReferralBonusAwardedNotification($points, $invitee));
    }
    
    /**
     * Generate a referral code for a new user.
     */
    public function generate_code_for_new_user(\App\Domain\ValueObjects\UserId $user_id, string $first_name = ''): string 
    {
        $base_code_name = !empty($first_name) ? $first_name : 'USER';
        $base_code      = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $base_code_name), 0, 4));
        $attempts = 0;
        $maxAttempts = 10;
        
        do {
            $unique_part = strtoupper(Str::random(4));
            $new_code    = $base_code . $unique_part;
            $exists = $this->user_repository->findUserIdByReferralCode($new_code);
            $attempts++;
        } while (!is_null($exists) && $attempts < $maxAttempts);
        
        // Fallback to random code generation if name-based fails
        if (!is_null($exists)) {
            do {
                $new_code = strtoupper(Str::random(8));
                $exists = $this->user_repository->findUserIdByReferralCode($new_code);
            } while (!is_null($exists));
        }
        
        $this->user_repository->saveReferralCode($user_id, $new_code);
        
        return $new_code;
    }

    /**
     * Get the status of all users referred by a specific user.
     */
    public function get_user_referrals(\App\Domain\ValueObjects\UserId $user_id): array 
    {
        $user = User::find($user_id->toInt());
        if (!$user) {
            return [];
        }
        
        $referrals = $user->referrals()->with('invitee')->get();
        
        return $referrals->map(function ($referral) {
            // Map internal status to user-friendly status
            $status = match($referral->status) {
                'signed_up' => 'Pending',
                'converted' => 'Converted',
                default => $referral->status
            };
            
            return [
                'id' => $referral->id,
                'invitee_email' => $referral->invitee->email,
                'status' => $status,
                'converted_at' => $referral->converted_at,
                'bonus_points_awarded' => $referral->bonus_points_awarded,
                'created_at' => $referral->created_at,
            ];
        })->toArray();
    }

    /**
     * Get referral stats for a user.
     */
    public function get_referral_stats(\App\Domain\ValueObjects\UserId $user_id): array
    {
        $user = User::find($user_id->toInt());
        if (!$user) {
            return [
                'total_referrals' => 0,
                'converted_referrals' => 0,
                'conversion_rate' => 0,
            ];
        }
        
        $totalReferrals = $user->referrals()->count();
        $convertedReferrals = $user->referrals()->converted()->count();
        
        return [
            'total_referrals' => $totalReferrals,
            'converted_referrals' => $convertedReferrals,
            'conversion_rate' => $totalReferrals > 0 ? ($convertedReferrals / $totalReferrals) * 100 : 0,
        ];
    }

    /**
     * Process referral conversion - wrapper for handle_referral_conversion to maintain compatibility
     */
    public function processConversion(User $invitee): void
    {
        $this->handle_referral_conversion($invitee);
    }
    
    /**
     * Get nudge options for a referee. 
     */
    public function get_nudge_options_for_referee(\App\Domain\ValueObjects\UserId $user_id, $email): array 
    { 
        $user = User::find($user_id->toInt());
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        // Convert to value object if it's a raw string
        if (is_string($email)) {
            $emailVO = \App\Domain\ValueObjects\EmailAddress::fromString($email);
        } else {
            $emailVO = $email;
        }

        // Validate email
        if (!filter_var($emailVO->value, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address'];
        }
        
        // Check if already referred
        $existingReferral = Referral::whereHas('invitee', function ($query) use ($emailVO) {
            $query->where('email', $emailVO->value);
        })->first();
        
        if ($existingReferral) {
            return ['error' => 'This person has already been referred'];
        }
        
        // Check if the user is trying to refer themselves
        if ($user->email === $emailVO->value) {
            return ['error' => 'You cannot refer yourself'];
        }
        
        return [
            'can_nudge' => true,
            'message' => "Invite {$emailVO->value} to earn bonus points!",
            'referral_code' => $user->referral_code,
        ]; 
    }
    
    /**
     * Get referral data for a user using Data classes.
     */
    public function get_user_referral_data(\App\Domain\ValueObjects\UserId $user_id): \App\Data\ReferralCollectionData
    {
        $user = User::find($user_id->toInt());
        if (!$user) {
            return \App\Data\ReferralCollectionData::fromServiceResponse([], [
                'total_referrals' => 0,
                'converted_referrals' => 0,
                'conversion_rate' => 0,
            ]);
        }
        
        $referrals = $user->referrals()->with('invitee')->get();
        $referralsArray = $referrals->map(function ($referral) {
            // Map internal status to user-friendly status
            $status = match($referral->status) {
                'signed_up' => 'Pending',
                'converted' => 'Converted',
                default => $referral->status
            };
            
            return [
                'id' => $referral->id,
                'invitee_email' => $referral->invitee->email,
                'status' => $status,
                'converted_at' => $referral->converted_at ? $referral->converted_at->format('Y-m-d H:i:s') : null,
                'bonus_points_awarded' => $referral->bonus_points_awarded,
                'created_at' => $referral->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
        
        $totalReferrals = $user->referrals()->count();
        $convertedReferrals = $user->referrals()->converted()->count();
        $stats = [
            'total_referrals' => $totalReferrals,
            'converted_referrals' => $convertedReferrals,
            'conversion_rate' => $totalReferrals > 0 ? ($convertedReferrals / $totalReferrals) * 100 : 0,
        ];
        
        return \App\Data\ReferralCollectionData::fromServiceResponse($referralsArray, $stats);
    }
}
<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SessionData extends Data
{
    public function __construct(
        public int $id,
        #[MapName('first_name')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $firstName = null,
        #[MapName('last_name')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $lastName = null,
        #[Validation(['required', 'email', 'max:255'])]
        public string $email,
        #[MapName('points_balance')]
        #[Validation(['integer', 'min:0'])]
        public int $pointsBalance = 0,
        public ?array $rank = null,
        #[MapName('shipping_address')]
        public ?array $shippingAddress = null,
        #[MapName('referral_code')]
        #[Validation(['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/'])]
        public ?string $referralCode = null,
        #[MapName('feature_flags')]
        public ?array $featureFlags = null,
    ) {
    }

    /**
     * Create from Session DTO
     */
    public static function fromSessionDto($sessionDto): self
    {
        try {
            // Handle both object and array formats for rank
            $rankData = null;
            if ($sessionDto->rank) {
                if (is_object($sessionDto->rank)) {
                    $rankData = [
                        'key' => (string) $sessionDto->rank->key,
                        'name' => $sessionDto->rank->name,
                        'points_required' => is_object($sessionDto->rank->pointsRequired) ? $sessionDto->rank->pointsRequired->toInt() : $sessionDto->rank->pointsRequired,
                        'point_multiplier' => $sessionDto->rank->pointMultiplier,
                    ];
                } elseif (is_array($sessionDto->rank)) {
                    $rankData = [
                        'key' => (string) ($sessionDto->rank['key'] ?? $sessionDto->rank['key'] ?? ''),
                        'name' => $sessionDto->rank['name'] ?? '',
                        'points_required' => is_object($sessionDto->rank['points_required']) ? $sessionDto->rank['points_required']->toInt() : ($sessionDto->rank['points_required'] ?? 0),
                        'point_multiplier' => $sessionDto->rank['point_multiplier'] ?? 1.0,
                    ];
                }
            }

            return new self(
                id: is_object($sessionDto->id) ? $sessionDto->id->toInt() : $sessionDto->id,
                firstName: $sessionDto->firstName ?? null,
                lastName: $sessionDto->lastName ?? null,
                email: (string) ($sessionDto->email ?? ''),
                pointsBalance: is_object($sessionDto->pointsBalance) ? $sessionDto->pointsBalance->toInt() : ($sessionDto->pointsBalance ?? 0),
                rank: $rankData,
                shippingAddress: $sessionDto->shippingAddress ? (array) $sessionDto->shippingAddress : null,
                referralCode: $sessionDto->referralCode ?? null,
                featureFlags: (array) ($sessionDto->featureFlags ?? []),
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'SessionDTO',
                self::class,
                $e->getMessage()
            );
        }
    }
    
    /**
     * Create from User model
     */
    public static function fromModel(\App\Models\User $user): self
    {
        try {
            return new self(
                id: $user->id,
                firstName: $user->first_name,
                lastName: $user->last_name,
                email: $user->email,
                pointsBalance: $user->points_balance ?? 0,
                rank: $user->current_rank ? [
                    'key' => $user->current_rank_key,
                    'name' => $user->current_rank->name ?? 'Member',
                    'points_required' => $user->current_rank->points_required ?? 0,
                    'point_multiplier' => $user->current_rank->point_multiplier ?? 1.0,
                ] : null,
                shippingAddress: $user->shipping_address,
                referralCode: $user->referral_code,
                featureFlags: $user->feature_flags ?: [],
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\User::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}